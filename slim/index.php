<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

require __DIR__ . '/vendor/autoload.php';
class CustomException extends Exception
{
}

$app = AppFactory::create();

$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();
$customErrorHandler = function (Request $request, Throwable $exception) use ($app) {
    $exceptionCode = $exception->getCode();
    if ($exception instanceof CustomException) {
        $responseCode = $exceptionCode;
    } else {
        $responseCode = 500;
    }

    $errorMessage = $responseCode === 500 ? "Error de servidor interno" : $exception->getMessage();
    $payload = ['status' => $responseCode, 'error' => $errorMessage];
    $response = $app->getResponseFactory()->createResponse();
    $response->getBody()->write(json_encode($payload, JSON_UNESCAPED_UNICODE));

    return $response->withStatus($responseCode);
};

$errorMiddleware = $app->addErrorMiddleware(true, true, true);
$errorMiddleware->setDefaultErrorHandler($customErrorHandler);
$app->add(function ($request, $handler) {
    $response = $handler->handle($request);

    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'OPTIONS, GET, POST, PUT, PATCH, DELETE')
        ->withHeader('Content-Type', 'application/json')
    ;
});

function createConnection()
{
    $dsn = 'mysql:host=db;dbname=seminariophp';
    $username = 'seminariophp';
    $password = 'seminariophp';

    return new PDO($dsn, $username, $password);
}
;

function findOne($table, $conditions, $select = "*")
{
    $pdo = createConnection();

    if (is_array($conditions)) {
        $conditionsString = implode(" AND ", $conditions);
    } else
        $conditionsString = $conditions;

    $sql = "SELECT " . $select . " FROM " . $table . " WHERE " . $conditionsString;
    $query = $pdo->query($sql);
    $value = null;
    if ($query->rowCount() === 1) {
        $value = $query->fetch(PDO::FETCH_ASSOC);
    } else if ($query->rowCount() > 1) {
        throw new CustomException("Se encontraron multiples resultados para una busqueda única", 500);
    }

    unset($pdo);
    return $value;
}

$app->run();