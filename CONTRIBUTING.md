# Contributing

- Keep `FIN_APP_CODE` and `FIN_SECRET_KEY` server-side only.
- Prefer mocked tests over live FIN calls.
- Run the PHP lint and test commands before tagging a release.

```bash
php -l src/FinExternalClient.php
php -l src/FinExternalSdkError.php
php tests/FinExternalClientTest.php
```
