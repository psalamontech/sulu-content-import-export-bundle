<?php

declare(strict_types=1);

namespace SuluContentImportExportBundle\Admin;

use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactoryInterface;
use Sulu\Component\Content\Metadata\PropertyMetadata;

final class StructureJsonValidator
{
    public function __construct(
        private readonly StructureMetadataFactoryInterface $structureMetadataFactory,
    ) {
    }

    /**
     * @param array<string, mixed> $content
     * @return list<array{level: string, path: string, message: string}>
     */
    public function validate(string $metadataType, string $structureType, array $content): array
    {
        try {
            $metadata = $this->structureMetadataFactory->getStructureMetadata($metadataType, $structureType);
        } catch (\Throwable) {
            return [[
                'level' => 'warning',
                'path' => '',
                'message' => \sprintf("Template '%s' metadata not found", $structureType),
            ]];
        }

        $issues = [];
        foreach ($metadata->getProperties() as $property) {
            $value = $content[$property->getName()] ?? null;
            $this->validateProperty($property, $value, $property->getName(), $issues);
        }

        return $issues;
    }

    /**
     * @param list<array{level: string, path: string, message: string}> $issues
     */
    private function validateProperty(PropertyMetadata $property, mixed $value, string $path, array &$issues): void
    {
        $type = $property->getType();
        $name = $property->getName();

        if ('block' === $type) {
            $minOccurs = $property->getMinOccurs() ?? 0;
            $items = \is_array($value) ? $value : [];
            $count = \count($items);

            if ($minOccurs > 0 && $count < $minOccurs) {
                $issues[] = [
                    'level' => 'error',
                    'path' => $path,
                    'message' => \sprintf('Block requires at least %d item(s), has %d', $minOccurs, $count),
                ];
            }

            foreach ($items as $index => $item) {
                if (!\is_array($item)) {
                    continue;
                }

                $itemType = $item['type'] ?? null;
                $component = \is_string($itemType) ? $property->getComponentByName($itemType) : null;
                if (!$component) {
                    continue;
                }

                foreach ($component->getChildren() as $child) {
                    if (!$child instanceof PropertyMetadata) {
                        continue;
                    }

                    $childValue = $item[$child->getName()] ?? null;
                    $this->validateProperty($child, $childValue, \sprintf('%s[%d:%s].%s', $path, $index, $itemType, $child->getName()), $issues);
                }
            }

            return;
        }

        if (\in_array($type, ['resource_locator', 'checkbox', 'smart_content'], true)) {
            return;
        }

        $empty = $this->isEmpty($type, $value);

        if ($property->isRequired() && $empty) {
            $issues[] = ['level' => 'error', 'path' => $path, 'message' => \sprintf("Required field '%s' is empty", $name)];
        } elseif (!$property->isRequired() && $empty) {
            $issues[] = ['level' => 'info', 'path' => $path, 'message' => \sprintf("Optional field '%s' is empty", $name)];
        }
    }

    private function isEmpty(string $type, mixed $value): bool
    {
        if (null === $value) {
            return true;
        }

        return match ($type) {
            'text_line', 'text_area', 'url', 'single_select', 'color' => '' === \trim((string) $value),
            'text_editor' => '' === \trim(\strip_tags((string) $value)),
            'media_selection', 'article_selection' => [] === ($value['ids'] ?? []),
            'single_snippet_selection' => empty($value['id'] ?? null),
            default => false,
        };
    }
}
