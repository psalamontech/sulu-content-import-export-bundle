<?php

declare(strict_types=1);

namespace SuluContentImportExportBundle\Tests\Admin;

use PHPUnit\Framework\TestCase;
use SuluContentImportExportBundle\Admin\DocumentExtensionsHelper;

final class DocumentExtensionsHelperTest extends TestCase
{
    public function testExtractSeoReturnsNormalizedSeoArray(): void
    {
        $document = new class() {
            public function getExtensionsData(): \ArrayObject
            {
                return new \ArrayObject(['seo' => ['title' => 'SEO title']]);
            }
        };

        $helper = new DocumentExtensionsHelper();

        self::assertSame(['title' => 'SEO title'], $helper->extractSeo($document));
    }

    public function testApplySeoReplacesSeoInsideExtensionsData(): void
    {
        $document = new class() {
            public array $extensionsData = ['foo' => 'bar'];

            public function getExtensionsData(): array
            {
                return $this->extensionsData;
            }

            public function setExtensionsData(array $extensionsData): void
            {
                $this->extensionsData = $extensionsData;
            }
        };

        $helper = new DocumentExtensionsHelper();
        $helper->applySeo($document, ['description' => 'SEO description']);

        self::assertSame(['foo' => 'bar', 'seo' => ['description' => 'SEO description']], $document->extensionsData);
    }
}
