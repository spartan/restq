<?php

namespace Spartan\Rest\Middleware;

use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Spartan\Http\Exception\HttpBadRequest;
use Spartan\Validation\Validator\JsonSchema;

/**
 * ValidateJsonSchema Middleware
 *
 * @package Spartan\Rest
 * @author  Iulian N. <iulian@spartanphp.com>
 * @license LICENSE MIT
 */
class ValidateJsonSchema implements MiddlewareInterface
{
    /**
     * {@inheritdoc}
     * @param ServerRequestInterface  $request
     * @param RequestHandlerInterface $handler
     *
     * @return ResponseInterface
     * @throws HttpBadRequest
     * @throws \ReflectionException
     * @throws \Spartan\Service\Exception\ContainerException
     * @throws \Spartan\Service\Exception\NotFoundException
     * @throws \Swaggest\JsonSchema\Exception
     * @throws \Swaggest\JsonSchema\InvalidValue
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $routeHandler = container()->get('route')->handler();
        $actionName   = $actionName = getenv('APP_NAME') . "\\Action\\" . str_replace('.', '\\', $routeHandler);

        $schema    = constant("{$actionName}::SCHEMA");
        $validator = new JsonSchema($schema);

        if ($validator->isValid($request->getParsedBody())) {
            return $handler->handle($request);
        }

        throw new HttpBadRequest(json_encode($validator->getMessages()));
    }
}
