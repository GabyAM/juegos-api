<?php
use Slim\Psr7\Request;
use Slim\Psr7\Response;

$app->get("/juegos", function (Request $req, Response $res) {

    $params = array_intersect_key(
        $req->getQueryParams() ?? [],
        array_flip([
            'pagina',
            'clasificacion',
            'texto',
            'plataforma',
        ])
    );

    $errors = validateGameParams($params);
    if (!empty($errors)) {
        $res
            ->getBody()
            ->write(json_encode(['status' => 400, 'errors' => $errors]));
        return $res->withStatus(400);
    }

    $pdo = createConnection();
    $conditions = [];
    if (isset($params["texto"])) {
        array_push($conditions, "juego.nombre LIKE '%" . $params["texto"] . "%'");
    }
    if (isset($params["clasificacion"])) {
        array_push($conditions, "juego.clasificacion_edad = '" . $params["clasificacion"] . "'");
    }
    $sql = "SELECT juego.id, juego.nombre, juego.descripcion, juego.imagen, juego.clasificacion_edad, 
    (SELECT AVG(estrellas) FROM calificacion WHERE juego_id = juego.id) as promedio_calificaciones, 
    GROUP_CONCAT(plataforma.nombre ORDER BY plataforma.nombre ASC SEPARATOR ', ') as plataformas 
    FROM juego LEFT JOIN soporte ON soporte.juego_id = juego.id LEFT JOIN plataforma on soporte.plataforma_id = plataforma.id
    " . (isset($params["plataforma"]) ? "WHERE plataforma.nombre = '" . $params["plataforma"] . "'" : "") . "
    GROUP BY juego.id
    " . (!empty($conditions) ? "HAVING " . implode(" AND ", $conditions) : "") . "
    LIMIT 5" . (isset($params["pagina"]) ? " OFFSET " . ((intval($params["pagina"]) - 1) * 5) : "");

    $query = $pdo->prepare($sql);
    $query->execute();
    $games = $query->fetchAll(PDO::FETCH_ASSOC);

    $res->getBody()->write(json_encode([
        "status" => 200,
        "data" => $games
    ]));
    return $res;
});

$app->get("/juegos/{id:[0-9]+}", function (Request $req, Response $res, array $args) {
    $id = $args["id"];

    $game = findOne("juego", "id = " . $id);
    if (!isset($game)) {
        throw new CustomException("Juego no encontrado", 404);
    }

    $pdo = createConnection();
    $sql = "SELECT * FROM calificacion WHERE juego_id = :id";
    $query = $pdo->prepare($sql);
    $query->bindValue(":id", $id);
    $query->execute();
    $scores = $query->fetchAll(PDO::FETCH_ASSOC);

    $game["calificaciones"] = $scores;
    $res->getBody()->write(json_encode([
        "status" => 200,
        "data" => $game
    ]));
    return $res;
});

$app->post("/juego", function (Request $req, Response $res) {
    $data = array_intersect_key(
        $req->getParsedBody() ?? [],
        array_flip([
            'nombre',
            'descripcion',
            'clasificacion_edad',
        ])
    );

    $data["imagen"] = $req->getUploadedFiles()["imagen"] ?? null;

    $errors = validateGame($data);
    if (!empty($errors)) {
        $res
            ->getBody()
            ->write(json_encode(['status' => 400, 'errors' => $errors]));
        return $res->withStatus(400);
    }

    $newName = $data["nombre"];
    $game = findOne("juego", "nombre = '$newName'");
    if (isset($game)) {
        throw new CustomException('Ya existe un juego con ese nombre', 409);
    }

    $data["imagen"] = base64_encode($data["imagen"]->getStream()->getContents());

    $pdo = createConnection();
    $insertString = buildInsertString($data);
    $sql = "INSERT INTO juego " . $insertString;
    $pdo->query($sql);

    $res->getBody()->write(json_encode([
        "status" => 200,
        "message" => "Juego añadido exitosamente"
    ]));
    return $res;
})->add($authenticate(true));

$app->put("/juego/{id:[0-9]+}", function (Request $req, Response $res, array $args) {
    $id = $args["id"];
    $data = array_intersect_key(
        $req->getParsedBody() ?? [],
        array_flip([
            'nombre',
            'descripcion',
            'clasificacion_edad',
        ])
    );
    if (isset($req->getUploadedFiles()["imagen"])) {
        $data["imagen"] = $req->getUploadedFiles()["imagen"]->getStream()->getContents();
    }

    $errors = validateGame($data, true);
    if (!empty($errors)) {
        $res
            ->getBody()
            ->write(json_encode(['status' => 400, 'errors' => $errors]));
        return $res->withStatus(400);
    }

    $game = findOne("juego", "id = $id");
    if (!isset($game)) {
        throw new CustomException("Juego no encontrado", 404);
    }

    if (isset($data["nombre"])) {
        $newName = $data["nombre"];
        $game = findOne("juego", ["nombre = '$newName'", "id != $id"]);
        if (isset($game)) {
            throw new CustomException('Ya existe un juego con ese nombre', 409);
        }
    }

    if (isset($data["imagen"])) {
        $data["imagen"] = base64_encode($data["imagen"]);
    }

    $pdo = createConnection();
    $updatesString = buildUpdateString($data);
    $sql = 'UPDATE juego SET ' . $updatesString . " WHERE id = $id";
    $pdo->query($sql);

    $res->getBody()->write(json_encode([
        "status" => 200,
        "message" => "Juego añadido exitosamente"
    ]));
    return $res;
})->add($authenticate(true));

$app->delete("/juego/{id:[0-9]+}", function (Request $req, Response $res, array $args) {
    $id = $args["id"];
    $game = findOne("juego", "id = " . $id);
    if (!isset($game)) {
        throw new CustomException("Juego no encontrado", 404);
    }

    $pdo = createConnection();

    $sql = "SELECT * FROM calificacion WHERE juego_id = $id";
    $query = $pdo->query($sql);
    if ($query->rowCount() > 0) {
        throw new CustomException("No se puede eliminar el juego porque hay calificaciones que lo referencian", 409);
    }

    $sql = "SELECT * FROM soporte WHERE juego_id = $id";
    $query = $pdo->query($sql);
    if ($query->rowCount() > 0) {
        throw new CustomException("No se puede eliminar el juego porque hay soportes que lo referencian", 409);
    }

    $sql = "DELETE FROM juego WHERE id = $id";
    $pdo->query($sql);

    $res->getBody()->write(json_encode([
        "status" => 200,
        "message" => "Juego eliminado exitosamente"
    ]));
    return $res;
});