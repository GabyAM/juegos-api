<?php

function validateUser($data, $optional = false)
{
    $errors = [];
    $userName = $data["nombre_usuario"] ?? null;
    if (!$optional || isset($userName)) {
        if (!isset($userName)) {
            $errors["nombre_usuario"] = "Este campo es requerido";
        }
        if (!is_string($userName)) {
            $errors["nombre_usuario"] = "Este campo debe ser un string";
        } else if (strlen($userName) < 6 || strlen($userName) > 20) {
            $errors["nombre_usuario"] = "El nombre de usuario debe contener entre 6 y 20 caracteres";
        } else if (!ctype_alnum($userName)) {
            $errors["nombre_usuario"] = "El nombre de usuario debe ser un valor alfanumérico";
        }
    }

    $password = $data["clave"] ?? null;
    if (!$optional || isset($password)) {
        if (!isset($password)) {
            $errors["clave"] = "Este campo es requerido";
        } else if (!is_string($password)) {
            $errors["clave"] = "Este campo debe ser un string";
        } else if (strlen($password) < 8) {
            $errors["clave"] = "La clave debe contener al menos 8 caracteres";
        } else if (!preg_match("/^.*(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[!@#$%^&*()_+\-=\[\]{};\':\"\\|,.<>\/?]).*$/", $password)) {
            $errors["clave"] = "La clave debe contener al menos 1 mayúscula, 1 minúscula, 1 número y 1 caracter especial";
        }
    }

    return $errors;
}