<?php
use Slim\Psr7\Request;
use Slim\Psr7\Response;

$app->post("/soporte", function (Request $req, Response $res) {
    $data = array_intersect_key(
        $req->getParsedBody() ?? [],
        array_flip(['plataforma_id', 'juego_id'])
    );

    $errors = validateSupport($data);
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

    $platformId = $data["plataforma_id"];
    $platform = findOne("plataforma", "id = $platformId");
    if (!isset($platform)) {
        throw new CustomException("No se encontró la plataforma referenciada", 404);
    }

    $support = findOne("soporte", ["juego_id = $gameId", "platform_id = $platformId"]);
    if (isset($support)) {
        throw new CustomException("Ya existe el soporte para el juego en esa plataforma", 409);
    }

    $pdo = createConnection();
    $insertString = buildInsertString($data);
    $sql = "INSERT INTO soporte " . $insertString;
    $pdo->query($sql);

    $res->getBody()->write(json_encode([
        "status" => 200,
        "message" => "Soporte creado"
    ]));
    return $res;

})->add($authenticate(true));