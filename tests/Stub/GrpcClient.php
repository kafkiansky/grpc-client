<?php

declare(strict_types=1);

namespace Kafkiansky\GrpcClient\Tests\Stub;

use Google\Protobuf\Internal\Message;
use Grpc\BaseStub;

class GrpcClient extends BaseStub
{
    public function create(Message $message)
    {
        return $message;
    }
}
