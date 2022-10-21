<?php

namespace App\Controller;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpClient\ScopingHttpClient;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Swoole\Coroutine\WaitGroup;
use Swoole\Coroutine as Co;
use function Swoole\Coroutine\batch;

class TestController extends AbstractController
{
    #[Route('/test', name: 'test', format: 'json')]
    public function test(): JsonResponse
    {
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/TestController.php',
        ]);
    }

    #[Route('/test-simple', name: 'test-simple', format: 'json')]
    public function testSimple(): JsonResponse
    {
        $wg = new WaitGroup();

        $startTime = microtime(true);
        $results = [];

        go(function () use ($wg, &$results) {
            $wg->add();
            co::sleep(1);
            $results[] = 'Hello';
            $wg->done();
        });
        go(function () use ($wg, &$results) {
            $wg->add();
            co::sleep(1);
            $results[] = 'World';
            $wg->done();
        });
        go(function () use ($wg, &$results) {
            $wg->add();
            co::sleep(1);
            $results[] = '!';
            $wg->done();
        });

        $wg->wait(60);
        $time = microtime(true) - $startTime;

        return $this->json([
            'expected_time' => '~1 sec.',
            'actual_time' => \round($time, 2),
            'results' => $results,
        ]);
    }

    #[Route('/test-complex', name: 'test-complex', format: 'json')]
    public function testComplex(): JsonResponse
    {
        /*
test-swoole-symfony-php-1  | 2022-10-21T10:43:45+00:00 [critical] Fatal Error: Uncaught Swoole\Error: The given object is not a valid coroutine CurlMultiHandle object in /var/www/app/vendor/symfony/http-client/Response/CurlResponse.php:179
test-swoole-symfony-php-1  | Stack trace:
test-swoole-symfony-php-1  | #0 /var/www/app/vendor/symfony/http-client/Response/CurlResponse.php(179): curl_multi_add_handle(Object(CurlMultiHandle), Object(CurlHandle))
test-swoole-symfony-php-1  | #1 /var/www/app/vendor/symfony/http-client/CurlHttpClient.php(304): Symfony\Component\HttpClient\Response\CurlResponse->__construct(Object(Symfony\Component\HttpClient\Internal\CurlClientState), Object(CurlHandle), Array, NULL, 'GET', Object(Closure), 480001, 'https://httpbin...')
test-swoole-symfony-php-1  | #2 /var/www/app/vendor/symfony/http-client/ScopingHttpClient.php(93): Symfony\Component\HttpClient\CurlHttpClient->request('GET', 'https://httpbin...', Array)
test-swoole-symfony-php-1  | #3 /var/www/app/src/Controller/TestController.php(92): Symfony\Component\HttpClient\ScopingHttpClient->request('GET', 'https://httpbin...')
test-swoole-symfony-php-1  | #4 [internal function]: App\Controller\TestController->App\Controller\{closure}()
         */

        $httpClient = ScopingHttpClient::forBaseUri(HttpClient::create(), 'https://httpbin.org', [
            'timeout' => 2,
            'headers' => [
                'Accept' => 'application/json',
            ],
        ]);

        \Swoole\Runtime::enableCoroutine(true, \SWOOLE_HOOK_ALL);
        $wg = new WaitGroup();

        $startTime = microtime(true);
        $results = [];

        foreach ([1, 1, 1, 1] as $delay) {
            go(function () use ($wg, $httpClient, $delay, &$results) {
                $wg->add();
                $results[] = $httpClient->request('GET', '/delay/'.$delay)->toArray();
                $wg->done();
            });
        }

        $wg->wait(60);
        $time = microtime(true) - $startTime;

        return $this->json([
            'expected_time' => '~1 sec.',
            'actual_time' => \round($time, 2),
            'results' => $results,
        ]);
    }

    #[Route('/test-complex-batch', name: 'test-complex-batch', format: 'json')]
    public function testComplexBatch(): JsonResponse
    {
        /*
test-swoole-symfony-php-1  | 2022-10-21T10:47:44+00:00 [critical] Fatal Error: Uncaught Swoole\Error: The given object is not a valid coroutine CurlMultiHandle object in /var/www/app/vendor/symfony/http-client/Response/CurlResponse.php:179
test-swoole-symfony-php-1  | Stack trace:
test-swoole-symfony-php-1  | #0 /var/www/app/vendor/symfony/http-client/Response/CurlResponse.php(179): curl_multi_add_handle(Object(CurlMultiHandle), Object(CurlHandle))
test-swoole-symfony-php-1  | #1 /var/www/app/vendor/symfony/http-client/CurlHttpClient.php(304): Symfony\Component\HttpClient\Response\CurlResponse->__construct(Object(Symfony\Component\HttpClient\Internal\CurlClientState), Object(CurlHandle), Array, NULL, 'GET', Object(Closure), 480001, 'https://httpbin...')
test-swoole-symfony-php-1  | #2 /var/www/app/vendor/symfony/http-client/ScopingHttpClient.php(93): Symfony\Component\HttpClient\CurlHttpClient->request('GET', 'https://httpbin...', Array)
test-swoole-symfony-php-1  | #3 /var/www/app/src/Controller/TestController.php(134): Symfony\Component\HttpClient\ScopingHttpClient->request('GET', 'https://httpbin...')
test-swoole-symfony-php-1  | #4 @swoole-src/library/core/Coroutine/functions.php(37): App\Controller\TestController::App\Controller\{closure}()
test-swoole-symfony-php-1  | #5 [internal function]: Swoole\Coroutine\{closure}()
         */

        $httpClient = ScopingHttpClient::forBaseUri(HttpClient::create(), 'https://httpbin.org', [
            'timeout' => 2,
            'headers' => [
                'Accept' => 'application/json',
            ],
        ]);

        \Swoole\Runtime::enableCoroutine(true, \SWOOLE_HOOK_ALL);
        $startTime = microtime(true);

        $tasks = [];
        foreach ([1, 1, 1, 1] as $delay) {
            $tasks[] = static function () use ($delay, $httpClient) {
                return $httpClient->request('GET', '/delay/'.$delay)->toArray();
            };
        }
        $results = batch($tasks, 60);
        $time = microtime(true) - $startTime;

        return $this->json([
            'expected_time' => '~1 sec.',
            'actual_time' => \round($time, 2),
            'results' => $results,
        ]);
    }

    #[Route('/test-simple-http', name: 'test-simple-http', format: 'json')]
    public function testSimpleHttp(): JsonResponse
    {
        // swoole hooks for file_get_contents.
        // we must build swoole with support several additions (see Dockerfile)
        // enable corutines
        // and enable hooks - https://www.swoole.co.uk/docs/modules/swoole-runtime-flags#swoole-runtime-hook-flags

        \Swoole\Runtime::enableCoroutine(true, \SWOOLE_HOOK_ALL);

        $startTime = microtime(true);

        $tasks = [];
        foreach ([1, 1, 1, 1] as $delay) {
            $tasks[] = static function () use ($delay) {
                return \file_get_contents('https://httpbin.org/delay/'.$delay);
            };
        }
        $results = batch($tasks, 60);
        $time = microtime(true) - $startTime;

        return $this->json([
            'expected_time' => '~1 sec.',
            'actual_time' => \round($time, 2),
            'results' => $results,
        ]);
    }
}
