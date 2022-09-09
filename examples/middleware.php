<?php

declare(strict_types=1);

use Google\Protobuf\Internal\Message;
use Kafkiansky\GrpcClient\Middleware;
use Kafkiansky\GrpcClient\RequestContext;
use Kafkiansky\GrpcClient\Result;

require_once __DIR__.'/../vendor/autoload.php';

final class Create extends Message {}
final class CreateReply extends Message {
    public readonly string $name;
}

$client = new \Kafkiansky\GrpcClient\Client(
    // your generated client here
);

/**
 * @template-implements Middleware<Create, CreateReply>
 */
final class DoSome implements Middleware
{
    public function __invoke(callable $handler): callable
    {
        return function (Create $create, RequestContext $context) use ($handler): Result {
            $result = $handler($create, $context);
            echo $result->response?->name;

            return $result;
        };
    }
}

$client
    ->withMiddleware(new \Kafkiansky\GrpcClient\Retries(attempts: 10))
    ->withMiddleware(new \Kafkiansky\GrpcClient\Logging(new \Psr\Log\NullLogger()))
    ->withMiddleware(new DoSome())
    ->withMiddleware(function (callable $handler): callable {
        return function (Message $message, RequestContext $context) use ($handler): Result {
            // before request
            $result = $handler($message, $context);
            // after request
            return $result;
        };
    })
;

try {
    $result = $client->invoke('create', new Create());
    echo $result->statusCode->name;
} catch (\Kafkiansky\GrpcClient\ClientException $e) {
}