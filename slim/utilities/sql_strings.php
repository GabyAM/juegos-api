<?php
function  buildInsertString($values): string {
    $fieldsString = '';
    $valuesString = '';
    $i = 0;
    foreach ($values as $key => $value) {
        $fieldsString .= $key;
        if (is_bool($value)) {
            $valuesString .= $value ? 'true' : 'false';
        } elseif (is_string($value)) {
            $valuesString .= '"'.$value.'"';
        } else {
            $valuesString .= $value;
        }
        if ($i < count($values) - 1) {
            $fieldsString .= ', ';
            $valuesString .= ', ';
            $i++;
        }
    }

    return '(' . $fieldsString . ') VALUES (' . $valuesString . ')';
}

function buildUpdateString($values): string {
    $updatesString = '';
    $i = 0;
    foreach ($values as $key => $value) {
        $updatesString .= $key . ' = ';
        if (is_bool($value)) {
            $updatesString .= $value ? 'true' : 'false';
        } elseif (is_string($value)) {
            $updatesString .= '"' . $value . '"';
        } else {
            $updatesString .= $value;
        }
        if ($i < count($values) - 1) {
            $updatesString .= ', ';
            $i++;
        }
    }

    return $updatesString;
}