<?php

declare(strict_types=1);

namespace SuluContentImportExportBundle\Controller\Admin;

use Psr\Log\LoggerInterface;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\Security\Authorization\PermissionTypes;
use SuluContentImportExportBundle\Admin\DocumentExtensionsHelper;
use SuluContentImportExportBundle\Admin\DocumentJsonManager;
use SuluContentImportExportBundle\Admin\JsonRequestDecoder;
use SuluContentImportExportBundle\Admin\StructureJsonValidator;
use SuluContentImportExportBundle\Resource\ResourceRegistry;
use SuluContentImportExportBundle\Security\PermissionCheckerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/content-json/{resource}/{id}')]
final class ContentJsonController extends AbstractController
{
    public function __construct(
        private readonly DocumentManagerInterface $documentManager,
        private readonly DocumentJsonManager $documentJsonManager,
        private readonly JsonRequestDecoder $jsonRequestDecoder,
        private readonly StructureJsonValidator $structureJsonValidator,
        private readonly DocumentExtensionsHelper $documentExtensionsHelper,
        private readonly ResourceRegistry $resourceRegistry,
        private readonly PermissionCheckerRegistry $permissionCheckerRegistry,
        private readonly LoggerInterface $logger,
        private readonly string $defaultLocale,
        private readonly string $csrfTokenId,
    ) {
    }

    #[Route(name: 'sulu_content_import_export_admin_content_show', methods: ['GET'])]
    public function show(string $resource, string $id, Request $request): JsonResponse
    {
        $definition = $this->getEnabledDefinition($resource);
        $locale = $request->query->getString('locale', $this->defaultLocale);

        $document = $this->documentManager->find($id, $locale);
        $this->permissionCheckerRegistry->get($resource)->check($document, $locale, PermissionTypes::VIEW);

        $content = $document->getStructure()->toArray();

        return new JsonResponse([
            'id' => $document->getUuid(),
            'locale' => $document->getLocale(),
            'structureType' => $document->getStructureType(),
            'content' => $content,
            'issues' => $this->structureJsonValidator->validate($definition->getMetadataType(), $document->getStructureType(), $content),
            'seo' => $definition->hasSeo() ? $this->documentExtensionsHelper->extractSeo($document) : null,
        ]);
    }

    #[Route('/validate', name: 'sulu_content_import_export_admin_content_validate', methods: ['POST'])]
    public function validateContent(string $resource, string $id, Request $request): JsonResponse
    {
        $definition = $this->getEnabledDefinition($resource);
        $locale = $request->query->getString('locale', $this->defaultLocale);

        try {
            $body = $this->jsonRequestDecoder->decode($request);
            $csrfToken = $this->jsonRequestDecoder->requireStringField($body, '_token');
            $payload = $this->jsonRequestDecoder->requireStructurePayload($body);
        } catch (\InvalidArgumentException $exception) {
            return new JsonResponse(['error' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        if (!$this->isCsrfTokenValid($this->csrfTokenId, $csrfToken)) {
            return new JsonResponse(['error' => 'Invalid CSRF token.'], Response::HTTP_FORBIDDEN);
        }

        $document = $this->documentManager->find($id, $locale);
        $this->permissionCheckerRegistry->get($resource)->check($document, $locale, PermissionTypes::VIEW);

        $issues = $this->structureJsonValidator->validate($definition->getMetadataType(), $payload['structureType'], $payload['content']);

        return new JsonResponse([
            'issues' => $issues,
            'errorCount' => \count(\array_filter($issues, static fn(array $issue): bool => 'error' === $issue['level'])),
        ]);
    }

    #[Route(name: 'sulu_content_import_export_admin_content_save', methods: ['POST'])]
    public function saveContent(string $resource, string $id, Request $request): JsonResponse
    {
        $definition = $this->getEnabledDefinition($resource);
        $locale = $request->query->getString('locale', $this->defaultLocale);

        try {
            $body = $this->jsonRequestDecoder->decode($request, true);
            $csrfToken = $this->jsonRequestDecoder->requireStringField($body, '_token');
            $content = $this->jsonRequestDecoder->requireArrayField($body, 'content');
        } catch (\InvalidArgumentException $exception) {
            return new JsonResponse(['error' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        if (!$this->isCsrfTokenValid($this->csrfTokenId, $csrfToken)) {
            return new JsonResponse(['error' => 'Invalid CSRF token.'], Response::HTTP_FORBIDDEN);
        }

        try {
            $document = $this->documentJsonManager->loadForEditing($id, $locale);
            $this->permissionCheckerRegistry->get($resource)->check($document, $locale, PermissionTypes::EDIT);
            $this->documentJsonManager->saveContent($document, $locale, $content);
        } catch (\Throwable $exception) {
            $this->logger->error(\sprintf('Failed to save %s JSON content.', $resource), [
                'exception' => $exception,
                'documentId' => $id,
                'locale' => $locale,
                'resource' => $resource,
            ]);

            return new JsonResponse(['error' => \sprintf('Failed to save %s content.', $resource)], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse(['success' => true, 'message' => $definition->getSaveSuccessMessage()]);
    }

    #[Route('/seo', name: 'sulu_content_import_export_admin_content_save_seo', methods: ['POST'])]
    public function saveSeo(string $resource, string $id, Request $request): JsonResponse
    {
        $definition = $this->getEnabledDefinition($resource);
        if (!$definition->hasSeo()) {
            return new JsonResponse(['error' => 'SEO is not supported for this resource.'], Response::HTTP_NOT_FOUND);
        }

        $locale = $request->query->getString('locale', $this->defaultLocale);

        try {
            $body = $this->jsonRequestDecoder->decode($request, true);
            $csrfToken = $this->jsonRequestDecoder->requireStringField($body, '_token');
            $seoContent = $this->jsonRequestDecoder->requireArrayField($body, 'seo');
        } catch (\InvalidArgumentException $exception) {
            return new JsonResponse(['error' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        if (!$this->isCsrfTokenValid($this->csrfTokenId, $csrfToken)) {
            return new JsonResponse(['error' => 'Invalid CSRF token.'], Response::HTTP_FORBIDDEN);
        }

        try {
            $document = $this->documentJsonManager->loadForEditing($id, $locale);
            $this->permissionCheckerRegistry->get($resource)->check($document, $locale, PermissionTypes::EDIT);
            $this->documentJsonManager->saveSeo($document, $locale, $seoContent);
        } catch (\Throwable $exception) {
            $this->logger->error(\sprintf('Failed to save %s SEO JSON.', $resource), [
                'exception' => $exception,
                'documentId' => $id,
                'locale' => $locale,
                'resource' => $resource,
            ]);

            return new JsonResponse(['error' => \sprintf('Failed to save %s SEO.', $resource)], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse(['success' => true, 'message' => $definition->getSeoSuccessMessage() ?? 'Saved']);
    }

    private function getEnabledDefinition(string $resource)
    {
        $definition = $this->resourceRegistry->get($resource);
        if (!$definition->isEnabled()) {
            throw $this->createNotFoundException(\sprintf('Resource "%s" is disabled.', $resource));
        }

        return $definition;
    }
}
