<?php

namespace Mamatech\ExternalSdk;

final class FinExternalSdkError extends \RuntimeException
{
    public int $status;
    public string $body;

    public function __construct(string $message, int $status, string $body)
    {
        parent::__construct($message, $status);
        $this->status = $status;
        $this->body = $body;
    }
}
