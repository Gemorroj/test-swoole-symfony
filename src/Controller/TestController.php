<?php

namespace App\Controller;

use App\Service\HttpClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Swoole\Coroutine\WaitGroup;
use Swoole\Coroutine as Co;

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
    public function testComplex(HttpClient $httpClient): JsonResponse
    {
        $wg = new WaitGroup();

        $startTime = microtime(true);
        $results = [];

        foreach ([1, 1, 1, 1] as $delay) {
            go(function () use ($wg, $httpClient, $delay, &$results) {
                $wg->add();
                $results[] = $httpClient->get($delay);
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
}
