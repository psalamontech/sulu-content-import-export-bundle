<?php

declare(strict_types=1);

namespace SuluContentImportExportBundle\Admin;

final class DocumentExtensionsHelper
{
    /**
     * @return array<string, mixed>
     */
    public function extractSeo(object $document): array
    {
        try {
            $extensions = $this->normalizeExtensionsData($document);
        } catch (\Throwable) {
            return [];
        }

        $seo = $extensions['seo'] ?? [];

        return \is_array($seo) ? $seo : [];
    }

    /**
     * @param array<string, mixed> $seoContent
     */
    public function applySeo(object $document, array $seoContent): void
    {
        $extensions = $this->normalizeExtensionsData($document);
        $extensions['seo'] = $seoContent;

        $document->setExtensionsData($extensions);
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeExtensionsData(object $document): array
    {
        $extensionsRaw = $document->getExtensionsData();

        return match (true) {
            \is_array($extensionsRaw) => $extensionsRaw,
            \is_object($extensionsRaw) && \method_exists($extensionsRaw, 'toArray') => $extensionsRaw->toArray(),
            $extensionsRaw instanceof \Traversable => \iterator_to_array($extensionsRaw),
            default => [],
        };
    }
}
