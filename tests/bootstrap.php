<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Test bootstrap — force the testing environment before Laravel loads
|--------------------------------------------------------------------------
|
| The local Docker container bakes APP_ENV=local into its process environment
| (docker-compose.yml `environment:`). That process value shadows phpunit.xml's
| <env name="APP_ENV" value="testing"/> — even with force="true" — and also
| wins over any .env.testing file, because Laravel reads APP_ENV to DECIDE which
| env file to load before that file is consulted.
|
| The consequence was severe: Application::runningUnitTests() (which is simply
| `env === 'testing'`) returned false, so the framework's VerifyCsrfToken
| middleware never self-skipped and every non-GET web test failed with HTTP 419.
| That alone reddened ~86 tests across the suite.
|
| Setting the variable here — in the PHP process, before vendor/autoload.php and
| therefore before the framework boots — is the one place that reliably wins over
| the container's environment. It is equivalent to running the suite with
| `docker compose exec -e APP_ENV=testing`, but committed and portable (CI, other
| machines, teammates) and touching nothing outside the test harness.
*/
// The database gets the same treatment: the local .env points at production,
// and RefreshDatabase must never run against it.
foreach (['APP_ENV' => 'testing', 'DB_CONNECTION' => 'sqlite', 'DB_DATABASE' => ':memory:'] as $key => $value) {
    putenv("{$key}={$value}");
    $_ENV[$key] = $value;
    $_SERVER[$key] = $value;
}

require __DIR__.'/../vendor/autoload.php';
