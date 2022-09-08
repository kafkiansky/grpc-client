<?php

declare(strict_types=1);

namespace Kafkiansky\GrpcClient\Tests;

use Kafkiansky\GrpcClient\Client;
use Kafkiansky\GrpcClient\StatusCode;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class SimpleRequestTest extends ClientTestCase
{
    public function testRequest(): void
    {
        $client = new Client(
            $this->createClient(),
        );

        $result = $client->invoke('create', $this->createRequest());
        self::assertEquals(StatusCode::OK, $result->statusCode);
        self::assertEquals('{"name":"Response"}', $result->response?->serializeToJsonString());
    }
}
