<?php

namespace ManiaLivePlugins\Shootmania\Elite\Classes;

class Log {

    private $serverLogin;

    function __construct($serverLogin) {
        $this->serverLogin = $serverLogin;
    }

    public function write($message) {
        \ManiaLive\Utilities\Logger::log($message, true, "ElitePlugin-'" . $this->serverLogin . "'");
    }

    public function debug($message) {
        \ManiaLive\Utilities\Logger::debug($message, true, "ElitePluginDebug-'" . $this->serverLogin . "'");
    }

}