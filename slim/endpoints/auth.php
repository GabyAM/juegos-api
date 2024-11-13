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
        $res
            ->getBody()
            ->write(json_encode(['status' => 400, 'errors' => $errors]));
        return $res->withStatus(400);
    }

    $userName = $data["nombre_usuario"];
    $user = findOne("usuario", "nombre_usuario = '$userName'");
    if (!isset($user)) {
        throw new CustomException("Nombre de usuario incorrecto", 400);
    }
    if ($user["clave"] !== $data["clave"]) {
        throw new CustomException("Contraseña incorrecta", 400);
    }

    $payloadUser = [
        "id" => $user["id"],
        "nombre_usuario" => $user["nombre_usuario"],
        "es_admin" => $user["es_admin"]
    ];
    $payload = [
        "exp" => time() + 3600,
        "iat" => time(),
        "user" => $payloadUser
    ];
    $privateKey = file_get_contents(__DIR__ . "/../mykey.pem");

    $jwt = JWT::encode($payload, $privateKey, "RS256");

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
        $res
            ->getBody()
            ->write(json_encode(['status' => 400, 'errors' => $errors]));
        return $res->withStatus(400);
    }

    $userName = $data["nombre_usuario"];
    $user = findOne("usuario", "nombre_usuario = '$userName'");
    if (isset($user)) {
        throw new CustomException("El nombre de usuario está en uso", 409);
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