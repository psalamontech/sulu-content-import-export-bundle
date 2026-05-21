<?php

declare(strict_types=1);

namespace SuluContentImportExportBundle\Admin;

use Sulu\Component\DocumentManager\DocumentManagerInterface;

final class DocumentJsonManager
{
    public function __construct(
        private readonly DocumentManagerInterface $documentManager,
        private readonly DocumentExtensionsHelper $documentExtensionsHelper,
    ) {
    }

    public function loadForEditing(string $id, string $locale): object
    {
        return $this->documentManager->find($id, $locale, [
            'load_ghost_content' => false,
            'load_shadow_content' => false,
        ]);
    }

    /**
     * @param array<string, mixed> $content
     */
    public function saveContent(object $document, string $locale, array $content): void
    {
        $document->getStructure()->bind($content, false);

        if (isset($content['title']) && \is_string($content['title']) && \method_exists($document, 'setTitle')) {
            $document->setTitle($content['title']);
        }

        $this->persist($document, $locale);
    }

    /**
     * @param array<string, mixed> $seoContent
     */
    public function saveSeo(object $document, string $locale, array $seoContent): void
    {
        $this->documentExtensionsHelper->applySeo($document, $seoContent);
        $this->persist($document, $locale);
    }

    private function persist(object $document, string $locale): void
    {
        $this->documentManager->persist($document, $locale, [
            'clear_missing_content' => false,
        ]);
        $this->documentManager->flush();
    }
}
