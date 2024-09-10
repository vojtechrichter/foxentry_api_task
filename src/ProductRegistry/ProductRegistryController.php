<?php declare(strict_types = 1);

namespace FoxentryApiTask\ProductRegistry;

use Elastic\Elasticsearch\Exception\ClientResponseException;
use Elastic\Elasticsearch\Exception\MissingParameterException;
use Elastic\Elasticsearch\Exception\ServerResponseException;
use Fig\Http\Message\StatusCodeInterface;
use FoxentryApiTask\ProductStorage\ElasticProductsController;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ProductRegistryController
{
    /**
     * @throws ClientResponseException
     * @throws ServerResponseException
     */
    public function getProducts(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $products = ElasticProductsController::getProducts();

        $json_product = [];
        $final_products = [];
        foreach ($products as $product) {
            $json_product['product_id'] = $product['_source']['product_id'];
            $json_product['name'] = $product['_source']['name'];
            $json_product['price'] = $product['_source']['price'];
            $json_product['quantity'] = $product['_source']['quantity'];

            $final_products[] = $json_product;
        }

        $response->getBody()->write(json_encode($final_products));

        return $response->withStatus(StatusCodeInterface::STATUS_OK)
            ->withHeader('Content-Type', 'application/json');
    }

    /**
     * @throws ServerResponseException
     * @throws ClientResponseException
     */
    public function getProduct(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $product = ElasticProductsController::getProduct((int)$args['id']);
        if ($product !== []) {
            $json_product['product_id'] = $product[0]['_source']['product_id'];
            $json_product['name'] = $product[0]['_source']['name'];
            $json_product['price'] = $product[0]['_source']['price'];
            $json_product['quantity'] = $product[0]['_source']['quantity'];

            $response->getBody()->write(json_encode($json_product));

            return $response->withHeader('Content-Type', 'application/json')
                ->withStatus(StatusCodeInterface::STATUS_OK);
        }

        return $response->withStatus(StatusCodeInterface::STATUS_NOT_FOUND);
    }

    /**
     * @throws ServerResponseException
     * @throws ClientResponseException
     * @throws MissingParameterException
     */
    public function deleteProduct(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        if (ElasticProductsController::productExistsByProductId((int)$args['id'])) {
            $params = [
                'index' => 'shop_products',
                'id' => $args['id'],
            ];

            ElasticProductsController::deleteProduct($params);

            return $response->withStatus(StatusCodeInterface::STATUS_OK);
        } else {
            return $response->withStatus(StatusCodeInterface::STATUS_NOT_FOUND);
        }
    }

    /**
     * @throws ServerResponseException
     * @throws ClientResponseException
     * @throws MissingParameterException
     */
    public function insertProduct(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $request_body = json_decode($request->getBody()->getContents(), true);

        if (ElasticProductsController::productExistsByName($request_body['name'])) {
            return $response->withStatus(StatusCodeInterface::STATUS_CONFLICT);
        } else {
            $id = ElasticProductsController::getIncrementedProductCounter();
            $es_params = [
                'index' => 'shop_products',
                'id' => $id,
                'body' => [
                    'product_id' => $id,
                    'name' => $request_body['name'],
                    'price' => $request_body['price'],
                    'quantity' => $request_body['quantity'],
                ]
            ];

            ElasticProductsController::insertProduct($es_params);

            return $response->withStatus(StatusCodeInterface::STATUS_OK);
        }
    }

    /**
     * @throws ClientResponseException
     * @throws ServerResponseException
     * @throws MissingParameterException
     */
    public function updateProduct(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $request_body = json_decode($request->getBody()->getContents(), true);

        $props_to_update = [];
        foreach ($request_body as $key => $value) {
            $props_to_update[$key] = $value;
        }

        if (ElasticProductsController::productExistsByProductId((int)$args['id'])) {
            $es_params = [
                'index' => 'shop_products',
                'id' => $args['id'],
                'body' => [
                    'doc' => $props_to_update
                ]
            ];

            ElasticProductsController::updateProduct($es_params);

            return $response->withStatus(StatusCodeInterface::STATUS_OK);
        } else {
            return $response->withStatus(StatusCodeInterface::STATUS_NOT_FOUND);
        }
    }

    /**
     * @throws ClientResponseException
     * @throws ServerResponseException
     */
    public function getPurchases(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $purchases = ElasticProductsController::getPurchases();

        $json_purchase = [];
        $final_purchases = [];
        foreach ($purchases as $purchase) {
            $json_purchase['customer_id'] = $purchase['_source']['customer_id'];
            $json_purchase['product_id'] = $purchase['_source']['product_id'];
            $json_purchase['quantity'] = $purchase['_source']['quantity'];

            $final_purchases[] = $json_purchase;
        }

        $response->getBody()->write(json_encode($final_purchases));

        return $response->withStatus(StatusCodeInterface::STATUS_OK)
            ->withHeader('Content-Type', 'application/json');
    }

    public function searchByName(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $found = ElasticProductsController::searchForProductByName($args['name']);
        if ($found !== []) {
            $response->getBody()->write(json_encode($found));

            return $response->withStatus(StatusCodeInterface::STATUS_OK)
                ->withHeader('Content-Type', 'application/json');
        }

        return $response->withStatus(StatusCodeInterface::STATUS_NOT_FOUND);
    }
}