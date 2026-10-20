<?php

declare(strict_types=1);

namespace SuluContentImportExportBundle\Tests\Admin;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\AdminBundle\Admin\View\ViewBuilderFactory;
use Sulu\Bundle\AdminBundle\Admin\View\ViewCollection;
use Sulu\Bundle\PageBundle\Admin\PageAdmin;
use Sulu\Bundle\SnippetBundle\Admin\SnippetAdmin;
use Sulu\Component\Localization\Manager\LocalizationManagerInterface;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;
use Sulu\Component\Webspace\Manager\WebspaceCollection;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Webspace;
use SuluContentImportExportBundle\Admin\ContentImportExportAdmin;

final class ContentImportExportAdminTest extends TestCase
{
    public function testViewsAreOmittedWhenParentViewsAreUnavailable(): void
    {
        $views = $this->configureViews(false, false);

        self::assertFalse($views->has('sulu_content_import_export.page'));
        self::assertFalse($views->has('sulu_content_import_export.snippet'));
    }

    public function testSnippetViewNeedsSnippetEditPermission(): void
    {
        $views = $this->configureViews(true, false);

        self::assertTrue($views->has('sulu_content_import_export.page'));
        self::assertFalse($views->has('sulu_content_import_export.snippet'));
    }

    public function testViewsRemainAvailableWithMatchingEditPermissions(): void
    {
        $views = $this->configureViews(true, true);

        self::assertSame(PageAdmin::EDIT_FORM_VIEW, $views->get('sulu_content_import_export.page')->getView()->getParent());
        self::assertSame(SnippetAdmin::EDIT_FORM_VIEW, $views->get('sulu_content_import_export.snippet')->getView()->getParent());
    }

    private function configureViews(bool $canEditPage, bool $canEditSnippet): ViewCollection
    {
        $localizationManager = $this->createMock(LocalizationManagerInterface::class);
        $localizationManager->method('getLocales')->willReturn(['en']);

        $webspace = new Webspace();
        $webspace->setKey('website');
        $webspaceManager = $this->createMock(WebspaceManagerInterface::class);
        $webspaceManager->method('getWebspaceCollection')->willReturn(new WebspaceCollection(['website' => $webspace]));

        $securityChecker = $this->createMock(SecurityCheckerInterface::class);
        $securityChecker->method('hasPermission')->willReturnCallback(
            static function (string $context, string $permission) use ($canEditPage, $canEditSnippet): bool {
                self::assertSame(PermissionTypes::EDIT, $permission);

                return match ($context) {
                    PageAdmin::getPageSecurityContext('website') => $canEditPage,
                    SnippetAdmin::SECURITY_CONTEXT => $canEditSnippet,
                    default => false,
                };
            },
        );

        $admin = new ContentImportExportAdmin(
            new ViewBuilderFactory(),
            $localizationManager,
            $securityChecker,
            $webspaceManager,
            true,
            [
                'page' => $this->resourceConfig('page'),
                'snippet' => $this->resourceConfig('snippet'),
            ],
        );

        $views = new ViewCollection();
        $admin->configureViews($views);

        return $views;
    }

    /** @return array<string, bool|string|null> */
    private function resourceConfig(string $name): array
    {
        return [
            'enabled' => true,
            'metadata_type' => $name,
            'url_prefix' => 'content-json/' . $name,
            'content_name' => ucfirst($name),
            'content_tab_label' => ucfirst($name),
            'has_seo' => false,
            'save_label' => 'Save',
            'save_success_message' => 'Saved',
            'seo_success_message' => null,
        ];
    }
}
