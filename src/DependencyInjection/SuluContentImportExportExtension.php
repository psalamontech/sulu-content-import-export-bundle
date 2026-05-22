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
    public function prepend(ContainerBuilder $container): void
    {
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

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../../config'));
        $loader->load('services.yaml');

        $container->setParameter('sulu_content_import_export.default_locale', $container->getParameter('kernel.default_locale'));
        $container->setParameter('sulu_content_import_export.csrf_token_id', $config['csrf_token_id']);
        $container->setParameter('sulu_content_import_export.resources', $this->buildResourceConfig($config));
    }

    /**
     * @param array{
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
    private function buildResourceConfig(array $config): array
    {
        $resources = [
            'page' => [
                'enabled' => $config['resources']['page']['enabled'],
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
                'enabled' => $config['resources']['snippet']['enabled'],
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
                'enabled' => $config['resources']['article']['enabled'],
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
}
