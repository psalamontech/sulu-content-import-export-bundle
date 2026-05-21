<?php

declare(strict_types=1);

namespace SuluContentImportExportBundle\Security;

interface PermissionCheckerInterface
{
    public function check(object $document, string $locale, string $permission): void;
}
