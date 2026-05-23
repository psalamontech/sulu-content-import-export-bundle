<?php

declare(strict_types=1);

namespace SuluContentImportExportBundle\Tests;

use PHPUnit\Framework\TestCase;
use SuluContentImportExportBundle\SuluContentImportExportBundle;

final class SuluContentImportExportBundleTest extends TestCase
{
    public function testBundlePathPointsToPackageRootAndContainsAdminRoutes(): void
    {
        $bundle = new SuluContentImportExportBundle();
        $path = $bundle->getPath();

        self::assertStringEndsWith('sulu-content-import-export-bundle', $path);
        self::assertFileExists($path . '/config/routes_admin.yaml');
        self::assertFileExists($path . '/Resources/translations/admin.en.json');
        self::assertFileExists($path . '/Resources/translations/admin.de.json');
    }
}
