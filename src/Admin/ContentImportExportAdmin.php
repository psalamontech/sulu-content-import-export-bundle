<?php

declare(strict_types=1);

namespace SuluContentImportExportBundle\Admin;

use Sulu\Bundle\AdminBundle\Admin\Admin;
use Sulu\Bundle\AdminBundle\Admin\View\ViewBuilderFactoryInterface;
use Sulu\Bundle\AdminBundle\Admin\View\ViewCollection;
use Sulu\Bundle\ArticleBundle\Admin\ArticleAdmin;
use Sulu\Bundle\PageBundle\Admin\PageAdmin;
use Sulu\Bundle\SnippetBundle\Admin\SnippetAdmin;
use Sulu\Component\Localization\Manager\LocalizationManagerInterface;
use SuluContentImportExportBundle\Resource\ResourceRegistry;

final class ContentImportExportAdmin extends Admin
{
    /**
     * @param array<string, array<string, mixed>> $resourceConfig
     */
    public function __construct(
        private readonly ViewBuilderFactoryInterface $viewBuilderFactory,
        private readonly LocalizationManagerInterface $localizationManager,
        array $resourceConfig,
    ) {
        $this->resourceRegistry = new ResourceRegistry($resourceConfig);
    }

    private readonly ResourceRegistry $resourceRegistry;

    public function configureViews(ViewCollection $viewCollection): void
    {
        $locales = $this->normalizeLocales($this->localizationManager->getLocales());

        foreach ($this->resourceRegistry->all() as $definition) {
            if (!$definition->isEnabled()) {
                continue;
            }

            if ('page' === $definition->getName()) {
                $viewCollection->add($this->buildView($definition->getName(), PageAdmin::EDIT_FORM_VIEW, $definition, $locales));
                continue;
            }

            if ('snippet' === $definition->getName()) {
                $viewCollection->add($this->buildView($definition->getName(), SnippetAdmin::EDIT_FORM_VIEW, $definition, $locales));
                continue;
            }

            if ('article' === $definition->getName() && \class_exists(ArticleAdmin::class)) {
                foreach ($definition->getArticleTypes() as $type) {
                    $viewCollection->add($this->buildView($definition->getName() . '_' . $type, ArticleAdmin::EDIT_FORM_VIEW . '_' . $type, $definition, $locales));
                }
            }
        }
    }

    /**
     * @param array<string> $locales
     */
    private function buildView(string $viewSuffix, string $parentView, \SuluContentImportExportBundle\Resource\ResourceDefinition $definition, array $locales)
    {
        return $this->viewBuilderFactory
            ->createViewBuilder('sulu_content_import_export.' . $viewSuffix, '/export-import', 'sulu_content_import_export')
            ->setOption('tabTitle', 'Export / Import')
            ->setOption('tabOrder', 9999)
            ->setOption('urlPrefix', $definition->getUrlPrefix())
            ->setOption('contentName', $definition->getContentName())
            ->setOption('contentTabLabel', $definition->getContentTabLabel())
            ->setOption('hasSeo', $definition->hasSeo())
            ->setOption('locales', $locales)
            ->setOption('saveLabel', $definition->getSaveLabel())
            ->setOption('saveSuccessMessage', $definition->getSaveSuccessMessage())
            ->setOption('seoSuccessMessage', $definition->getSeoSuccessMessage())
            ->setParent($parentView);
    }

    /**
     * @param array<mixed> $locales
     * @return array<string>
     */
    private function normalizeLocales(array $locales): array
    {
        return \array_values(\array_filter(\array_map(static function(mixed $locale): ?string {
            if (\is_string($locale) && '' !== $locale) {
                return $locale;
            }

            if (\is_object($locale) && \method_exists($locale, 'getLocale')) {
                $resolvedLocale = $locale->getLocale();

                return \is_string($resolvedLocale) && '' !== $resolvedLocale ? $resolvedLocale : null;
            }

            return null;
        }, $locales)));
    }
}
