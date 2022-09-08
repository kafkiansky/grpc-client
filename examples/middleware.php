<?php

declare(strict_types=1);

require_once __DIR__.'/../vendor/autoload.php';

final class Create extends \Google\Protobuf\Internal\Message {}

$client = new \Kafkiansky\GrpcClient\Client(
    // your generated client here
);

$client
    ->withMiddleware(new \Kafkiansky\GrpcClient\Retries(attempts: 10))
    ->withMiddleware(new \Kafkiansky\GrpcClient\Logging(new \Psr\Log\NullLogger()))
    ->withMiddleware(function (callable $handler): callable {
        return function (\Google\Protobuf\Internal\Message $message, \Kafkiansky\GrpcClient\RequestContext $context) use ($handler): \Kafkiansky\GrpcClient\Result {
            // before request
            $result = $handler($message, $context);
            // after request
            return $result;
        };
    })
;

$result = $client->invoke('create', new Create());