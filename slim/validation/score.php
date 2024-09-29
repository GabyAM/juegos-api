<?php

function validateScore($data, $optional = false) {
    $errors = [];
    $stars = $data["estrellas"] ?? null;
    if(!$optional || isset($stars)) { 
        if(!isset($stars)) {
            $errors["estrellas"] = "Este campo es requerido";
        } else if(!is_int($stars)) {
            $errors["estrellas"] = "Este campo debe ser un numero entero";
        } else if($stars < 1 || $stars > 5) {
            $errors["estrellas"] = "La cantidad de estrellas debe estar entre 1 y 5";
        }
    }

    $gameId = $data["juego_id"] ?? null;
    if(!$optional || isset($gameId)) { 
        if(!isset($gameId)) {
            $errors["juego_id"] = "Este campo es requerido";
        } else if(!is_int($gameId)) {
            $errors["juego_id"] = "Este campo debe ser un numero entero";
        } 
    }
    return $errors;
}