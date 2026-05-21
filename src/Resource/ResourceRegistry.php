<?php

declare(strict_types=1);

namespace SuluContentImportExportBundle\Resource;

final class ResourceRegistry
{
    /** @var array<string, ResourceDefinition> */
    private array $definitions = [];

    /**
     * @param array<string, array<string, mixed>> $resourceConfig
     */
    public function __construct(array $resourceConfig)
    {
        foreach ($resourceConfig as $name => $definition) {
            $this->definitions[$name] = new ResourceDefinition(
                $name,
                (bool) ($definition['enabled'] ?? false),
                (string) $definition['metadata_type'],
                (string) $definition['url_prefix'],
                (string) $definition['content_name'],
                (string) $definition['content_tab_label'],
                (bool) $definition['has_seo'],
                (string) $definition['save_label'],
                (string) $definition['save_success_message'],
                isset($definition['seo_success_message']) ? (string) $definition['seo_success_message'] : null,
                $definition['types'] ?? [],
            );
        }
    }

    /**
     * @return list<ResourceDefinition>
     */
    public function all(): array
    {
        return \array_values($this->definitions);
    }

    public function get(string $resource): ResourceDefinition
    {
        if (!isset($this->definitions[$resource])) {
            throw new \InvalidArgumentException(\sprintf('Unknown resource "%s".', $resource));
        }

        return $this->definitions[$resource];
    }

    public function has(string $resource): bool
    {
        return isset($this->definitions[$resource]);
    }
}
