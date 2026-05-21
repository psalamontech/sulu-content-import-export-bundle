<?php

declare(strict_types=1);

namespace SuluContentImportExportBundle\Tests\Admin;

use PHPUnit\Framework\TestCase;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use SuluContentImportExportBundle\Admin\DocumentExtensionsHelper;
use SuluContentImportExportBundle\Admin\DocumentJsonManager;

final class DocumentJsonManagerTest extends TestCase
{
    public function testLoadForEditingUsesGhostAndShadowOptions(): void
    {
        $document = new \stdClass();
        $documentManager = $this->createMock(DocumentManagerInterface::class);
        $documentManager
            ->expects(self::once())
            ->method('find')
            ->with('123', 'en', [
                'load_ghost_content' => false,
                'load_shadow_content' => false,
            ])
            ->willReturn($document);

        $manager = new DocumentJsonManager($documentManager, new DocumentExtensionsHelper());

        self::assertSame($document, $manager->loadForEditing('123', 'en'));
    }

    public function testSaveContentBindsStructureUpdatesTitleAndPersists(): void
    {
        $structure = new class() {
            public ?array $boundContent = null;
            public ?bool $boundClearMissing = null;

            public function bind(array $content, bool $clearMissingContent): void
            {
                $this->boundContent = $content;
                $this->boundClearMissing = $clearMissingContent;
            }
        };

        $document = new class($structure) {
            public ?string $title = null;

            public function __construct(private readonly object $structure)
            {
            }

            public function getStructure(): object
            {
                return $this->structure;
            }

            public function setTitle(string $title): void
            {
                $this->title = $title;
            }
        };

        $documentManager = $this->createMock(DocumentManagerInterface::class);
        $documentManager
            ->expects(self::once())
            ->method('persist')
            ->with($document, 'en', ['clear_missing_content' => false]);
        $documentManager->expects(self::once())->method('flush');

        $manager = new DocumentJsonManager($documentManager, new DocumentExtensionsHelper());
        $manager->saveContent($document, 'en', ['title' => 'Homepage']);

        self::assertSame(['title' => 'Homepage'], $structure->boundContent);
        self::assertFalse($structure->boundClearMissing);
        self::assertSame('Homepage', $document->title);
    }

    public function testSaveSeoAppliesSeoAndPersistsDocument(): void
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

        $documentManager = $this->createMock(DocumentManagerInterface::class);
        $documentManager
            ->expects(self::once())
            ->method('persist')
            ->with($document, 'en', ['clear_missing_content' => false]);
        $documentManager->expects(self::once())->method('flush');

        $manager = new DocumentJsonManager($documentManager, new DocumentExtensionsHelper());
        $manager->saveSeo($document, 'en', ['title' => 'SEO']);

        self::assertSame(['foo' => 'bar', 'seo' => ['title' => 'SEO']], $document->extensionsData);
    }
}
