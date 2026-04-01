<?php

namespace Mamatech\ExternalSdk;

final class FinExternalClient
{
    private string $baseUrl;
    private string $appCode;
    private string $secretKey;
    /** @var null|callable */
    private $transport;

    public function __construct(?string $baseUrl = null, ?string $appCode = null, ?string $secretKey = null, ?callable $transport = null)
    {
        $this->baseUrl = $baseUrl ?? (getenv('FIN_BASE_URL') ?: 'https://api.fin.io');
        $this->appCode = $appCode ?? (getenv('FIN_APP_CODE') ?: '');
        $this->secretKey = $secretKey ?? (getenv('FIN_SECRET_KEY') ?: '');
        $this->transport = $transport;

        if ($this->appCode === '' || $this->secretKey === '') {
            throw new \InvalidArgumentException('FIN_APP_CODE and FIN_SECRET_KEY are required');
        }
    }

    public function createExternalUser(string $firstName, string $lastName, string $thirdPartyId, ?string $language = null): array
    {
        $payload = [
            'firstName' => $firstName,
            'lastName' => $lastName,
            'thirdPartyId' => $thirdPartyId,
            'code' => $this->appCode,
            'secretKey' => $this->secretKey,
        ];
        if ($language !== null) {
            $payload['language'] = $language;
        }
        return $this->requestJson('/auth/v1/external/user', 'POST', $payload);
    }

    public function loginExternalUser(string $thirdPartyId): array
    {
        return $this->requestJson('/auth/v1/external/login', 'POST', [
            'thirdPartyId' => $thirdPartyId,
            'code' => $this->appCode,
            'secretKey' => $this->secretKey,
        ]);
    }

    public function renewToken(string $token): array
    {
        return $this->requestJson('/api/v1/external/renew', 'POST', null, $token);
    }

    public function getContext(string $token): array
    {
        return $this->requestJson('/api/v1/external/context', 'GET', null, $token);
    }

    public function listPeople(string $token): array
    {
        return $this->requestJson('/api/v1/person/all', 'GET', null, $token);
    }

    public function getMessages(string $token, int $limit = 20, int $offset = 0): array
    {
        return $this->requestJson(
            sprintf('/api/v1/conversations/external/messages?limit=%d&offset=%d', $limit, $offset),
            'GET',
            null,
            $token
        );
    }

    public function sendMessage(string $token, string $text, ?string $securityHeader = null): array
    {
        return $this->requestStream('/api/v1/conversations/external/message', ['text' => $text], $token, $securityHeader);
    }

    public function submitSecureInput(string $token, string $field, string $value, ?string $securityHeader = null): array
    {
        return $this->requestStream(
            '/api/v1/conversations/external/secure-input',
            ['field' => $field, 'value' => $value],
            $token,
            $securityHeader
        );
    }

    private function requestJson(string $path, string $method, ?array $payload, ?string $token = null, ?string $securityHeader = null): array
    {
        $body = $this->send($path, $method, $payload, $token, $securityHeader);
        return $body === '' ? [] : json_decode($body, true, flags: JSON_THROW_ON_ERROR);
    }

    private function requestStream(string $path, array $payload, string $token, ?string $securityHeader = null): array
    {
        $body = $this->send($path, 'POST', $payload, $token, $securityHeader);
        return array_values(array_filter(array_map('trim', explode("\n", $body))));
    }

    private function send(string $path, string $method, ?array $payload, ?string $token = null, ?string $securityHeader = null): string
    {
        $headers = ['Content-Type: application/json'];
        if ($token !== null && $token !== '') {
            $headers[] = 'Authorization: Bearer ' . $token;
        }
        if ($securityHeader !== null && $securityHeader !== '') {
            $headers[] = 'life-graph: ' . $securityHeader;
        }
        if ($this->transport !== null) {
            $result = ($this->transport)($this->baseUrl . $path, $method, $headers, $payload);
            $status = (int) ($result['status'] ?? 500);
            $responseBody = (string) ($result['body'] ?? '');
            if ($status < 200 || $status > 299) {
                throw new FinExternalSdkError('FIN request failed for ' . $path, $status, $responseBody);
            }
            return $responseBody;
        }

        $handle = curl_init($this->baseUrl . $path);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($handle, CURLOPT_HTTPHEADER, $headers);
        if ($payload !== null) {
            curl_setopt($handle, CURLOPT_POSTFIELDS, json_encode($payload, JSON_THROW_ON_ERROR));
        }

        $responseBody = curl_exec($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        if ($responseBody === false) {
            $message = curl_error($handle);
            curl_close($handle);
            throw new \RuntimeException($message);
        }
        curl_close($handle);

        if ($status < 200 || $status > 299) {
            throw new FinExternalSdkError('FIN request failed for ' . $path, $status, $responseBody);
        }

        return $responseBody;
    }
}
