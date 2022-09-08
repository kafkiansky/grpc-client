<?php

declare(strict_types=1);

namespace Kafkiansky\GrpcClient\Tests\Stub;

use Psr\Log\AbstractLogger;

final class ArrayLogger extends AbstractLogger
{
    /**
     * @var array<string, list<array{string, array}>>
     */
    private array $logs = [];

    /**
     * {@inheritdoc}
     */
    public function log($level, \Stringable|string $message, array $context = []): void
    {
        /** @var string */
        $logLevel = $level;

        $this->logs[$logLevel][] = [(string)$message, $context];
    }

    /**
     * @return array<string, list<array{string, array}>>
     */
    public function flush(): array
    {
        [$logs, $this->logs] = [$this->logs, []];
        return $logs;
    }
}
