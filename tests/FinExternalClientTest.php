<?php

require __DIR__ . '/../src/FinExternalClient.php';
require __DIR__ . '/../src/FinExternalSdkError.php';

use Mamatech\ExternalSdk\FinExternalClient;
use Mamatech\ExternalSdk\FinExternalSdkError;

function assertTrue(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

putenv('FIN_APP_CODE=app-code');
putenv('FIN_SECRET_KEY=secret-key');

$captured = [];
$client = new FinExternalClient(null, null, null, function (string $url, string $method, array $headers, ?array $payload) use (&$captured): array {
    $captured = compact('url', 'method', 'headers', 'payload');
    return ['status' => 200, 'body' => '{"token":"jwt","user":{"id":1}}'];
});
$login = $client->loginExternalUser('cust_12345');
assertTrue($captured['url'] === 'https://api.fin.io/auth/v1/external/login', 'login url mismatch');
assertTrue($captured['method'] === 'POST', 'login method mismatch');
assertTrue($captured['payload']['code'] === 'app-code', 'env app code mismatch');
assertTrue($captured['payload']['secretKey'] === 'secret-key', 'env secret key mismatch');
assertTrue($login['token'] === 'jwt', 'login token mismatch');

$captured = [];
$client = new FinExternalClient('https://api.fin.io', 'app-code', 'secret-key', function (string $url, string $method, array $headers, ?array $payload) use (&$captured): array {
    $captured = compact('url', 'method', 'headers', 'payload');
    return ['status' => 200, 'body' => '[]'];
});
$client->getMessages('jwt-token', 25, 5);
assertTrue($captured['url'] === 'https://api.fin.io/api/v1/conversations/external/messages?limit=25&offset=5', 'messages url mismatch');
assertTrue(in_array('Authorization: Bearer jwt-token', $captured['headers'], true), 'auth header missing');

$client = new FinExternalClient('https://api.fin.io', 'app-code', 'secret-key', function (): array {
    return ['status' => 200, 'body' => "{\"type\":\"content\"}\n\n{\"type\":\"done\"}\n"];
});
$events = $client->sendMessage('jwt-token', 'hello');
assertTrue($events === ['{"type":"content"}', '{"type":"done"}'], 'stream parsing mismatch');

$client = new FinExternalClient('https://api.fin.io', 'app-code', 'secret-key', function (): array {
    return ['status' => 401, 'body' => '{"message":"Unauthorized"}'];
});
try {
    $client->getContext('jwt-token');
    throw new RuntimeException('expected FinExternalSdkError');
} catch (FinExternalSdkError $error) {
    assertTrue($error->status === 401, 'error status mismatch');
    assertTrue(str_contains($error->body, 'Unauthorized'), 'error body mismatch');
}

echo "PHP tests passed\n";
