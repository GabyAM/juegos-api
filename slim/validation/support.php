<?php

function validateSupport($data)
{
    $errors = [];
    $platformId = $data["plataforma_id"] ?? null;
    if (!isset($platformId)) {
        $errors["plataforma_id"] = "Este campo es requerido";
    } else if (!is_int($platformId)) {
        $errors["plataforma_id"] = "Este campo debe ser un numero entero";
    }

    $gameId = $data["juego_id"] ?? null;
    if (!isset($gameId)) {
        $errors["juego_id"] = "Este campo es requerido";
    } else if (!is_int($gameId)) {
        $errors["juego_id"] = "Este campo debe ser un numero entero";
    }
    return $errors;
}