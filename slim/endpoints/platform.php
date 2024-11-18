<?php

use Slim\Psr7\Request;
use Slim\Psr7\Response;

$app->get('/plataformas', function (Request $req, Response $res, array $args) {
    $pdo = createConnection();
    $sql = "SELECT * FROM plataforma";

    $query = $pdo->query($sql);
    $platforms = $query->fetchAll(PDO::FETCH_ASSOC);

    $res->getBody()->write(json_encode([
        "status" => 200,
        "data" => $platforms
    ]));

    return $res;
});