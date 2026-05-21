<?php

declare(strict_types=1);

namespace SuluContentImportExportBundle\Security;

final class PermissionCheckerRegistry
{
    /**
     * @param iterable<string, PermissionCheckerInterface> $checkers
     */
    public function __construct(
        private readonly iterable $checkers,
    ) {
    }

    public function get(string $resource): PermissionCheckerInterface
    {
        foreach ($this->checkers as $name => $checker) {
            if ($name === $resource) {
                return $checker;
            }
        }

        throw new \InvalidArgumentException(\sprintf('No permission checker configured for resource "%s".', $resource));
    }
}
