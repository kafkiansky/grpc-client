<?php

declare(strict_types=1);

namespace Kafkiansky\GrpcClient\Tests;

use Google\Protobuf\Internal\Message;
use Grpc\UnaryCall;
use Kafkiansky\GrpcClient\StatusCode;
use Kafkiansky\GrpcClient\Tests\Stub\GrpcClient;
use PHPUnit\Framework\TestCase;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
abstract class ClientTestCase extends TestCase
{
    final protected function createRequest(): Message
    {
        $mock = $this->createMock(Message::class);
        $mock->method('serializeToJsonString')->willReturn(\json_encode(['name' => 'Request']));

        return $mock;
    }

    final protected function createResponse(): Message
    {
        $mock = $this->createMock(Message::class);
        $mock->method('serializeToJsonString')->willReturn(\json_encode(['name' => 'Response']));
        return $mock;
    }

    final protected function createClient(StatusCode $statusCode = StatusCode::OK): GrpcClient
    {
        $status = new \stdClass();
        $status->code = $statusCode->value;

        $call = $this->createMock(UnaryCall::class);
        $call->method('wait')->willReturn([
            $this->createResponse(),
            $status,
        ]);

        $mock = $this->createMock(GrpcClient::class);
        $mock->method('create')->willReturn($call);

        return $mock;
    }
}
