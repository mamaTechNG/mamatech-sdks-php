# Mamatech External PHP SDK

Standalone PHP SDK for the FIN external integration APIs.

Package name:

```text
mamatech/external-sdk
```

Environment variables:

- `FIN_BASE_URL`, default `https://api.fin.io`
- `FIN_APP_CODE`
- `FIN_SECRET_KEY`

Install:

```bash
composer require mamatech/external-sdk
```

Usage:

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use Mamatech\ExternalSdk\FinExternalClient;

$client = new FinExternalClient();
$login = $client->loginExternalUser('cust_12345');
print_r($login);
```

Release:

- Connect this repo in Packagist
- Push a semantic tag like `v0.1.0`
- Refresh the package in Packagist if it does not pick the tag up automatically
