<?php

declare(strict_types=1);

namespace Kafkiansky\GrpcClient;

use Carbon\CarbonInterval;
use Google\Protobuf\Internal\Message;
use Grpc\UnaryCall;

/**
 * @template G of \Grpc\BaseStub
 *
 * @psalm-import-type MiddlewareFn from Middleware
 */
final class Client
{
    /**
     * @psalm-var (Middleware<Message, Message>|callable(MiddlewareFn): MiddlewareFn)[]
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
     * @template TReq of Message
     * @template TResp of Message
     *
     * @param Middleware<TReq, TResp>|callable(MiddlewareFn):MiddlewareFn $middleware
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
     * @template TReq of Message
     * @template TResp of Message
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
