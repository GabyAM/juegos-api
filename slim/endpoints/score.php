<?php

use Slim\Psr7\Request;
use Slim\Psr7\Response;

$app->get('/juego/{id:[0-9]+}/calificacion', function (Request $req, Response $res, array $args) {
    $gameId = $args["id"];
    $userId = $req->getAttribute("userId");

    $score = findOne("calificacion", ["juego_id = $gameId", "usuario_id = $userId"]);
    if (!isset($score)) {
        throw new CustomException("No se encontró la calificacion", 404);
    }

    $res->getBody()->write(json_encode([
        "status" => 200,
        "data" => $score
    ]));

    return $res;
})->add($authenticate());

$app->post("/calificacion", function (Request $req, Response $res) {
    $data = array_intersect_key(
        $req->getParsedBody() ?? [],
        array_flip(['estrellas', 'juego_id'])
    );

    $errors = validateScore($data);
    if (!empty($errors)) {
        $res
            ->getBody()
            ->write(json_encode(['status' => 400, 'errors' => $errors]));
        return $res->withStatus(400);
    }

    $gameId = $data["juego_id"];
    $game = findOne("juego", "id = $gameId");
    if (!isset($game)) {
        throw new CustomException("No se encontró el juego referenciado", 404);
    }

    $userId = $req->getAttribute("userId");
    $score = findOne("calificacion", ["usuario_id = $userId", "juego_id = $gameId"]);
    if (isset($score)) {
        throw new CustomException("El usuario ya tiene una calificación para ese juego", 409);
    }

    $pdo = createConnection();
    $insertString = buildInsertString([...$data, "usuario_id" => $userId]);
    $sql = "INSERT INTO calificacion " . $insertString;
    $query = $pdo->query($sql);
    $score = $query->fetch(PDO::FETCH_ASSOC);

    $res->getBody()->write(json_encode([
        "status" => 200,
        "message" => "Calificacion creada",
        "data" => $score
    ]));
    return $res;
})->add($authenticate());

$app->put("/calificacion/{id:[0-9]+}", function (Request $req, Response $res, array $args) {
    $data = array_intersect_key(
        $req->getParsedBody() ?? [],
        array_flip(['estrellas'])
    );

    $id = $args["id"];

    $score = findOne("calificacion", "id = " . $id);
    if (!isset($score)) {
        throw new CustomException("No se encontro la calificacion", 404);
    }
    $user = findOne("usuario", "id = " . $score["usuario_id"]);

    if ($req->getAttribute("userId") !== $score["usuario_id"] && !$user["es_admin"]) {
        throw new CustomException("No autorizado, solo el autor de la calificacion o un administrador puede editarla", 401);
    }

    $errors = validateScore($data, true);
    if (!empty($errors)) {
        $res
            ->getBody()
            ->write(json_encode(['status' => 400, 'errors' => $errors]));
        return $res->withStatus(400);
    }

    $pdo = createConnection();
    $sql = 'UPDATE calificacion SET estrellas = :stars WHERE id = :id';
    $query = $pdo->prepare($sql);
    $query->bindValue(':stars', $data["estrellas"]);
    $query->bindValue(':id', $id);
    $query->execute();

    $res->getBody()->write(json_encode([
        "status" => 200,
        "message" => "Calificacion actualizada exitosamente"
    ]));

    return $res;
})->add($authenticate());

$app->delete("/calificacion/{id:[0-9]+}", function (Request $req, Response $res, array $args) {
    $id = $args["id"];

    $score = findOne("calificacion", "id = " . $id);
    if (!isset($score)) {
        throw new CustomException("No se encontro la calificacion", 404);
    }
    $user = findOne("usuario", "id = " . $score["usuario_id"]);

    if ($req->getAttribute("userId") !== $score["usuario_id"] && !$user["es_admin"]) {
        throw new CustomException("No autorizado, solo el autor de la calificacion o un administrador puede eliminarla", 401);
    }

    $pdo = createConnection();
    $sql = "DELETE FROM calificacion WHERE id = $id";
    $pdo->query($sql);

    $res->getBody()->write(json_encode([
        "status" => 200,
        "message" => "Calificacion eliminada exitosamente"
    ]));
})->add($authenticate());