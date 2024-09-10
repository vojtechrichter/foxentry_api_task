<?php

namespace FoxentryApiTask;

use Elastic\Elasticsearch\ClientBuilder;
use Elastic\Elasticsearch\Exception\AuthenticationException;
use Elastic\Elasticsearch\Exception\ElasticsearchException;
use FoxentryApiTask\CustomerInterface\CustomerController;
use FoxentryApiTask\ProductRegistry\ProductRegistryController;
use FoxentryApiTask\ProductStorage\ElasticProductsController;
use Slim\Factory\AppFactory;

require(__DIR__ . '/../vendor/autoload.php');

try {
    ElasticProductsController::init();
} catch (AuthenticationException $e) {
    die($e->getMessage());
}

$app = AppFactory::create();
$app->addErrorMiddleware(true, true, true);

$app->get('/products', ProductRegistryController::class . ':getProducts');
$app->get('/product/{id}', ProductRegistryController::class . ':getProduct');
$app->delete('/product/{id}', ProductRegistryController::class . ':deleteProduct');
$app->post('/product', ProductRegistryController::class . ':insertProduct');
$app->put('/product/{id}', ProductRegistryController::class . ':updateProduct');
$app->post('/buy/{id}', CustomerController::class . ':buyProduct');
$app->get('/generate_id', CustomerController::class . ':generateId');
$app->get('/purchases', ProductRegistryController::class . ':getPurchases');
$app->get('/search/{name}', ProductRegistryController::class . ':searchByName');

$app->run();
