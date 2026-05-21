<?php

declare(strict_types=1);

namespace SuluContentImportExportBundle\Tests\Security;

use PHPUnit\Framework\TestCase;
use SuluContentImportExportBundle\Security\PermissionCheckerInterface;
use SuluContentImportExportBundle\Security\PermissionCheckerRegistry;

final class PermissionCheckerRegistryTest extends TestCase
{
    public function testReturnsCheckerForResource(): void
    {
        $checker = $this->createMock(PermissionCheckerInterface::class);
        $registry = new PermissionCheckerRegistry([
            'page' => $checker,
        ]);

        self::assertSame($checker, $registry->get('page'));
    }

    public function testThrowsForUnknownResource(): void
    {
        $registry = new PermissionCheckerRegistry([]);

        $this->expectException(\InvalidArgumentException::class);
        $registry->get('missing');
    }
}
