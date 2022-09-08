<?php

declare(strict_types=1);

namespace Kafkiansky\GrpcClient;

use Google\Protobuf\Internal\Message;

/**
 * @template T of Message
 */
final class Result
{
    /**
     * @param T|null $response
     */
    public function __construct(
        public readonly StatusCode $statusCode,
        public readonly ?Message $response = null,
    ) {
    }
}
