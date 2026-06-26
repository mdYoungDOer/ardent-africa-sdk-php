<?php

declare(strict_types=1);

namespace Ardent\Sdk;

/**
 * A tiny, read-only PHP client for the Ardent Africa public API (/public/v1).
 *
 * Methods return the decoded JSON as associative arrays. List endpoints return
 * `['data' => [...], 'page' => int, 'limit' => int, 'total' => int]`.
 *
 * ```php
 * $ardent = new \Ardent\Sdk\Client(['api_key' => getenv('ARDENT_API_KEY')]);
 * $page = $ardent->listCampaigns(['limit' => 5, 'category' => 'Education']);
 * ```
 */
class Client
{
    private string $baseUrl;
    private ?string $apiKey;
    /** @var callable|null fn(string $url, array $headers): array{0:int,1:string} — for tests */
    private $transport;

    /**
     * @param array{api_key?:string,base_url?:string,transport?:callable} $options
     */
    public function __construct(array $options = [])
    {
        $this->baseUrl   = rtrim($options['base_url'] ?? 'https://api.ardent.africa/public/v1', '/');
        $this->apiKey    = $options['api_key'] ?? null;
        $this->transport = $options['transport'] ?? null;
    }

    /**
     * @param array<string,mixed> $query
     * @return array<string,mixed>
     */
    private function request(string $path, array $query = []): array
    {
        $url   = $this->baseUrl . $path;
        $query = array_filter(
            $query,
            static fn ($v) => $v !== null && $v !== ''
        );
        if (! empty($query)) {
            $url .= '?' . http_build_query($query);
        }

        $headers = ['Accept: application/json'];
        if ($this->apiKey) {
            $headers[] = 'x-api-key: ' . $this->apiKey;
        }

        [$status, $body] = $this->send($url, $headers);
        $data            = json_decode($body, true);

        if ($status < 200 || $status >= 300) {
            $err = is_array($data) && isset($data['error']) ? $data['error'] : [];
            throw new ArdentApiException(
                $err['message'] ?? ('Request failed with status ' . $status),
                $err['code'] ?? 'INTERNAL',
                $status,
                $err['ref'] ?? null
            );
        }

        return is_array($data) ? $data : [];
    }

    /**
     * @param string[] $headers
     * @return array{0:int,1:string}
     */
    private function send(string $url, array $headers): array
    {
        if ($this->transport !== null) {
            return ($this->transport)($url, $headers);
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_USERAGENT      => 'ardent-africa-php/1.0',
        ]);
        $body = curl_exec($ch);
        if ($body === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new ArdentApiException('Network error: ' . $error, 'NETWORK', 0, null);
        }
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [$status, (string) $body];
    }

    // ---------------------------------------------------------------- campaigns
    public function listCampaigns(array $params = []): array
    {
        return $this->request('/campaigns', $params);
    }

    public function getCampaign(string $slug): array
    {
        return $this->request('/campaigns/' . rawurlencode($slug));
    }

    // ---------------------------------------------------------------- petitions
    public function listPetitions(array $params = []): array
    {
        return $this->request('/petitions', $params);
    }

    public function getPetition(string $slug): array
    {
        return $this->request('/petitions/' . rawurlencode($slug));
    }

    // ------------------------------------------------------------------- events
    public function listEvents(array $params = []): array
    {
        return $this->request('/events', $params);
    }

    public function getEvent(string $slug): array
    {
        return $this->request('/events/' . rawurlencode($slug));
    }

    // -------------------------------------------------------------- marketplace
    public function listServices(array $params = []): array
    {
        return $this->request('/marketplace/services', $params);
    }

    public function getService(string $id): array
    {
        return $this->request('/marketplace/services/' . rawurlencode($id));
    }

    public function listCategories(): array
    {
        return $this->request('/marketplace/categories');
    }

    // --------------------------------------------------------------------- blog
    public function listBlog(array $params = []): array
    {
        return $this->request('/blog', $params);
    }

    public function getBlogPost(string $slug): array
    {
        return $this->request('/blog/' . rawurlencode($slug));
    }

    // ----------------------------------------------------------------- profiles
    public function getProfile(string $username): array
    {
        return $this->request('/profiles/' . rawurlencode($username));
    }

    // -------------------------------------------------------------------- stats
    public function getStats(): array
    {
        return $this->request('/stats');
    }
}
