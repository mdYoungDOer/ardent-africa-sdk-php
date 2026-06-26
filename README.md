# ardent-africa/sdk (PHP)

A tiny, **read-only** PHP client for the [Ardent Africa public API](https://docs.ardent.africa/docs/developers) (`/public/v1`). Campaigns, petitions, events, marketplace listings, blog posts, public profiles, and platform stats. No write access, no PII. Depends only on `ext-curl` and `ext-json` — no third-party packages.

## Install

```bash
composer require ardent-africa/sdk
```

Requires PHP 7.4+.

## Usage

```php
require 'vendor/autoload.php';

use Ardent\Sdk\Client;
use Ardent\Sdk\ArdentApiException;

// The key is optional (keyless works at a lower rate tier). Create one at
// https://ardent.africa/dashboard/developer
$ardent = new Client(['api_key' => getenv('ARDENT_API_KEY')]);

$page = $ardent->listCampaigns(['limit' => 5, 'category' => 'Education']);
foreach ($page['data'] as $campaign) {
    echo $campaign['title'], "\n";
}

$campaign = $ardent->getCampaign('clean-water-for-tamale');
$stats    = $ardent->getStats();
```

Methods return the decoded JSON as associative arrays. List endpoints return
`['data' => [...], 'page' => int, 'limit' => int, 'total' => int]`.

### Methods

`listCampaigns` · `getCampaign` · `listPetitions` · `getPetition` · `listEvents` · `getEvent` ·
`listServices` · `getService` · `listCategories` · `listBlog` · `getBlogPost` · `getProfile` ·
`getStats`.

### Errors

Non-2xx responses throw an `ArdentApiException` carrying the API error envelope:

```php
try {
    $ardent->getCampaign('does-not-exist');
} catch (ArdentApiException $e) {
    $e->apiCode;    // e.g. 'NOT_FOUND'
    $e->httpStatus; // e.g. 404
    $e->ref;        // support reference, or null
    $e->getMessage();
}
```

### Options

```php
new Client([
    'api_key'  => 'ardent_pk_…',                          // optional
    'base_url' => 'https://api.ardent.africa/public/v1',  // override for staging
]);
```

## Develop

```bash
composer test   # runs the dependency-free harness in tests/
# or directly:
php tests/client_test.php
```

The response shapes mirror the published [OpenAPI contract](https://api.ardent.africa/public/v1/openapi.json); the API is additive within `v1`, so treat response arrays as open.

## License

MIT
