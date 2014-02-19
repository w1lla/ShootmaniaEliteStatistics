<?php

namespace ManiaLivePlugins\Shootmania\Elite\Classes;

class RpcObject extends \Maniaplanet\DedicatedServer\Structures\AbstractStructure {
    /**
     * 
     * @param string $json
     */
    public function __construct($json = false) {
        if ($json)
            $this->set(json_decode($json, true));
    }

    public function set($data) {
        foreach ($data AS $key => $value) {
            if (is_array($value)) {
                $sub = new RpcObject();
                $sub->set($value);
                $value = $sub;
            }
            $key = lcfirst($key);
            $this->{$key} = $value;
        }
    }

}

