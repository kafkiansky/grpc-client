<?php

declare(strict_types=1);

namespace Kafkiansky\GrpcClient\Tests;

use Kafkiansky\GrpcClient\Client;
use Kafkiansky\GrpcClient\Logging;
use Kafkiansky\GrpcClient\Retries;
use Kafkiansky\GrpcClient\RetriesAttemptsExceeded;
use Kafkiansky\GrpcClient\StatusCode;
use Kafkiansky\GrpcClient\Tests\Stub\ArrayLogger;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class RetriesTest extends ClientTestCase
{
    public function testRequestRetried(): void
    {
        $client = new Client($this->createClient(StatusCode::CANCELLED));

        $logger = new ArrayLogger();

        $client
            ->withMiddleware(new Retries(2))
            ->withMiddleware(new Logging($logger, measureFn: fn(): float => 1000))
        ;

        $msg = $this->createRequest();
        try {
            $result = $client->invoke('create', $msg);
            self::assertTrue($result->statusCode === StatusCode::OK);
        } catch (RetriesAttemptsExceeded) {}

        $logs = $logger->flush()['error'];
        self::assertCount(2, $logs);
        self::assertEquals([
            [
                'The request "{method}" with data "{request}" and context "{context}" was terminated with code "{code}".',
                [
                    'method' => $msg::class,
                    'request' => [
                        'name' => 'Request',
                    ],
                    'context' => [
                        'options' => [
                            'client-name' => 'kafkiansky/grpc-client',
                            'client-version' => '1.0.0.0',
                        ],
                        'metadata' => [],
                        'context' => [
                            'grpc.elapsed' => 0.0,
                        ],
                    ],
                    'code' => StatusCode::CANCELLED->name,
                ],
            ],
            [
                'The request "{method}" with data "{request}" and context "{context}" was terminated with code "{code}".',
                [
                    'method' => $msg::class,
                    'request' => [
                        'name' => 'Request',
                    ],
                    'context' => [
                        'options' => [
                            'client-name' => 'kafkiansky/grpc-client',
                            'client-version' => '1.0.0.0',
                        ],
                        'metadata' => [],
                        'context' => [
                            'grpc.elapsed' => 0.0,
                        ],
                    ],
                    'code' => StatusCode::CANCELLED->name,
                ],
            ],
        ], $logs);
    }
}
