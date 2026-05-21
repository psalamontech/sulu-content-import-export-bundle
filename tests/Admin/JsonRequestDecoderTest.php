<?php

declare(strict_types=1);

namespace SuluContentImportExportBundle\Tests\Admin;

use PHPUnit\Framework\TestCase;
use SuluContentImportExportBundle\Admin\JsonRequestDecoder;
use Symfony\Component\HttpFoundation\Request;

final class JsonRequestDecoderTest extends TestCase
{
    public function testDecodeReturnsParsedArray(): void
    {
        $decoder = new JsonRequestDecoder();
        $request = Request::create('/', 'POST', [], [], [], [], '{"content":{"title":"Hello"}}');

        self::assertSame(['content' => ['title' => 'Hello']], $decoder->decode($request));
    }

    public function testDecodeThrowsDetailedMessageForInvalidJson(): void
    {
        $decoder = new JsonRequestDecoder();
        $request = Request::create('/', 'POST', [], [], [], [], '{"content":');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid JSON:');

        $decoder->decode($request, true);
    }

    public function testRequireStructurePayloadReturnsStructureTypeAndContent(): void
    {
        $decoder = new JsonRequestDecoder();

        self::assertSame(
            [
                'structureType' => 'default',
                'content' => ['title' => 'Hello'],
            ],
            $decoder->requireStructurePayload([
                'structureType' => 'default',
                'content' => ['title' => 'Hello'],
            ])
        );
    }

    public function testRequireArrayFieldRejectsMissingField(): void
    {
        $decoder = new JsonRequestDecoder();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing or invalid "seo" key');

        $decoder->requireArrayField([], 'seo');
    }

    public function testRequireStringFieldRejectsMissingField(): void
    {
        $decoder = new JsonRequestDecoder();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing or invalid "_token" key');

        $decoder->requireStringField([], '_token');
    }
}
