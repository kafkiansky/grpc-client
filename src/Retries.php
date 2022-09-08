<?php

declare(strict_types=1);

namespace Kafkiansky\GrpcClient;

use Carbon\CarbonInterval;
use Google\Protobuf\Internal\Message;

final class Retries implements Middleware
{
    /**
     * @param positive-int $attempts
     * @param positive-int|0 $delay In milliseconds.
     */
    public function __construct(
        private readonly int $attempts = 1,
        private readonly int $delay = 100,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke(callable $handler): callable
    {
        return function (Message $message, RequestContext $context) use ($handler): Result {
            /** @var positive-int|0 $retryTimeout */
            $retryTimeout = (int)CarbonInterval::milliseconds($this->delay)->totalMicroseconds;

            $attempts = 0;
            do {
                $result = $handler($message, $context);
                if ($result->statusCode === StatusCode::OK) {
                    return $result;
                }

                \usleep($retryTimeout);
            } while (++$attempts < $this->attempts);

            throw new RetriesAttemptsExceeded();
        };
    }
}
