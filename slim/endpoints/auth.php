<?php
use Slim\Psr7\Request;
use Slim\Psr7\Response;

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
        throw new CustomException("Usuario no encontrado", 404);
    }
    if ($user["clave"] !== $data["clave"]) {
        throw new CustomException("Contraseña incorrecta", 400);
    }

    $token = $user["id"] . "::" . bin2hex(random_bytes(30));
    $expiresAt = time() + 3600;

    $update = ["token" => $token, "vencimiento_token" => date('Y-m-d H:i:s', $expiresAt)];
    $updateString = buildUpdateString($update);

    $pdo = createConnection();
    $id = $user["id"];
    $sql = 'UPDATE usuario SET ' . $updateString . " WHERE id = $id";
    $pdo->query($sql);

    $res->getBody()->write(json_encode(
        [
            "status" => 200,
            "message" => "Login realizado exitosamente",
            "token" => $token
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