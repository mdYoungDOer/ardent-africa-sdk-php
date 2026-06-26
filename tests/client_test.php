<?php

declare(strict_types=1);

/**
 * Dependency-free test harness (no PHPUnit required): run with `php tests/client_test.php`.
 * Uses an injected transport so no network is touched.
 */

require __DIR__ . '/../src/ArdentApiException.php';
require __DIR__ . '/../src/Client.php';

use Ardent\Sdk\ArdentApiException;
use Ardent\Sdk\Client;

$failures = 0;
function check(bool $cond, string $msg): void
{
    global $failures;
    if ($cond) {
        echo "  ok  - {$msg}\n";
    } else {
        $failures++;
        echo "  FAIL - {$msg}\n";
    }
}

// 1. Query + header building, with empty/null values dropped.
$captured = [];
$client   = new Client([
    'api_key'   => 'ardent_pk_test',
    'transport' => function (string $url, array $headers) use (&$captured): array {
        $captured = ['url' => $url, 'headers' => $headers];
        return [200, json_encode(['data' => [], 'page' => 1, 'limit' => 5, 'total' => 0])];
    },
]);
$res = $client->listCampaigns(['limit' => 5, 'category' => 'Education', 'q' => '']);
check($res['total'] === 0, 'decodes JSON body into an array');
check(strpos($captured['url'], '/campaigns?') !== false, 'appends query string for list calls');
check(strpos($captured['url'], 'limit=5') !== false, 'includes provided params');
check(strpos($captured['url'], 'category=Education') !== false, 'includes string params');
check(strpos($captured['url'], 'q=') === false, 'drops empty-string params');
check(in_array('x-api-key: ardent_pk_test', $captured['headers'], true), 'sends x-api-key header when key set');

// 2. No key => no x-api-key header.
$captured2 = [];
$anon      = new Client([
    'transport' => function (string $url, array $headers) use (&$captured2): array {
        $captured2 = ['url' => $url, 'headers' => $headers];
        return [200, json_encode(['ok' => true])];
    },
]);
$anon->getStats();
$hasKey = false;
foreach ($captured2['headers'] as $h) {
    if (stripos($h, 'x-api-key') === 0) {
        $hasKey = true;
    }
}
check(! $hasKey, 'omits x-api-key header when no key set');
check(substr($captured2['url'], -6) === '/stats', 'builds path without trailing query when no params');

// 3. Non-2xx throws ArdentApiException carrying envelope fields.
$errClient = new Client([
    'transport' => fn (string $url, array $headers): array => [
        404,
        json_encode(['ok' => false, 'error' => ['code' => 'NOT_FOUND', 'message' => 'nope', 'ref' => 'r1']]),
    ],
]);
try {
    $errClient->getCampaign('does-not-exist');
    check(false, 'throws on non-2xx');
} catch (ArdentApiException $e) {
    check($e->apiCode === 'NOT_FOUND', 'exception carries the API error code');
    check($e->httpStatus === 404, 'exception carries the HTTP status');
    check($e->ref === 'r1', 'exception carries the support ref');
    check($e->getMessage() === 'nope', 'exception message comes from the envelope');
}

// 4. Path encoding for detail calls.
$captured3 = [];
$encClient = new Client([
    'transport' => function (string $url, array $headers) use (&$captured3): array {
        $captured3 = $url;
        return [200, json_encode(['slug' => 'a b'])];
    },
]);
$encClient->getCampaign('a b/c');
check(strpos($captured3, 'a%20b%2Fc') !== false, 'raw-url-encodes path segments');

// 5. Custom base URL is honored (and trailing slash trimmed).
$captured4 = '';
$staging   = new Client([
    'base_url'  => 'https://staging.example.com/public/v1/',
    'transport' => function (string $url, array $headers) use (&$captured4): array {
        $captured4 = $url;
        return [200, json_encode(['data' => []])];
    },
]);
$staging->listPetitions();
check(strpos($captured4, 'https://staging.example.com/public/v1/petitions') === 0, 'honors custom base_url and trims trailing slash');

echo "\n";
if ($failures > 0) {
    fwrite(STDERR, "{$failures} test(s) failed\n");
    exit(1);
}
echo "all tests passed\n";
exit(0);
