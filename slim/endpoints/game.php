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
        if ($params["clasificacion"] === 'ATP') {
            $condition = "juego.clasificacion_edad = 'ATP'";
        } else if ($params["clasificacion"] === '+13') {
            $condition = "juego.clasificacion_edad = 'ATP' OR juego.clasificacion_edad = '+13'";
        } else if ($params["clasificacion"] === '+18') {
            $condition = "juego.clasificacion_edad = 'ATP' OR juego.clasificacion_edad = '+13' OR juego.clasificacion_edad = '+18'";
        }
        array_push($conditions, $condition);
    }
    if (isset($params["plataforma"])) {
        array_push($conditions, "plataforma.nombre = '" . $params["plataforma"] . "'");
    }

    $whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

    $sql = "SELECT juego.id, juego.nombre, juego.descripcion, juego.imagen, juego.clasificacion_edad, 
    (SELECT AVG(estrellas) FROM calificacion WHERE juego_id = juego.id) as promedio_calificaciones,
    (SELECT COUNT(id) FROM calificacion WHERE juego_id = juego.id) as cantidad_calificaciones,  
    GROUP_CONCAT(plataforma.nombre ORDER BY plataforma.nombre ASC SEPARATOR ', ') as plataformas 
    FROM juego LEFT JOIN soporte ON soporte.juego_id = juego.id LEFT JOIN plataforma on soporte.plataforma_id = plataforma.id
    {$whereClause}
    GROUP BY juego.id
    LIMIT 5" . (isset($params["pagina"]) ? " OFFSET " . ((intval($params["pagina"]) - 1) * 5) : "");

    $query = $pdo->query($sql);
    $games = $query->fetchAll(PDO::FETCH_ASSOC);

    $sql = "SELECT COUNT(*) as count FROM (
    SELECT juego.id  
    FROM juego LEFT JOIN soporte ON soporte.juego_id = juego.id LEFT JOIN plataforma on soporte.plataforma_id = plataforma.id
    {$whereClause}
    GROUP BY juego.id, juego.nombre
    ) as list";

    $query = $pdo->query($sql);
    $count = $query->fetch(PDO::FETCH_ASSOC)["count"];
    $pages = ceil($count / 5);

    $res->getBody()->write(json_encode([
        "status" => 200,
        "data" => [
            "pages" => $pages,
            "results" => $games
        ]
    ]));
    return $res;
});

$app->get("/juegos/{id:[0-9]+}", function (Request $req, Response $res, array $args) {
    $id = $args["id"];

    $pdo = createConnection();
    $sql = "SELECT juego.id, juego.nombre, juego.descripcion, juego.clasificacion_edad, juego.imagen, 
    GROUP_CONCAT(plataforma.nombre ORDER BY plataforma.nombre ASC SEPARATOR ', ') as plataformas 
    FROM juego
    LEFT JOIN soporte ON soporte.juego_id = juego.id
    LEFT JOIN plataforma ON plataforma.id = soporte.plataforma_id
    GROUP BY juego.id
    HAVING juego.id = $id";

    $query = $pdo->query($sql);
    $game = $query->fetch(PDO::FETCH_ASSOC);

    if (!isset($game)) {
        throw new CustomException("Juego no encontrado", 404);
    }

    $pdo = createConnection();
    $sql = "SELECT calificacion.id, calificacion.estrellas, calificacion.usuario_id, usuario.nombre_usuario FROM calificacion 
    JOIN usuario ON usuario.id = calificacion.usuario_id 
    WHERE juego_id = $id
    ORDER BY usuario.nombre_usuario ASC";
    $query = $pdo->query($sql);
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
            "imagen"
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

    $pdo = createConnection();
    $sql = "SELECT * FROM juego WHERE nombre = :nombre";
    $query = $pdo->prepare($sql);
    $query->bindParam(":nombre", $data["nombre"]);
    $query->execute();

    if ($query->rowCount() > 0) {
        throw new CustomException('Ya existe un juego con ese nombre', 409);
    }

    try {
        $imageStream = $data["imagen"]->getStream();
        $imageStream->rewind();
        $imageData = '';
        while (!$imageStream->eof()) {
            $imageData .= $imageStream->read(8192);
        }
        $data["imagen"] = base64_encode($imageData);
    } catch (Exception $e) {
        throw new CustomException('Error al procesar la imagen', 500);
    }

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
        try {
            $imageStream = $data["imagen"]->getStream();
            $imageStream->rewind();
            $imageData = '';
            while (!$imageStream->eof()) {
                $imageData .= $imageStream->read(8192);
            }
            $data["imagen"] = base64_encode($imageData);
        } catch (Exception $e) {
            throw new CustomException('Error al procesar la imagen', 500);
        }
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