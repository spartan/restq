<?php

namespace Spartan\Rest\Middleware;

use Laminas\Diactoros\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Spartan\Cache\Cache;

/**
 * CacheResponse Middleware
 *
 * @package Spartan\Rest
 * @author  Iulian N. <iulian@spartanphp.com>
 * @license LICENSE MIT
 */
class CacheResponse implements MiddlewareInterface
{
    /**
     * Process an incoming server request and return a response, optionally delegating
     * response creation to a handler.
     *
     * @param ServerRequestInterface  $request
     * @param RequestHandlerInterface $handler
     *
     * @return ResponseInterface
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \ReflectionException
     * @throws \Spartan\Service\Exception\ContainerException
     * @throws \Spartan\Service\Exception\NotFoundException
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $ttl = getenv('API_CACHE_TTL');

        if (!$ttl) {
            return $handler->handle($request);
        }

        $method       = $request->getMethod();
        $cacheMethods = ['GET', 'POST'];

        if ($request->getHeaderLine('cache-control') === 'no-cache' || !in_array($method, $cacheMethods)) {
            return $handler->handle($request);
        }

        $cacheKey = 'rest-response-';
        $cacheKey .= md5(
            $request->getUri()->getPath() . serialize(
                (array)$request->getParsedBody() + $request->getQueryParams()
            )
        );

        [$body, $statusCode, $headers] = Cache::getAlways(
            $cacheKey,
            function () use ($handler, $request) {
                $response = $handler->handle($request);

                return [
                    (string)$response->getBody(),
                    $response->getStatusCode(),
                    (array)$response->getHeaders()
                ];
            },
            $ttl
        );

        return http(new Response())
            ->withStatus($statusCode)
            ->withHeaders($headers)
            ->withStringBody($body);
    }
}
