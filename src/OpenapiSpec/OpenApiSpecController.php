<?php declare(strict_types=1);

namespace FoxentryApiTask\OpenapiSpec;

use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class OpenApiSpecController
{
    public function renderOpenApiSpec(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $file = file_get_contents(__DIR__ . '/../openapi.html');

        $response->getBody()->write($file);

        return $response->withHeader('Content-Type', 'text/html')
            ->withStatus(StatusCodeInterface::STATUS_OK);
    }
}