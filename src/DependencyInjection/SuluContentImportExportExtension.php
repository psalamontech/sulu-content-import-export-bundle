<?php

declare(strict_types=1);

namespace SuluContentImportExportBundle\DependencyInjection;

use Sulu\Bundle\ArticleBundle\Admin\ArticleAdmin;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

final class SuluContentImportExportExtension extends Extension implements PrependExtensionInterface
{
    private const ENABLED_ENV_VAR = 'SULU_CONTENT_IMPORT_EXPORT_ENABLED';

    public function prepend(ContainerBuilder $container): void
    {
        if (!$this->isEnabledByEnvironment()) {
            return;
        }

        $container->prependExtensionConfig('framework', [
            'csrf_protection' => [
                'stateless_token_ids' => ['sulu_content_import_export'],
                'check_header' => true,
            ],
        ]);
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);
        $enabled = $this->isEnabled($config);

        $container->setParameter('sulu_content_import_export.enabled', $enabled);
        $container->setParameter('sulu_content_import_export.default_locale', $container->getParameter('kernel.default_locale'));
        $container->setParameter('sulu_content_import_export.csrf_token_id', $config['csrf_token_id']);
        $container->setParameter('sulu_content_import_export.resources', $this->buildResourceConfig($config, $enabled));

        if (!$enabled) {
            return;
        }

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../../config'));
        $loader->load('services.yaml');
    }

    /**
     * @param array{
     *     enabled: bool,
     *     csrf_token_id: string,
     *     resources: array{
     *         page: array{enabled: bool},
     *         snippet: array{enabled: bool},
     *         article: array{enabled: bool, types: list<string>}
     *     }
     * } $config
     *
     * @return array<string, array<string, mixed>>
     */
    private function buildResourceConfig(array $config, bool $enabled): array
    {
        $resources = [
            'page' => [
                'enabled' => $enabled && $config['resources']['page']['enabled'],
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
                'enabled' => $enabled && $config['resources']['snippet']['enabled'],
                'metadata_type' => 'snippet',
                'url_prefix' => 'content-json/snippet',
                'content_name' => 'Snippet',
                'content_tab_label' => 'Snippet',
                'has_seo' => false,
                'save_label' => 'Save',
                'save_success_message' => 'Saved',
                'seo_success_message' => null,
            ],
        ];

        if (\class_exists(ArticleAdmin::class)) {
            $resources['article'] = [
                'enabled' => $enabled && $config['resources']['article']['enabled'],
                'metadata_type' => 'article',
                'url_prefix' => 'content-json/article',
                'content_name' => 'Article',
                'content_tab_label' => 'Article',
                'has_seo' => true,
                'save_label' => 'Save as Draft',
                'save_success_message' => 'Saved as draft',
                'seo_success_message' => 'SEO saved as draft',
                'types' => $config['resources']['article']['types'],
            ];
        }

        return $resources;
    }

    /**
     * @param array{enabled: bool} $config
     */
    private function isEnabled(array $config): bool
    {
        return $config['enabled'] && $this->isEnabledByEnvironment();
    }

    private function isEnabledByEnvironment(): bool
    {
        $rawValue = $_ENV[self::ENABLED_ENV_VAR] ?? $_SERVER[self::ENABLED_ENV_VAR] ?? null;
        if (null === $rawValue || '' === $rawValue) {
            return true;
        }

        $enabled = \filter_var($rawValue, \FILTER_VALIDATE_BOOL, \FILTER_NULL_ON_FAILURE);

        return null === $enabled ? true : $enabled;
    }
}
