<?php

namespace FoxentryApiTask;

use Elastic\Elasticsearch\Exception\AuthenticationException;
use FoxentryApiTask\CustomerInterface\CustomerController;
use FoxentryApiTask\OpenapiSpec\OpenApiSpecController;
use FoxentryApiTask\ProductRegistry\ProductRegistryController;
use FoxentryApiTask\ProductStorage\ElasticProductsController;
use Slim\Factory\AppFactory;

require(__DIR__ . '/../vendor/autoload.php');

error_reporting(0);

try {
    ElasticProductsController::init();
} catch (AuthenticationException $e) {
    die($e->getMessage());
}

$app = AppFactory::create();

$app->get('/products', ProductRegistryController::class . ':getProducts');
$app->get('/product/{id}', ProductRegistryController::class . ':getProduct');
$app->delete('/product/{id}', ProductRegistryController::class . ':deleteProduct');
$app->post('/product', ProductRegistryController::class . ':insertProduct');
$app->put('/product/{id}', ProductRegistryController::class . ':updateProduct');
$app->post('/buy/{id}', CustomerController::class . ':buyProduct');
$app->get('/generate_id', CustomerController::class . ':generateId');
$app->get('/purchases', ProductRegistryController::class . ':getPurchases');
$app->get('/search/{name}', ProductRegistryController::class . ':searchByName');
$app->get('/api_spec', OpenApiSpecController::class . ':renderOpenApiSpec');

$app->run();
