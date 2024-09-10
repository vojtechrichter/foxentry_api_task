<?php declare(strict_types=1);

namespace FoxentryApiTask\ProductStorage;

use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\ClientBuilder;
use Elastic\Elasticsearch\Exception\AuthenticationException;
use Elastic\Elasticsearch\Exception\ClientResponseException;
use Elastic\Elasticsearch\Exception\MissingParameterException;
use Elastic\Elasticsearch\Exception\ServerResponseException;
use Fig\Http\Message\StatusCodeInterface;

final class ElasticProductsController
{
    private static Client $client;

    /**
     * @throws AuthenticationException
     */
    public static function init(): void
    {
        self::$client = ClientBuilder::create()
            ->setHosts(['elasticsearch:9200'])
            ->setBasicAuthentication('elastic', 'heslo')
            ->build();

        self::createTypesMappingForProductsIndex();
        self::createProductCounterIndex();
        self::createPurchasesIndex();
    }

    /**
     * @throws ClientResponseException
     * @throws ServerResponseException
     * @throws MissingParameterException
     */
    private static function createTypesMappingForProductsIndex(): void
    {
         $params = [
             'index' => 'shop_products',
             'body' => [
                 'mappings' => [
                     'properties' => [
                         'product_id' => ['type' => 'keyword'],
                         'name' => ['type' => 'text'],
                         'price' => ['type' => 'integer'],
                         'quantity' => ['type' => 'integer'],
                     ]
                 ]
             ],
         ];
         if (!self::$client->indices()->exists(['index' => $params['index']])->asBool()) {
             self::$client->indices()->create($params);
         }
    }

    /**
     * @throws ServerResponseException
     * @throws ClientResponseException
     * @throws MissingParameterException
     */
    private static function createProductCounterIndex(): void
    {
        $params = [
            'index' => 'product_counter',
            'id' => 'counter',
            'body' => [
                'counter' => 0
            ]
        ];

        self::$client->index($params);
    }

    /**
     * @throws ClientResponseException
     * @throws ServerResponseException
     * @throws MissingParameterException
     */
    public static function getIncrementedProductCounter(): int
    {
        $params = [
            'index' => 'product_counter',
            'id' => 'counter',
            'body' => [
                'script' => [
                    'source' => 'ctx._source.counter += 1',
                    'lang' => 'painless'
                ],
                'upsert' => ['counter' => 1]
            ],
        ];

        $response = self::$client->update($params);

        return $response['_version'];
    }

    /**
     * @throws ClientResponseException
     * @throws ServerResponseException
     * @throws MissingParameterException
     */
    private static function createPurchasesIndex(): void
    {
        $params = [
            'index' => 'purchases',
            'body' => [
                'mappings' => [
                    'properties' => [
                        'customer_id' => ['type' => 'text'],
                        'product_id' => ['type' => 'integer'],
                        'quantity' => ['type' => 'integer'],
                    ]
                ]
            ]
        ];

        if (!self::$client->indices()->exists(['index' => $params['index']])->asBool()) {
            self::$client->indices()->create($params);
        }
    }

    /**
     * @throws ServerResponseException
     * @throws ClientResponseException
     * @throws MissingParameterException
     */
    public static function deleteIndex(string $index): void
    {
        self::$client->indices()->delete(['index' => $index]);
    }

    public static function getClient(): Client
    {
        return self::$client;
    }

    /**
     * @throws ServerResponseException
     * @throws ClientResponseException
     */
    public static function getProducts(): array
    {
        $params = [
            'index' => 'shop_products',
            'body' => [
                'query' => [
                    'match_all' => (object) []
                ]
            ]
        ];

        $response = self::$client->search($params);

        return $response['hits']['hits'];
    }

    /**
     * @throws ClientResponseException
     * @throws ServerResponseException
     */
    public static function getProduct(int $id): array
    {
        $params = [
            'index' => 'shop_products',
            'body' =>[
                'query' => [
                    'match' => [
                        'product_id' => $id
                    ]
                ]
            ]
        ];

        $response = self::$client->search($params);

        if ($response['hits']['total']['value'] === 1) {
            return $response['hits']['hits'];
        }

        return [];
    }

    /**
     * @throws ClientResponseException
     * @throws ServerResponseException
     * @throws MissingParameterException
     */
    public static function insertProduct(array $params): void
    {
        self::$client->index($params);
    }

