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

class ValidationException extends Exception
{
    private $errors;

    public function __construct($errors = [], $code = 0, Throwable $previous = null)
    {
        parent::__construct("Validacion fallida", $code, $previous);
        $this->errors = $errors;
    }

    public function getErrors()
    {
        return $this->errors;
    }
}

$app = AppFactory::create();

$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();
$customErrorHandler = function (Request $request, Throwable $exception) use ($app) {
    // var_dump($exception);
    // die;
    $exceptionCode = $exception->getCode();

    if ($exception instanceof CustomException || $exception instanceof HttpException || $exception instanceof ValidationException) {
        $responseCode = $exceptionCode;
    } else {
        $responseCode = 500;
    }

    if ($exception instanceof ValidationException) {
        $payload = ["status" => $responseCode, "errors" => $exception->getErrors()];
    } else {
        $errorMessage = $responseCode === 500 ? "Error de servidor interno" : $exception->getMessage();
        $payload = ['status' => $responseCode, 'error' => $errorMessage];
    }
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
        ->withHeader('Access-Control-Allow-Origin', 'http://localhost:3000')
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'OPTIONS, GET, POST, PUT, PATCH, DELETE')
        ->withHeader('Access-Control-Allow-Credentials', 'true')
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

$authenticate = function ($admin = false) {
    return function (Request $req, $handler) use ($admin) {
        $header = $req->getHeader("Authorization");
        if (empty($header)) {
            throw new CustomException("Autenticación fallida", 401);
        }

        $jwt = explode(' ', $header[0])[1] ?? '';
        $publicKey = file_get_contents('./mykey.pub');
        try {
            $decoded = JWT::decode($jwt, new Key($publicKey, "RS256"));
        } catch (Exception $e) {
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

// ACÁ VAN LOS ENDPOINTS

// $app
//     ->post("/protected", function (Request $req, Response $res) {
//         $res->getBody()->write("Congratulations!");
//         return $res;
//     })
//     ->add($authenticate());

require __DIR__ . '/endpoints/auth.php';
require __DIR__ . '/endpoints/user.php';
require __DIR__ . '/endpoints/game.php';
require __DIR__ . '/endpoints/score.php';
require __DIR__ . '/endpoints/support.php';
require __DIR__ . '/endpoints/platform.php';

$app->run();