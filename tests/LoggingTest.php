<?php

declare(strict_types=1);

namespace Kafkiansky\GrpcClient\Tests;

use Kafkiansky\GrpcClient\Client;
use Kafkiansky\GrpcClient\Logging;
use Kafkiansky\GrpcClient\StatusCode;
use Kafkiansky\GrpcClient\Tests\Stub\ArrayLogger;
use Psr\Log\LogLevel;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class LoggingTest extends ClientTestCase
{
    public function testSuccessRequestLogged(): void
    {
        $client = new Client($this->createClient());

        $logger = new ArrayLogger();

        $client->withMiddleware(new Logging($logger, measureFn: fn(): float => 1000));

        $result = $client->invoke('create', $msg = $this->createRequest());
        self::assertEquals(
            [
                'The request "{method}" with data "{request}" and context "{context}" was terminated successful with response "{response}".',
                [
                    'method' => $msg::class,
                    'request' => [
                        'name' => 'Request',
                    ],
                    'response' => [
                        'name' => 'Response',
                    ],
                    'context' => [
                        'grpc.elapsed' => 0.0,
                    ]
                ]
            ],
            $logger->flush()['debug'][0]
        );
        self::assertEquals(StatusCode::OK, $result->statusCode);
        self::assertEquals('{"name":"Response"}', $result->response?->serializeToJsonString());
    }

    public function testFailureRequestLogged(): void
    {
        $client = new Client($this->createClient(StatusCode::CANCELLED));

        $logger = new ArrayLogger();

        $client->withMiddleware(new Logging($logger, failureRequestLog: LogLevel::CRITICAL, measureFn: fn(): float => 10000));

        $result = $client->invoke('create', $msg = $this->createRequest());
        self::assertEquals([
            'The request "{method}" with data "{request}" and context "{context}" was terminated with code "{code}".',
            [
                'method' => $msg::class,
                'request' => [
                    'name' => 'Request',
                ],
                'context' => [
                    'options' => [
                        'client-name' => 'kafkiansky/grpc-client',
                        'client-version' => 'dev-master',
                    ],
                    'metadata' => [],
                    'context' => [
                        'grpc.elapsed' => 0.0,
                    ]
                ],
                'code' => StatusCode::CANCELLED->name,
            ]
        ], $logger->flush()['critical'][0]);
        self::assertEquals(StatusCode::CANCELLED, $result->statusCode);
    }
}
