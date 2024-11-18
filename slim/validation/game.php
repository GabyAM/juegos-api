<?php
function validateGameParams($params)
{
    $errors = [];

    if (isset($params["pagina"])) {
        if (!preg_match('/^[1-9][0-9]*$/', $params["pagina"])) {
            $errors["pagina"] = "El parametro de pagina debe ser un numero entero";
        }
    }

    if (isset($params["plataforma"])) {
        if (!preg_match("/^(PC|PS|XBOX|Android|Otro)$/", $params["plataforma"])) {
            $errors["plataforma"] = "El parametro de plataforma debe indicar una plataforma valida (PC, XBOX, PS, Android, Otro)";
        }
    }

    if (isset($params["clasificacion"])) {
        if (!preg_match("/^(ATP|\+13|\+18)$/", $params["clasificacion"])) {
            $errors["clasificacion"] = "El parametro de clasificacion debe indicar una clasificacion valida (ATP, +13, +18)";
        }
    }

    return $errors;
}

function validateGame($data, $optional = false)
{
    $errors = [];
    $name = $data["nombre"] ?? null;
    if (!$optional || isset($name)) {
        if (!isset($name)) {
            $errors["nombre"] = "Este campo es requerido";
        } else if (!is_string($name)) {
            $errors["nombre"] = "Este campo debe ser un string";
        } else if (strlen($name) > 45) {
            $errors["nombre_usuario"] = "El nombre debe contener a lo sumo 45 caracteres";
        }
    }

    $description = $data["descripcion"] ?? null;
    if (!$optional || isset($description)) {
        if (!isset($description)) {
            $errors["descripcion"] = "Este campo es requerido";
        } else if (!is_string($description)) {
            $errors["descripcion"] = "Este campo debe ser un string";
        } else if (strlen($description) === 0) {
            $errors["descripcion"] = "Este campo no puede estar vacio";
        }
    }

    $image = $data["imagen"] ?? null;
    if (!$optional || isset($image)) {
        if (!isset($image)) {
            $errors["imagen"] = "Este campo es requerido";
        } else if ($image->getSize() > 5 * 1024 * 1024) {
            $errors["imagen"] = "La imagen no puede pesar mas de 5MB";
        } else {
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mimetype = $finfo->buffer($image->getStream()->getContents());
            if (!preg_match("/^image\/(jpeg|png|webp|gif)$/", $mimetype)) {
                $errors["imagen"] = "Solo se admiten imagenes";
            }
        }
    }

    $ageRating = $data["clasificacion_edad"] ?? null;
    if (!$optional || isset($ageRating)) {
        if (!isset($ageRating)) {
            $errors["clasificacion_edad"] = "Este campo es requerido";
        } else if (!is_string($ageRating)) {
            $errors["clasificacion_edad"] = "Este campo debe ser un string";
        } else if (strlen($ageRating) === 0) {
            $errors["clasificacion_edad"] = "Este campo no puede estar vacio";
        } else if (!preg_match("/^(ATP|\+13|\+18)$/", $ageRating)) {
            $errors["clasificacion_edad"] = "Este campo debe indicar una clasificacion valida (ATP, +13, +18)";
        }
    }
    return $errors;
}