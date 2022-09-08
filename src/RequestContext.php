<?php

declare(strict_types=1);

namespace Kafkiansky\GrpcClient;

use Composer\InstalledVersions;

final class RequestContext
{
    /**
     * @psalm-readonly-allow-private-mutation
     *
     * @var array<string, mixed>
     */
    public array $options;

    /**
     * @psalm-readonly-allow-private-mutation
     *
     * @var array<string, mixed>
     */
    public array $metadata = [];

    /**
     * @psalm-allow-private-mutation
     *
     * @var array<string, mixed>
     */
    public array $context = [];

    /**
     * @param null|positive-int|0 $timeout In milliseconds.
     */
    public function __construct(
        public readonly ?int $timeout = null,
    ) {
        $this->options = [
            'client-name' => 'kafkiansky/grpc-client',
            'client-version' => InstalledVersions::getVersion('kafkiansky/grpc-client'),
        ];
    }

    /**
     * @param array<string, mixed> $ctx
     */
    public function withContext(array $ctx): RequestContext
    {
        $this->context = \array_merge($this->context, $ctx);

        return $this;
    }

    /**
     * @param array<string, mixed> $options
     */
    public function withOptions(array $options): RequestContext
    {
        $this->options = \array_merge($this->options, $options);

        return $this;
    }

    /**
     * @param array<string, mixed> $metadata
     */
    public function withMetadata(array $metadata): RequestContext
    {
        $this->metadata = \array_merge($this->metadata, $metadata);

        return $this;
    }
}
