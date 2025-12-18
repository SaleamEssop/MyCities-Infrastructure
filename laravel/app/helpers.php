<?php

if (! function_exists('validateData')) {

    function validateData($required, $data) {
        foreach ($required as $field) {
            if(empty($data[$field])) {
                return ['status' => false, 'error' => "Required '$field' field is missing."];
            }
        }
        return ['status' => true, 'error' => ""];
    }
}
