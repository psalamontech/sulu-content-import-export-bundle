<?php

declare(strict_types=1);

namespace SuluContentImportExportBundle\Admin;

use Symfony\Component\HttpFoundation\Request;

final class JsonRequestDecoder
{
    /**
     * @return array<string, mixed>
     */
    public function decode(Request $request, bool $includeJsonErrorMessage = false): array
    {
        $body = \json_decode($request->getContent(), true);
        if (\JSON_ERROR_NONE !== \json_last_error()) {
            $message = 'Invalid JSON';
            if ($includeJsonErrorMessage) {
                $message .= ': ' . \json_last_error_msg();
            }

            throw new \InvalidArgumentException($message);
        }

        if (!\is_array($body)) {
            throw new \InvalidArgumentException('Invalid JSON');
        }

        return $body;
    }

    /**
     * @param array<string, mixed> $body
     * @return array{structureType: string, content: array<mixed>}
     */
    public function requireStructurePayload(array $body): array
    {
        $structureType = $body['structureType'] ?? null;
        $content = $body['content'] ?? null;

        if (!\is_string($structureType) || '' === $structureType || !\is_array($content)) {
            throw new \InvalidArgumentException('Missing structureType or content');
        }

        return [
            'structureType' => $structureType,
            'content' => $content,
        ];
    }

    /**
     * @param array<string, mixed> $body
     * @return array<mixed>
     */
    public function requireArrayField(array $body, string $field): array
    {
        $value = $body[$field] ?? null;
        if (!\is_array($value)) {
            throw new \InvalidArgumentException(\sprintf('Missing or invalid "%s" key', $field));
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $body
     */
    public function requireStringField(array $body, string $field): string
    {
        $value = $body[$field] ?? null;
        if (!\is_string($value) || '' === $value) {
            throw new \InvalidArgumentException(\sprintf('Missing or invalid "%s" key', $field));
        }

        return $value;
    }
}
