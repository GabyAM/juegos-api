<?php

use Firebase\JWT\ExpiredException;
use Firebase\JWT\Key;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Firebase\JWT\JWT;

require __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/utilities/sql_strings.php';
require_once __DIR__ . '/validation/user.php';
require_once __DIR__ . '/validation/score.php';
require_once __DIR__ . '/validation/game.php';
require_once __DIR__ . '/validation/support.php';

class CustomException extends Exception
{
}

$app = AppFactory::create();

$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();
$customErrorHandler = function (Request $request, Throwable $exception) use ($app) {
    $exceptionCode = $exception->getCode();
    if ($exception instanceof CustomException || $exception instanceof HttpException) {
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

$app->options('/{routes:.+}', function ($request, $response, $args) {
    return $response;
});
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

$authenticate = function ($admin = false, $optional = false) {
    return function (Request $req, $handler) use ($admin, $optional) {
        $header = $req->getHeader("Authorization");
        if (empty($header)) {
            if ($optional) {
                $req = $req->withAttribute("userId", null);
                return $handler->handle($req);
            }
            throw new CustomException("Autenticación fallida", 401);
        }

        $jwt = explode(' ', $header[0])[1] ?? '';
        $publicKey = file_get_contents('./mykey.pub');
        try {
            $decoded = JWT::decode($jwt, new Key($publicKey, "RS256"));
        } catch (Exception $e) {
            if ($optional) {
                $req = $req->withAttribute("userId", null);
                return $handler->handle($req);
            }
            if ($e instanceof ExpiredException) {
                throw new CustomException("El token expiró", 401);
            } else {
                throw new CustomException("El token no es válido", 401);
            }
        }

        $user = (array) $decoded->user;
        if ($admin && !$user["es_admin"]) {
            throw new CustomException("El usuario debe ser admin", 401);
        }

        $req = $req->withAttribute("userId", $user["id"]);
        $res = $handler->handle($req);
        return $res;
    };
};

require __DIR__ . '/endpoints/auth.php';
require __DIR__ . '/endpoints/user.php';
require __DIR__ . '/endpoints/game.php';
require __DIR__ . '/endpoints/score.php';
require __DIR__ . '/endpoints/support.php';

$app->run();