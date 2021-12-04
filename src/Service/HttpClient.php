<?php

namespace App\Service;

use Symfony\Component\HttpClient\HttpClient as SymfonyHttpClient;
use Symfony\Component\HttpClient\ScopingHttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class HttpClient
{
    private HttpClientInterface $httpClient;

    public function __construct()
    {
        $this->httpClient = ScopingHttpClient::forBaseUri(SymfonyHttpClient::create(), 'https://httpbin.org', [
            'timeout' => 2,
            'headers' => [
                'Accept' => 'application/json',
            ],
        ]);
    }

    public function get(int $delay): array
    {
        return $this->httpClient->request('GET', '/delay/'.$delay)->toArray();
    }
}
