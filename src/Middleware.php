<?php

declare(strict_types=1);

namespace Kafkiansky\GrpcClient;

use Google\Protobuf\Internal\Message;

/**
 * @psalm-type MiddlewareFn = callable(Message, RequestContext): Result<Message>
 */
interface Middleware
{
    /**
     * @psalm-param MiddlewareFn $handler
     *
     * @psalm-return MiddlewareFn
     */
    public function __invoke(callable $handler): callable;
}
