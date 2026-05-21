<?php

declare(strict_types=1);

namespace SuluContentImportExportBundle\Security;

use Sulu\Bundle\PageBundle\Admin\PageAdmin;
use Sulu\Component\Content\Document\Behavior\SecurityBehavior;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;
use Sulu\Component\Security\Authorization\SecurityCondition;

final class PagePermissionChecker implements PermissionCheckerInterface
{
    public function __construct(
        private readonly SecurityCheckerInterface $securityChecker,
    ) {
    }

    public function check(object $document, string $locale, string $permission): void
    {
        $this->securityChecker->checkPermission(
            new SecurityCondition(
                PageAdmin::getPageSecurityContext($document->getWebspaceName()),
                $locale,
                SecurityBehavior::class,
                $document->getUuid(),
            ),
            $permission,
        );
    }
}
