<?php

declare(strict_types=1);

namespace Kafkiansky\GrpcClient;

use Google\Protobuf\Internal\Message;

/**
 * @template T of Message
 * @template E of Message
 *
 * @psalm-type MiddlewareFn = callable(Message, RequestContext): Result<Message>
 */
interface Middleware
{
    /**
     * @psalm-param callable(T, RequestContext): Result<E> $handler
     *
     * @psalm-return callable(T, RequestContext): Result<E>
     */
    public function __invoke(callable $handler): callable;
}
