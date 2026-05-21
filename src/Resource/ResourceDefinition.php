<?php

declare(strict_types=1);

namespace SuluContentImportExportBundle\Resource;

final class ResourceDefinition
{
    /**
     * @param array<string> $articleTypes
     */
    public function __construct(
        private readonly string $name,
        private readonly bool $enabled,
        private readonly string $metadataType,
        private readonly string $urlPrefix,
        private readonly string $contentName,
        private readonly string $contentTabLabel,
        private readonly bool $hasSeo,
        private readonly string $saveLabel,
        private readonly string $saveSuccessMessage,
        private readonly ?string $seoSuccessMessage,
        private readonly array $articleTypes = [],
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function getMetadataType(): string
    {
        return $this->metadataType;
    }

    public function getUrlPrefix(): string
    {
        return $this->urlPrefix;
    }

    public function getContentName(): string
    {
        return $this->contentName;
    }

    public function getContentTabLabel(): string
    {
        return $this->contentTabLabel;
    }

    public function hasSeo(): bool
    {
        return $this->hasSeo;
    }

    public function getSaveLabel(): string
    {
        return $this->saveLabel;
    }

    public function getSaveSuccessMessage(): string
    {
        return $this->saveSuccessMessage;
    }

    public function getSeoSuccessMessage(): ?string
    {
        return $this->seoSuccessMessage;
    }

    /**
     * @return array<string>
     */
    public function getArticleTypes(): array
    {
        return $this->articleTypes;
    }
}
