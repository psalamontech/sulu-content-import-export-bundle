<?php

declare(strict_types=1);

namespace SuluContentImportExportBundle\Tests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use SuluContentImportExportBundle\DependencyInjection\SuluContentImportExportExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class SuluContentImportExportExtensionTest extends TestCase
{
    public function testPrependRegistersStatelessCsrfConfig(): void
    {
        $container = new ContainerBuilder();
        $extension = new SuluContentImportExportExtension();

        $extension->prepend($container);

        $frameworkConfigs = $container->getExtensionConfig('framework');

        self::assertNotEmpty($frameworkConfigs);

        $csrfConfig = null;
        foreach ($frameworkConfigs as $config) {
            if (isset($config['csrf_protection'])) {
                $csrfConfig = $config['csrf_protection'];
                break;
            }
        }

        self::assertNotNull($csrfConfig, 'No csrf_protection key found in prepended framework config');
        self::assertContains('sulu_content_import_export', $csrfConfig['stateless_token_ids']);
        self::assertTrue($csrfConfig['check_header']);
    }

    public function testLoadSetsExpectedParameters(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.default_locale', 'en');
        $extension = new SuluContentImportExportExtension();

        $extension->load([[]], $container);

        self::assertSame('sulu_content_import_export', $container->getParameter('sulu_content_import_export.csrf_token_id'));
        self::assertSame('en', $container->getParameter('sulu_content_import_export.default_locale'));

        $resources = $container->getParameter('sulu_content_import_export.resources');
        self::assertArrayHasKey('page', $resources);
        self::assertArrayHasKey('snippet', $resources);
        self::assertTrue($resources['page']['enabled']);
        self::assertTrue($resources['snippet']['enabled']);
    }

    public function testLoadRespectsDisabledResource(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.default_locale', 'en');
        $extension = new SuluContentImportExportExtension();

        $extension->load([['resources' => ['page' => ['enabled' => false]]]], $container);

        $resources = $container->getParameter('sulu_content_import_export.resources');
        self::assertFalse($resources['page']['enabled']);
        self::assertTrue($resources['snippet']['enabled']);
    }
}
