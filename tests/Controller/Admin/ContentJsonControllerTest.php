<?php

declare(strict_types=1);

namespace SuluContentImportExportBundle\Tests\Controller\Admin;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactoryInterface;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use SuluContentImportExportBundle\Admin\DocumentExtensionsHelper;
use SuluContentImportExportBundle\Admin\DocumentJsonManager;
use SuluContentImportExportBundle\Admin\JsonRequestDecoder;
use SuluContentImportExportBundle\Admin\StructureJsonValidator;
use SuluContentImportExportBundle\Controller\Admin\ContentJsonController;
use SuluContentImportExportBundle\Resource\ResourceRegistry;
use SuluContentImportExportBundle\Security\PermissionCheckerInterface;
use SuluContentImportExportBundle\Security\PermissionCheckerRegistry;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

final class ContentJsonControllerTest extends TestCase
{
    private DocumentManagerInterface&MockObject $documentManager;
    private StructureMetadataFactoryInterface&MockObject $structureMetadataFactory;
    private LoggerInterface&MockObject $logger;
    private PermissionCheckerInterface&MockObject $pagePermissionChecker;
    private PermissionCheckerInterface&MockObject $snippetPermissionChecker;
    private PermissionCheckerInterface&MockObject $articlePermissionChecker;
    private DocumentJsonManager $documentJsonManager;
    private JsonRequestDecoder $jsonRequestDecoder;
    private StructureJsonValidator $structureJsonValidator;
    private DocumentExtensionsHelper $documentExtensionsHelper;

