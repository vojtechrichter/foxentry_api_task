<?php declare(strict_types=1);

namespace FoxentryApiTask\CustomerInterface;

use Elastic\Elasticsearch\Exception\ClientResponseException;
use Elastic\Elasticsearch\Exception\MissingParameterException;
use Elastic\Elasticsearch\Exception\ServerResponseException;
use Fig\Http\Message\StatusCodeInterface;
use FoxentryApiTask\ProductStorage\ElasticProductsController;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class CustomerController
{
    /**
     * @throws ClientResponseException
     * @throws ServerResponseException
     * @throws MissingParameterException
     */
    public function buyProduct(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $request_body = json_decode($request->getBody()->getContents(), true);
        ElasticProductsController::buyProduct((int)$args['id'], $request_body['customer_id'], (int)$request_body['quantity']);

        return $response->withStatus(StatusCodeInterface::STATUS_OK);
    }

    public function generateId(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = ['customer_id' => CustomerIdGenerator::generate()];

        $response->getBody()->write(json_encode($id));

        return $response->withStatus(200)
            ->withHeader('Content-Type', 'application/json');
    }
}