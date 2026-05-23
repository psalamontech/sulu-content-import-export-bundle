<?php

declare(strict_types=1);

namespace SuluContentImportExportBundle\Tests\Admin;

use PHPUnit\Framework\TestCase;
use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactoryInterface;
use Sulu\Component\Content\Metadata\PropertyMetadata;
use Sulu\Component\Content\Metadata\StructureMetadata;
use SuluContentImportExportBundle\Admin\StructureJsonValidator;

final class StructureJsonValidatorTest extends TestCase
{
    public function testValidateReturnsErrorsForRequiredAndNestedBlockFields(): void
    {
        $requiredTitle = $this->createConfiguredMock(PropertyMetadata::class, [
            'getName' => 'title',
            'getType' => 'text_line',
            'isRequired' => true,
        ]);

        $optionalColor = $this->createConfiguredMock(PropertyMetadata::class, [
            'getName' => 'color',
            'getType' => 'color',
            'isRequired' => false,
        ]);

        $nestedChild = $this->createConfiguredMock(PropertyMetadata::class, [
            'getName' => 'ctaLabel',
            'getType' => 'text_line',
            'isRequired' => true,
        ]);

        $component = new class([$nestedChild]) {
            /**
             * @param array<int, mixed> $children
             */
            public function __construct(
                private readonly array $children,
            ) {
            }

            /**
             * @return array<int, mixed>
             */
            public function getChildren(): array
            {
                return $this->children;
            }
        };

        $block = $this->createConfiguredMock(PropertyMetadata::class, [
            'getName' => 'sections',
            'getType' => 'block',
            'getMinOccurs' => 1,
            'isRequired' => false,
        ]);
        $block->method('getComponentByName')->with('cta')->willReturn($component);

        $metadata = $this->createMock(StructureMetadata::class);
        $metadata->method('getProperties')->willReturn([$requiredTitle, $optionalColor, $block]);

        $factory = $this->createMock(StructureMetadataFactoryInterface::class);
        $factory
            ->expects(self::once())
            ->method('getStructureMetadata')
            ->with('page', 'landing_page')
            ->willReturn($metadata);

        $validator = new StructureJsonValidator($factory);
        $issues = $validator->validate('page', 'landing_page', [
            'title' => '',
            'color' => '',
            'sections' => [
                ['type' => 'cta', 'ctaLabel' => ''],
            ],
        ]);

        self::assertSame([
            ['level' => 'error', 'path' => 'title', 'message' => "Required field 'title' is empty"],
            ['level' => 'info', 'path' => 'color', 'message' => "Optional field 'color' is empty"],
            ['level' => 'error', 'path' => 'sections[0:cta].ctaLabel', 'message' => "Required field 'ctaLabel' is empty"],
        ], $issues);
    }

    public function testValidateReturnsWarningWhenMetadataDoesNotExist(): void
    {
        $factory = $this->createMock(StructureMetadataFactoryInterface::class);
        $factory
            ->expects(self::once())
            ->method('getStructureMetadata')
            ->willThrowException(new \RuntimeException('missing'));

        $validator = new StructureJsonValidator($factory);

        self::assertSame([
            ['level' => 'warning', 'path' => '', 'message' => "Template 'missing-template' metadata not found"],
        ], $validator->validate('snippet', 'missing-template', []));
    }
}
