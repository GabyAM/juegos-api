<?php
use Slim\Psr7\Request;
use Slim\Psr7\Response;

$app->get("/usuario/{id:[0-9]+}", function (Request $req, Response $res, array $args) {
    $id = $args["id"];

    $user = findOne("usuario", "id = $id", "id, nombre_usuario, es_admin");
    if (!isset($user)) {
        throw new CustomException("Usuario no encontrado", 404);
    }

    $res->getBody()->write(json_encode([
        "status" => 200,
        "data" => $user
    ]));
    return $res;
})->add($authenticate());


$app->put("/usuario/{id:[0-9]+}", function (Request $req, Response $res, array $args) {
    $data = array_intersect_key(
        $req->getParsedBody() ?? [],
        array_flip(['nombre_usuario', 'clave'])
    );

    $id = $args["id"];

    if ($req->getAttribute("userId") !== intval($id)) {
        throw new CustomException("No autorizado", 401);
    }

    $errors = validateUser($data, true);

    if (!empty($errors)) {
        $res
            ->getBody()
            ->write(json_encode(['status' => 400, 'errors' => $errors]));
        return $res->withStatus(400);
    }

    $updatesString = buildUpdateString($data);
    $sql = 'UPDATE usuario SET ' . $updatesString . " WHERE id = $id";
    $pdo = createConnection();
    $pdo->query($sql);

    $res->getBody()->write(json_encode([
        "status" => 200,
        "message" => "Usuario actualizado exitosamente"
    ]));

    return $res;
})->add($authenticate());

$app->delete("/usuario/{id:[0-9]+}", function (Request $req, Response $res, array $args) {
    $id = $args["id"];

    if ($req->getAttribute("userId") !== intval($id)) {
        throw new CustomException("No autorizado", 401);
    }

    $pdo = createConnection();

    $sql = "SELECT * FROM calificacion WHERE usuario_id = $id";
    $query = $pdo->query($sql);
    if ($query->rowCount() > 0) {
        throw new CustomException("No se puede eliminar el usuario porque hay clasificaciones que lo referencian", 409);
    }

    $sql = "SELECT * FROM usuario WHERE id = $id";
    $query = $pdo->query($sql);
    if ($query->rowCount() === 0) {
        throw new CustomException("Usuario no encontrado", 404);
    }

    $sql = "DELETE FROM usuario WHERE id = $id";
    $pdo->query($sql);

    $res->getBody()->write(json_encode([
        "status" => 200,
        "message" => "Usuario eliminado exitosamente"
    ]));
    return $res;
})->add($authenticate());