    protected function setUp(): void
    {
        $this->documentManager = $this->createMock(DocumentManagerInterface::class);
        $this->structureMetadataFactory = $this->createMock(StructureMetadataFactoryInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->pagePermissionChecker = $this->createMock(PermissionCheckerInterface::class);
        $this->snippetPermissionChecker = $this->createMock(PermissionCheckerInterface::class);
        $this->articlePermissionChecker = $this->createMock(PermissionCheckerInterface::class);
        $this->jsonRequestDecoder = new JsonRequestDecoder();
        $this->documentExtensionsHelper = new DocumentExtensionsHelper();
        $this->structureJsonValidator = new StructureJsonValidator($this->structureMetadataFactory);
        $this->documentJsonManager = new DocumentJsonManager($this->documentManager, $this->documentExtensionsHelper);
    }

    public function testShowReturnsPagePayloadWithSeoAndUsesViewPermission(): void
    {
        $document = $this->createDocumentStub('page-1', 'en', 'default', ['title' => 'Homepage'], ['seo' => ['title' => 'Meta']]);

        $this->documentManager
            ->expects(self::once())
            ->method('find')
            ->with('page-1', 'en')
            ->willReturn($document);
        $this->pagePermissionChecker
            ->expects(self::once())
            ->method('check')
            ->with($document, 'en', 'view');
        $this->structureMetadataFactory
            ->expects(self::once())
            ->method('getStructureMetadata')
            ->with('page', 'default')
            ->willThrowException(new \RuntimeException('missing'));

        $response = $this->createController()->show(
            'page',
            'page-1',
            Request::create('/admin/content-json/page/page-1', 'GET', ['locale' => 'en'])
        );

        self::assertSame([
            'id' => 'page-1',
            'locale' => 'en',
            'structureType' => 'default',
            'content' => ['title' => 'Homepage'],
            'issues' => [['level' => 'warning', 'path' => '', 'message' => "Template 'default' metadata not found"]],
            'seo' => ['title' => 'Meta'],
        ], \json_decode((string) $response->getContent(), true, 512, \JSON_THROW_ON_ERROR));
    }

    public function testShowReturnsSnippetPayloadWithoutSeo(): void
    {
        $document = $this->createDocumentStub('snippet-1', 'hr', 'default', ['title' => 'Snippet']);

        $this->documentManager
            ->expects(self::once())
            ->method('find')
            ->with('snippet-1', 'hr')
            ->willReturn($document);
        $this->snippetPermissionChecker
            ->expects(self::once())
            ->method('check')
            ->with($document, 'hr', 'view');
        $this->structureMetadataFactory
            ->expects(self::once())
            ->method('getStructureMetadata')
            ->with('snippet', 'default')
            ->willThrowException(new \RuntimeException('missing'));

        $response = $this->createController()->show(
            'snippet',
            'snippet-1',
            Request::create('/admin/content-json/snippet/snippet-1', 'GET', ['locale' => 'hr'])
        );

        $payload = \json_decode((string) $response->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        self::assertSame('snippet-1', $payload['id']);
        self::assertSame(null, $payload['seo']);
    }

    public function testValidateContentReturnsIssuesAndErrorCount(): void
    {
        $document = $this->createDocumentStub('article-1', 'de', 'article', ['title' => 'Article']);

        $this->documentManager
            ->expects(self::once())
            ->method('find')
            ->with('article-1', 'de')
            ->willReturn($document);
        $this->articlePermissionChecker
            ->expects(self::once())
            ->method('check')
            ->with($document, 'de', 'view');
        $this->structureMetadataFactory
            ->expects(self::once())
            ->method('getStructureMetadata')
            ->with('article', 'article')
            ->willThrowException(new \RuntimeException('missing'));

        $response = $this->createController()->validateContent(
            'article',
            'article-1',
            Request::create(
                '/admin/content-json/article/article-1/validate?locale=de',
                'POST',
                [],
                [],
                [],
                [],
                \json_encode([
                    '_token' => 'valid-token',
                    'structureType' => 'article',
                    'content' => ['title' => 'Article'],
                ], \JSON_THROW_ON_ERROR)
            )
        );

        self::assertSame([
            'issues' => [
                ['level' => 'warning', 'path' => '', 'message' => "Template 'article' metadata not found"],
            ],
            'errorCount' => 0,
        ], \json_decode((string) $response->getContent(), true, 512, \JSON_THROW_ON_ERROR));
    }

    public function testValidateContentReturnsBadRequestWhenDecoderFails(): void
    {
        $response = $this->createController()->validateContent(
            'page',
            'page-1',
            Request::create('/admin/content-json/page/page-1/validate', 'POST', [], [], [], [], '{"broken"')
        );

        self::assertSame(400, $response->getStatusCode());
        self::assertSame(['error' => 'Invalid JSON'], \json_decode((string) $response->getContent(), true, 512, \JSON_THROW_ON_ERROR));
    }

    public function testValidateContentReturnsForbiddenForInvalidCsrf(): void
    {
        $response = $this->createController(false)->validateContent(
            'page',
            'page-1',
            Request::create(
                '/admin/content-json/page/page-1/validate',
                'POST',
                [],
                [],
                [],
                [],
                \json_encode([
                    '_token' => 'invalid-token',
                    'structureType' => 'default',
                    'content' => ['title' => 'Test'],
                ], \JSON_THROW_ON_ERROR)
            )
        );

        self::assertSame(403, $response->getStatusCode());
        self::assertSame(['error' => 'Invalid CSRF token.'], \json_decode((string) $response->getContent(), true, 512, \JSON_THROW_ON_ERROR));
    }

    public function testSaveContentUsesDefaultLocaleAndReturnsConfiguredMessage(): void
    {
        $document = $this->createEditableDocumentStub('page-1', 'en', 'default', ['title' => 'Homepage']);

        $this->documentManager
            ->expects(self::once())
            ->method('find')
            ->with('page-1', 'en', [
                'load_ghost_content' => false,
                'load_shadow_content' => false,
            ])
            ->willReturn($document);
        $this->pagePermissionChecker
            ->expects(self::once())
            ->method('check')
            ->with($document, 'en', 'edit');
        $this->documentManager
            ->expects(self::once())
            ->method('persist')
            ->with($document, 'en', ['clear_missing_content' => false]);
        $this->documentManager
            ->expects(self::once())
            ->method('flush');

        $response = $this->createController()->saveContent(
            'page',
            'page-1',
            Request::create(
                '/admin/content-json/page/page-1',
                'POST',
                [],
                [],
                [],
                [],
                \json_encode([
                    '_token' => 'valid-token',
                    'content' => ['title' => 'Homepage'],
                ], \JSON_THROW_ON_ERROR)
            )
        );

        self::assertSame(['title' => 'Homepage'], $document->getStructure()->boundContent);
        self::assertSame('Homepage', $document->title);
        self::assertSame([
            'success' => true,
            'message' => 'Saved as draft',
        ], \json_decode((string) $response->getContent(), true, 512, \JSON_THROW_ON_ERROR));
    }

    public function testSaveContentReturnsInternalServerErrorAndLogsOnFailure(): void
    {
        $document = $this->createEditableDocumentStub('snippet-1', 'en', 'default', ['title' => 'Snippet']);

        $this->documentManager
            ->expects(self::once())
            ->method('find')
            ->with('snippet-1', 'en', [
                'load_ghost_content' => false,
                'load_shadow_content' => false,
            ])
            ->willReturn($document);
        $this->snippetPermissionChecker
            ->expects(self::once())
            ->method('check')
            ->with($document, 'en', 'edit');
        $this->documentManager
            ->expects(self::once())
            ->method('persist')
            ->willThrowException(new \RuntimeException('persist failed'));
        $this->documentManager
            ->expects(self::never())
            ->method('flush');
        $this->logger
            ->expects(self::once())
            ->method('error')
            ->with(
                'Failed to save snippet JSON content.',
                self::callback(static fn(array $context): bool => 'snippet-1' === $context['documentId'] && 'snippet' === $context['resource'])
            );

        $response = $this->createController()->saveContent(
            'snippet',
            'snippet-1',
            Request::create(
                '/admin/content-json/snippet/snippet-1',
                'POST',
                ['locale' => 'en'],
                [],
                [],
                [],
                \json_encode([
                    '_token' => 'valid-token',
                    'content' => ['title' => 'Snippet'],
                ], \JSON_THROW_ON_ERROR)
            )
        );

        self::assertSame(500, $response->getStatusCode());
        self::assertSame(['error' => 'Failed to save snippet content.'], \json_decode((string) $response->getContent(), true, 512, \JSON_THROW_ON_ERROR));
    }

    public function testSaveSeoSucceedsForArticle(): void
    {
        $document = $this->createEditableDocumentStub('article-1', 'hr', 'article', ['title' => 'Article']);

        $this->documentManager
            ->expects(self::once())
            ->method('find')
            ->with('article-1', 'hr', [
                'load_ghost_content' => false,
                'load_shadow_content' => false,
            ])
            ->willReturn($document);
        $this->articlePermissionChecker
            ->expects(self::once())
            ->method('check')
            ->with($document, 'hr', 'edit');
        $this->documentManager
            ->expects(self::once())
            ->method('persist')
            ->with($document, 'hr', ['clear_missing_content' => false]);
        $this->documentManager
            ->expects(self::once())
            ->method('flush');

        $response = $this->createController()->saveSeo(
            'article',
            'article-1',
            Request::create(
                '/admin/content-json/article/article-1/seo?locale=hr',
                'POST',
                [],
                [],
                [],
                [],
                \json_encode([
                    '_token' => 'valid-token',
                    'seo' => ['title' => 'Meta'],
                ], \JSON_THROW_ON_ERROR)
            )
        );

        self::assertSame([
            'success' => true,
            'message' => 'SEO saved as draft',
        ], \json_decode((string) $response->getContent(), true, 512, \JSON_THROW_ON_ERROR));
        self::assertSame(['title' => 'Meta'], $document->extensionsData['seo'] ?? null);
    }

    public function testSaveSeoReturnsNotFoundForSnippet(): void
    {
        $response = $this->createController()->saveSeo(
            'snippet',
            'snippet-1',
            Request::create('/admin/content-json/snippet/snippet-1/seo', 'POST')
        );

        self::assertSame(404, $response->getStatusCode());
        self::assertSame(['error' => 'SEO is not supported for this resource.'], \json_decode((string) $response->getContent(), true, 512, \JSON_THROW_ON_ERROR));
    }

    public function testDisabledResourceThrowsNotFound(): void
    {
        $this->expectException(NotFoundHttpException::class);

        $this->createController()->show(
            'disabled',
            'page-1',
            Request::create('/admin/content-json/disabled/page-1', 'GET')
        );
    }

    private function createController(bool $csrfValid = true): ContentJsonController
    {
        $registry = new ResourceRegistry([
            'page' => [
                'enabled' => true,
                'metadata_type' => 'page',
                'url_prefix' => 'content-json/page',
                'content_name' => 'Page',
                'content_tab_label' => 'Page Content',
                'has_seo' => true,
                'save_label' => 'Save as Draft',
                'save_success_message' => 'Saved as draft',
                'seo_success_message' => 'SEO saved as draft',
            ],
            'snippet' => [
                'enabled' => true,
                'metadata_type' => 'snippet',
                'url_prefix' => 'content-json/snippet',
                'content_name' => 'Snippet',
                'content_tab_label' => 'Snippet',
                'has_seo' => false,
                'save_label' => 'Save',
                'save_success_message' => 'Saved',
                'seo_success_message' => null,
            ],
            'article' => [
                'enabled' => true,
                'metadata_type' => 'article',
                'url_prefix' => 'content-json/article',
                'content_name' => 'Article',
                'content_tab_label' => 'Article',
                'has_seo' => true,
                'save_label' => 'Save as Draft',
                'save_success_message' => 'Saved as draft',
                'seo_success_message' => 'SEO saved as draft',
                'types' => ['article'],
            ],
            'disabled' => [
                'enabled' => false,
                'metadata_type' => 'page',
                'url_prefix' => 'content-json/disabled',
                'content_name' => 'Disabled',
                'content_tab_label' => 'Disabled',
                'has_seo' => false,
                'save_label' => 'Save',
                'save_success_message' => 'Saved',
                'seo_success_message' => null,
            ],
        ]);

        $permissionRegistry = new PermissionCheckerRegistry([
            'page' => $this->pagePermissionChecker,
            'snippet' => $this->snippetPermissionChecker,
            'article' => $this->articlePermissionChecker,
            'disabled' => $this->pagePermissionChecker,
        ]);

        $csrfTokenManager = new class($csrfValid) implements CsrfTokenManagerInterface {
            public function __construct(
                private readonly bool $valid,
            ) {
            }

            public function getToken(string $tokenId): CsrfToken
            {
                return new CsrfToken($tokenId, 'unused');
            }

            public function refreshToken(string $tokenId): CsrfToken
            {
                return new CsrfToken($tokenId, 'unused');
            }

            public function removeToken(string $tokenId): ?string
            {
                unset($tokenId);

                return null;
            }

            public function isTokenValid(CsrfToken $token): bool
            {
                unset($token);

                return $this->valid;
            }
        };

        $controller = new ContentJsonController(
            $this->documentManager,
            $this->documentJsonManager,
            $this->jsonRequestDecoder,
            $this->structureJsonValidator,
            $this->documentExtensionsHelper,
            $registry,
            $permissionRegistry,
            $this->logger,
            'en',
            'sulu_content_import_export',
        );

        $controller->setContainer(new class($csrfTokenManager) implements ContainerInterface {
            public function __construct(
                private readonly CsrfTokenManagerInterface $csrfTokenManager,
            ) {
            }

            public function get(string $id, int $invalidBehavior = self::EXCEPTION_ON_INVALID_REFERENCE): ?object
            {
                if ('security.csrf.token_manager' === $id) {
                    return $this->csrfTokenManager;
                }

                if (self::EXCEPTION_ON_INVALID_REFERENCE === $invalidBehavior) {
                    throw new \RuntimeException(\sprintf('Unknown service "%s".', $id));
                }

                return null;
            }

            public function has(string $id): bool
            {
                return 'security.csrf.token_manager' === $id;
            }

            public function initialized(string $id): bool
            {
                return $this->has($id);
            }

            public function set(string $id, ?object $service): void
            {
                unset($id, $service);

                throw new \BadMethodCallException('Not supported in tests.');
            }

            public function getParameter(string $name): array|bool|string|int|float|\UnitEnum|null
            {
                unset($name);

                throw new \RuntimeException('No parameters configured in tests.');
            }

            public function hasParameter(string $name): bool
            {
                unset($name);

                return false;
            }

            public function setParameter(string $name, array|bool|string|int|float|\UnitEnum|null $value): void
            {
                unset($name, $value);

                throw new \BadMethodCallException('Not supported in tests.');
            }
        });

        return $controller;
    }

    /**
     * @param array<string, mixed> $content
     * @param array<string, mixed> $extensionsData
     */
    private function createDocumentStub(string $id, string $locale, string $structureType, array $content, array $extensionsData = []): object
    {
        $structure = new class($content) {
            /**
             * @param array<string, mixed> $content
             */
            public function __construct(private readonly array $content)
            {
            }

            /**
             * @return array<string, mixed>
             */
            public function toArray(): array
            {
                return $this->content;
            }
        };

        return new class($id, $locale, $structureType, $structure, $extensionsData) {
            /**
             * @param array<string, mixed> $extensionsData
             */
            public function __construct(
                private readonly string $id,
                private readonly string $locale,
                private readonly string $structureType,
                private readonly object $structure,
                private readonly array $extensionsData,
            ) {
            }

            public function getUuid(): string
            {
                return $this->id;
            }

            public function getLocale(): string
            {
                return $this->locale;
            }

            public function getStructureType(): string
            {
                return $this->structureType;
            }

            public function getStructure(): object
            {
                return $this->structure;
            }

            /**
             * @return array<string, mixed>
             */
            public function getExtensionsData(): array
            {
                return $this->extensionsData;
            }
        };
    }

    /**
     * @param array<string, mixed> $content
     */
    private function createEditableDocumentStub(string $id, string $locale, string $structureType, array $content): object
    {
        $structure = new class($content) {
            public ?array $boundContent = null;
            public ?bool $boundClearMissing = null;

            /**
             * @param array<string, mixed> $content
             */
            public function __construct(private readonly array $content)
            {
            }

            /**
             * @return array<string, mixed>
             */
            public function toArray(): array
            {
                return $this->content;
            }

            /**
             * @param array<string, mixed> $content
             */
            public function bind(array $content, bool $clearMissingContent): void
            {
                $this->boundContent = $content;
                $this->boundClearMissing = $clearMissingContent;
            }
        };

        return new class($id, $locale, $structureType, $structure) {
            public ?string $title = null;
            /** @var array<string, mixed> */
            public array $extensionsData = [];

            public function __construct(
                private readonly string $id,
                private readonly string $locale,
                private readonly string $structureType,
                private readonly object $structure,
            ) {
            }

            public function getUuid(): string
            {
                return $this->id;
            }

            public function getLocale(): string
            {
                return $this->locale;
            }

            public function getStructureType(): string
            {
                return $this->structureType;
            }

            public function getStructure(): object
            {
                return $this->structure;
            }

            public function setTitle(string $title): void
            {
                $this->title = $title;
            }

            /**
             * @return array<string, mixed>
             */
            public function getExtensionsData(): array
            {
                return $this->extensionsData;
            }

            /**
             * @param array<string, mixed> $extensionsData
             */
            public function setExtensionsData(array $extensionsData): void
            {
                $this->extensionsData = $extensionsData;
            }
        };
    }
}
