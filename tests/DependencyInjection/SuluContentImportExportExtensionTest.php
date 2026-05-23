<?php

declare(strict_types=1);

namespace SuluContentImportExportBundle\Tests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use SuluContentImportExportBundle\Admin\ContentImportExportAdmin;
use SuluContentImportExportBundle\DependencyInjection\SuluContentImportExportExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class SuluContentImportExportExtensionTest extends TestCase
{
    private mixed $originalEnabledEnv;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalEnabledEnv = $_ENV['SULU_CONTENT_IMPORT_EXPORT_ENABLED'] ?? null;
    }

    protected function tearDown(): void
    {
        if (null === $this->originalEnabledEnv) {
            unset($_ENV['SULU_CONTENT_IMPORT_EXPORT_ENABLED'], $_SERVER['SULU_CONTENT_IMPORT_EXPORT_ENABLED']);
        } else {
            $_ENV['SULU_CONTENT_IMPORT_EXPORT_ENABLED'] = $this->originalEnabledEnv;
            $_SERVER['SULU_CONTENT_IMPORT_EXPORT_ENABLED'] = $this->originalEnabledEnv;
        }

        parent::tearDown();
    }

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

    public function testPrependSkipsCsrfConfigWhenDisabledByEnvironment(): void
    {
        $_ENV['SULU_CONTENT_IMPORT_EXPORT_ENABLED'] = 'false';
        $_SERVER['SULU_CONTENT_IMPORT_EXPORT_ENABLED'] = 'false';

        $container = new ContainerBuilder();
        $extension = new SuluContentImportExportExtension();

        $extension->prepend($container);

        self::assertSame([], $container->getExtensionConfig('framework'));
    }

    public function testLoadSetsExpectedParameters(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.default_locale', 'en');
        $extension = new SuluContentImportExportExtension();

        $extension->load([[]], $container);

        self::assertSame('sulu_content_import_export', $container->getParameter('sulu_content_import_export.csrf_token_id'));
        self::assertSame('en', $container->getParameter('sulu_content_import_export.default_locale'));
        self::assertTrue($container->getParameter('sulu_content_import_export.enabled'));

        $resources = $container->getParameter('sulu_content_import_export.resources');
        self::assertArrayHasKey('page', $resources);
        self::assertArrayHasKey('snippet', $resources);
        self::assertTrue($resources['page']['enabled']);
        self::assertTrue($resources['snippet']['enabled']);
        self::assertTrue($container->hasDefinition(ContentImportExportAdmin::class));
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

    public function testLoadSkipsServiceRegistrationWhenDisabledByEnvironment(): void
    {
        $_ENV['SULU_CONTENT_IMPORT_EXPORT_ENABLED'] = 'false';
        $_SERVER['SULU_CONTENT_IMPORT_EXPORT_ENABLED'] = 'false';

        $container = new ContainerBuilder();
        $container->setParameter('kernel.default_locale', 'en');
        $extension = new SuluContentImportExportExtension();

        $extension->load([[]], $container);

        self::assertFalse($container->getParameter('sulu_content_import_export.enabled'));
        self::assertFalse($container->hasDefinition(ContentImportExportAdmin::class));

        $resources = $container->getParameter('sulu_content_import_export.resources');
        self::assertFalse($resources['page']['enabled']);
        self::assertFalse($resources['snippet']['enabled']);
    }
}
