<?php

declare(strict_types=1);

namespace SuluContentImportExportBundle\Security;

use Sulu\Bundle\ArticleBundle\Admin\ArticleAdmin;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;
use Sulu\Component\Security\Authorization\SecurityCondition;

final class ArticlePermissionChecker implements PermissionCheckerInterface
{
    public function __construct(
        private readonly SecurityCheckerInterface $securityChecker,
    ) {
    }

    public function check(object $document, string $locale, string $permission): void
    {
        unset($document, $locale);

        $this->securityChecker->checkPermission(
            new SecurityCondition(ArticleAdmin::SECURITY_CONTEXT),
            $permission,
        );
    }
}
