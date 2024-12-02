<?php
use Slim\Psr7\Request;
use Slim\Psr7\Response;
use Firebase\JWT\JWT;

$app->post('/login', function (Request $req, Response $res) {
    $data = array_intersect_key(
        $req->getParsedBody() ?? [],
        array_flip(['nombre_usuario', 'clave'])
    );

    $errors = validateUser($data);

    if (!empty($errors)) {
        throw new ValidationException($errors, 400);
    }

    $userName = $data["nombre_usuario"];
    $user = findOne("usuario", "nombre_usuario = '$userName'");
    if (!isset($user)) {
        throw new ValidationException(["nombre_usuario" => "Nombre de usuario incorrecto"], 400);
    }
    if ($user["clave"] !== $data["clave"]) {
        throw new ValidationException(["clave" => "Contraseña incorrecta"], 400);
    }

    $userId = $user["id"];
    $currentTime = time();
    $expireTime = $currentTime + 3600;
    $payloadUser = [
        "id" => $userId,
        "nombre_usuario" => $user["nombre_usuario"],
        "es_admin" => $user["es_admin"],
    ];
    $payload = [
        "exp" => $expireTime,
        "iat" => $currentTime,
        "user" => $payloadUser
    ];
    $privateKey = file_get_contents(__DIR__ . "/../mykey.pem");

    $jwt = JWT::encode($payload, $privateKey, "RS256");

    $expireDate = date('Y-m-d H:i:s', $expireTime);
    $pdo = createConnection();
    $sql = "UPDATE usuario SET vencimiento_token = '$expireDate', token = '$jwt' WHERE id = $userId";
    $pdo->query($sql);

    $res->getBody()->write(json_encode(
        [
            "status" => 200,
            "message" => "Login realizado exitosamente",
            "token" => $jwt
        ]
    ));
    return $res;
});

$app->post('/register', function (Request $req, Response $res) {
    $data = array_intersect_key(
        $req->getParsedBody() ?? [],
        array_flip(['nombre_usuario', 'clave'])
    );

    $errors = validateUser($data);

    if (!empty($errors)) {
        throw new ValidationException($errors, 400);
    }

    $userName = $data["nombre_usuario"];
    $user = findOne("usuario", "nombre_usuario = '$userName'");
    if (isset($user)) {
        throw new ValidationException(["nombre_usuario" => "El nombre de usuario está en uso"], 409);
    }

    $insertString = buildInsertString($data);
    $sql = "INSERT INTO usuario " . $insertString;
    $pdo = createConnection();
    $pdo->query($sql);

    $res->getBody()->write(json_encode([
        "status" => 200,
        "message" => "Usuario creado exitosamente"
    ]));
    return $res;
});