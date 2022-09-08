<?php

declare(strict_types=1);

require_once __DIR__.'/../vendor/autoload.php';

final class Create extends \Google\Protobuf\Internal\Message {}

$client = new \Kafkiansky\GrpcClient\Client(
    // your generated client here
);

$result = $client->invoke('create', new Create());
echo $result->statusCode->name; // OK
var_dump($result->response); // Protobuf response object