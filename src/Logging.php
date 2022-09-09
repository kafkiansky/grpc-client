<?php

declare(strict_types=1);

namespace Kafkiansky\GrpcClient;

use Google\Protobuf\Internal\Message;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

/**
 * @psalm-type Level = LogLevel::*
 * @template-implements Middleware<Message, Message>
 */
final class Logging implements Middleware
{
    /**
     * @var callable(bool=):(float|string)
     */
    private $measureFn;

    /**
     * @psalm-param Level $successRequestLog
     * @psalm-param Level $failureRequestLog
     * @psalm-param null|callable(bool=):float $measureFn
     */
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly string $successRequestLog = LogLevel::DEBUG,
        private readonly string $failureRequestLog = LogLevel::ERROR,
        ?callable $measureFn = null,
    ) {
        $this->measureFn = $measureFn ?: \microtime(...);
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke(callable $handler): callable
    {
        return function (Message $message, RequestContext $context) use ($handler): Result {
            $result = $this->measure(fn(): Result => $handler($message, $context), $context);
            $response = function(callable $log) use ($message, $context, $result): Result {
                $log($message, $context, $result);
                return $result;
            };

            return match ($result->statusCode) {
                StatusCode::OK => $response($this->onSuccess(...)),
                default => $response($this->onFailure(...)),
            };
        };
    }

    /**
     * @param callable():Result $fn
     */
    private function measure(callable $fn, RequestContext $context): Result
    {
        $measure = $this->measureFn;

        $start = (float)$measure(true);
        $result = $fn();
        $end = (float)$measure(true) - $start;

        $context->withContext(['grpc.elapsed' => \round($end, 3)]);

        return $result;
    }

    private function onSuccess(Message $message, RequestContext $context, Result $result): void
    {
        $this->logger->log($this->successRequestLog, 'The request "{method}" with data "{request}" and context "{context}" was terminated successful with response "{response}".', [
            'method' => $message::class,
            'request' => \json_decode($message->serializeToJsonString(), true),
            'response' => \json_decode($result->response?->serializeToJsonString() ?: '{}', true),
            'context' => $context->context,
        ]);
    }

    private function onFailure(Message $message, RequestContext $context, Result $result): void
    {
        $this->logger->log($this->failureRequestLog, 'The request "{method}" with data "{request}" and context "{context}" was terminated with code "{code}".', [
            'method' => $message::class,
            'request' => \json_decode($message->serializeToJsonString(), true),
            'context' => [
                'options' => $context->options,
                'metadata' => $context->metadata,
                'context' => $context->context,
            ],
            'code' => $result->statusCode->name,
        ]);
    }
}
