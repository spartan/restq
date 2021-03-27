<?php

namespace Spartan\Rest\Command;

use Jenssegers\Optimus\Energon;
use Spartan\Console\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Init Command
 *
 * @package Spartan\Rest
 * @author  Iulian N. <iulian@spartanphp.com>
 * @license LICENSE MIT
 */
class Init extends Command
{
    protected function configure()
    {
        $this->withSynopsis('rest:init', 'Setup as standalone API');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|null|void
     * @throws \InvalidArgumentException
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /*
         * Middleware:
         *  - Http\ParseJson
         *  - CacheResponse
         *
         * Environment:
         *  - API_RATE_LIMIT
         *  - API_RATE_WHITELIST
         *  - API_TOKEN
         *  - API_CACHE_WHITELIST
         *  - API_CACHE_TTL
         */

        $this->panel("REST options");

        $options = $this->choose(
            'Options',
            [
                'Setup'       => [
                    'routes'  => 'Install routes into ./config/routes.php',
                    'action'  => 'Add API action',
                    'fakeint' => 'Add FAKE_INT constants',
                    'hashid'  => 'Add HASH_ID constants',
                    '_'       => [
                        'selected' => ['routes', 'action'],
                    ],
                ],
                'Middlewares' => [
                    'json'  => 'ParseJson',
                    'acl'   => 'Authorization',
                    'cache' => 'CacheResponse',
                    'cors'  => 'CORS',
                    'rate'  => 'RateLimit (soon)',
                    '_'     => [
                        'selected' => ['json', 'acl', 'cache', 'cors'],
                    ],
                ],
            ]
        );

        $options = array_combine($options, $options);

        $token = substr(sha1(microtime(true)), 0, 32);

        $recipe = [
            'env'        => [],
            'middleware' => [],
        ];

        $recipe['env']['API_TOKEN'] = $token;

        if (isset($options['cors'])) {
            $recipe['middleware'][] = 'Spartan\\Rest\\Middleware\\Cors';
            $recipe['env']          += [
                'CORS_ALLOW_ORIGINS'  => 'http://localhost:8080',
                'CORS_ALLOW_METHODS'  => 'GET,OPTIONS,POST,PUT,PATCH,DELETE',
                'CORS_ALLOW_HEADERS'  => 'content-type,accept-language,cache-control',
                'CORS_EXPOSE_HEADERS' => '',
            ];
        }

        if (isset($options['json'])) {
            $recipe['middleware'][] = 'Spartan\\Http\\Middleware\\ParseJson';
        }

        if (isset($options['acl'])) {
            $recipe['middleware'][] = 'Spartan\\Auth\\Middleware\\Authorization';
        }

        if (isset($options['cache'])) {
            $recipe['env']          += [
                'API_CACHE_WHITELIST' => '*',
                'API_CACHE_TTL'       => 5,
            ];
            $recipe['middleware'][] = 'Spartan\\Rest\\Middleware\\CacheResponse';
        }

        if (isset($options['fakeint'])) {
            [$prime, $inverse, $random] = Energon::generate();
            $recipe['env'] += [
                'FAKE_INT_PRIME'   => $prime,
                'FAKE_INT_INVERSE' => $inverse,
                'FAKE_INT_RANDOM'  => $random,
            ];
        }

        if (isset($options['hashid'])) {
            $recipe['env'] += [
                'HASH_ID_SALT'     => sha1(microtime(true)),
                'HASH_ID_PAD'      => null,
                'HASH_ID_ALPHABET' => '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ',
            ];
        }

        if (isset($options['rate'])) {
            throw new \InvalidArgumentException('Not supported');
        }

        if (isset($options['routes'])) {
            $recipe['routes'] = [
                "['api.all', ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], '/{resource}', 'Api']",
                "['api.one', 'GET', '/{resource}/{id}', 'Api']",
            ];
        }

        if (isset($options['action'])) {
            self::loadEnv();

            $php = file_get_contents(__DIR__ . '/../../data/Action.php');
            $php = str_replace('App\\', getenv('APP_NAME') . '\\', $php);

            file_put_contents('./src/Action/Api.php', $php);
        }

        return 0;
    }
}
