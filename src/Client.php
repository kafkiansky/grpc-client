<?php

declare(strict_types=1);

namespace Kafkiansky\GrpcClient;

use Carbon\CarbonInterval;
use Grpc\UnaryCall;

/**
 * @template G of \Grpc\BaseStub
 *
 * @psalm-import-type MiddlewareFn from Middleware
 * @psalm-type StackMiddleware = Middleware|callable(MiddlewareFn): MiddlewareFn
 */
final class Client
{
    /**
     * @var StackMiddleware[]
     */
    private array $stack = [];

    /**
     * @param G $delegate
     */
    public function __construct(
        private readonly object $delegate,
    ) {
    }

    /**
     * @param StackMiddleware $middleware
     */
    public function withMiddleware(Middleware|callable $middleware): Client
    {
        $this->stack[] = $middleware;

        return $this;
    }

    public function close(): void
    {
        $this->delegate->close();
    }

    public function __destruct()
    {
        $this->close();
    }

    /**
     * @template TReq of \Google\Protobuf\Internal\Message
     * @template TResp of \Google\Protobuf\Internal\Message
     *
     * @psalm-param non-empty-string $method
     * @psalm-param TReq $request
     *
     * @throws ClientException
     *
     * @psalm-return Result<TResp>
     */
    public function invoke(string $method, $request, ?RequestContext $context = null): Result
    {
        $context ??= new RequestContext();

        /**
         * @return Result<TResp>
         * @throws ClientException
         */
        $handler = function (object $request, RequestContext $context) use ($method): Result {
            if (null !== $context->timeout) {
                $context->withOptions([
                    'timeout' => (int)CarbonInterval::milliseconds($context->timeout)->totalMicroseconds
                ]);
            }

            /** @var UnaryCall */
            $call = $this->delegate->{$method}(
                $request,
                $context->metadata,
                $context->options,
            );

            /**
             * @psalm-var TResp $result
             * @psalm-var object{code:int} $status
             */
            [$result, $status] = $call->wait();

            $status = StatusCode::tryFrom($status->code) ?: StatusCode::UNKNOWN;

            return new Result($status, $result);
        };

        foreach (\array_reverse($this->stack) as $middleware) {
            $handler = $middleware($handler);
        }

        /** @var Result<TResp> */
        return $handler($request, $context);
    }
}