    /**
     * @throws ServerResponseException
     * @throws ClientResponseException
     * @throws MissingParameterException
     */
    public static function updateProduct(array $params): void
    {
        self::$client->update($params);
    }

    /**
     * @throws ClientResponseException
     * @throws ServerResponseException
     * @throws MissingParameterException
     */
    public static function deleteProduct(array $params): void
    {
        self::$client->delete($params);
    }

    /**
     * @throws ServerResponseException
     * @throws ClientResponseException
     */
    public static function productExistsByName(string $name): bool
    {
        $params = [
            'index' => 'shop_products',
            'body' => [
                'query' => [
                    'match' => [
                        'name' => $name
                    ]
                ]
            ]
        ];

        $response = self::$client->search($params);

        if ($response['hits']['total']['value'] > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @throws ClientResponseException
     * @throws ServerResponseException
     */
    public static function productExistsByProductId(int $id): bool
    {
        $params = [
            'index' => 'shop_products',
            'body' => [
                'query' => [
                    'match' => [
                        'product_id' => $id
                    ]
                ]
            ]
        ];

        $response = self::$client->search($params);

        if ($response['hits']['total']['value'] > 0) {
            return true;
        } else {
            return false;
        }
    }

    public static function getProductIdFromProductName(string $name): int
    {
        $params = [
            'index' => 'shop_products',
            'body' => [
                'query' => [
                    'match' => [
                        'name' => $name
                    ]
                ]
            ]
        ];

        $response = self::$client->search($params);

        return $response['hits']['hits'][0]['_id'];
    }

    /**
     * @throws ServerResponseException
     * @throws ClientResponseException
     * @throws MissingParameterException
     */
    public static function getFieldValueFromProductId(string $field, int $id): string
    {
        $params = [
            'index' => 'shop_products',
            'id' => $id
        ];

        $response = self::$client->get($params);

        return $response['_source'][$field];
    }

    /**
     * @throws ServerResponseException
     * @throws ClientResponseException
     */
    public static function getPurchases(): array
    {
        $params = [
            'index' => 'purchases',
            'body' => [
                'query' =>[
                    'match_all' => (object) []
                ]
            ]
        ];

        $response = self::$client->search($params);

        return $response['hits']['hits'];
    }

    /**
     * @throws ClientResponseException
     * @throws ServerResponseException
     * @throws MissingParameterException
     */
    public static function buyProduct(int $id, string $customer_id, int $quantity): void
    {
        $search_product_params = [
            'index' => 'shop_products',
            'body' => [
                'query' => [
                    'match' => [
                        'product_id' => $id
                    ]
                ]
            ]
        ];

        $search_response = self::$client->search($search_product_params);

        if ($search_response['hits']['total']['value'] === 1) {
            $old_quantity = $search_response['hits']['hits'][0]['_source']['quantity'];
            $new_quantity = $old_quantity - $quantity;
            if ($new_quantity > 0) {
                $update_params = [
                    'index' => 'shop_products',
                    'id' => $id,
                    'body' => [
                        'doc' => [
                            'quantity' => $new_quantity
                        ]
                    ]
                ];

                ElasticProductsController::updateProduct($update_params);

                $insert_purchase_params = [
                    'index' => 'purchases',
                    'id' => uniqid(),
                    'body' => [
                        'customer_id' => $customer_id,
                        'product_id' => $id,
                        'quantity' => $quantity
                    ]
                ];

                ElasticProductsController::getClient()->index($insert_purchase_params);
            }
        }
    }

    public static function searchForProductByName(string $name): array
    {
        $params = [
            'index' => 'shop_products',
            'body' => [
                'query' => [
                    'match' => [
                        'name' => $name
                    ]
                ]
            ]
        ];

        $response = self::$client->search($params);

        if ($response['hits']['total']['value'] > 0) {
            $found = [];
            $product = [];
            foreach ($response['hits']['hits'] as $hit) {
                $product['product_id'] = $hit['_source']['product_id'];
                $product['name'] = $hit['_source']['name'];
                $product['quantity'] = $hit['_source']['quantity'];
                $product['price'] = $hit['_source']['price'];

                $found[] = $product;
            }

            return $found;
        }

        return [];
    }
}