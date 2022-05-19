<?php

namespace App;

final class ParameterCheck {
    private $parameters;
    private $sent_params;

    public function __construct($params = null) {
        $this->sent_params = $params;
        $this->parameters = json_decode(file_get_contents('app/parameters.json'), true);    }

    public function hasRequiredParams($route) {
        if (array_key_exists($route, $this->parameters)) {
            $route_to_check = $this->parameters[$route];
            foreach ($route_to_check as $key => $value) {
                if (array_key_exists($value, $this->sent_params)==false) {
                    return false;
                }
            }
        }
        else    return false;

        return true;
    }

    public function getParameters($route) {
        if (array_key_exists($route, $this->parameters)) {
            return $this->parameters[$route];
        }
        return null;
    }
}
