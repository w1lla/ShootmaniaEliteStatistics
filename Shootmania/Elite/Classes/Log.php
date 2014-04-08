<?php

namespace ManiaLivePlugins\Shootmania\Elite\Classes;

class Log {

    private $serverLogin;
	
	
    function __construct($serverLogin) {
        $this->serverLogin = $serverLogin;
		$this->config = Config::getInstance();
    }

    public function Normal($message) {
	if ($this->config->Normal == true)
	{
	try
	{
        \ManiaLive\Utilities\Logger::log($message, true, "ElitePlugin_Normal_Log-'" . $this->serverLogin . "'");
	}
	catch(\Exception $e)
	{
	    \ManiaLive\Utilities\Logger::log($message, true, "ElitePlugin_Normal_Log-'" . $this->serverLogin . "'");
	}	
	}
	else 
	{
	try
	{
        \ManiaLive\Utilities\Logger::log($message, true, "ElitePlugin_Normal_Log-'" . $this->serverLogin . "'");
	}
	catch(\Exception $e)
	{
	    \ManiaLive\Utilities\Logger::log($message, true, "ElitePlugin_Normal_Log-'" . $this->serverLogin . "'");
	}	
	}
    }
	
	public function Spectator($message) {
	if ($this->config->Spectator == true)
	{
	try
	{
        \ManiaLive\Utilities\Logger::log($message, true, "ElitePlugin_Spectator_Log-'" . $this->serverLogin . "'");
	}
	catch(\Exception $e)
	{
	    \ManiaLive\Utilities\Logger::log($message, true, "ElitePlugin_Spectator_Log-'" . $this->serverLogin . "'");
	}	
	}
	else 
	{
	try
	{
        \ManiaLive\Utilities\Logger::log($message, true, "ElitePlugin_Spectator_Log-'" . $this->serverLogin . "'");
	}
	catch(\Exception $e)
	{
	    \ManiaLive\Utilities\Logger::log($message, true, "ElitePlugin_Spectator_Log-'" . $this->serverLogin . "'");
	}	
	}
    }

    public function Console($message) {
	if ($this->config->Console == true)
	{
	try
	{
        \ManiaLive\Utilities\Logger::log($message, true, "ElitePlugin_Console_Log-'" . $this->serverLogin . "'");
	}
	catch(\Exception $e)
	{
	    \ManiaLive\Utilities\Logger::log($message, true, "ElitePlugin_Console_Log-'" . $this->serverLogin . "'");
	}	
	}
	else 
	{
	try
	{
        \ManiaLive\Utilities\Logger::log($message, true, "ElitePlugin_Console_Log-'" . $this->serverLogin . "'");
	}
	catch(\Exception $e)
	{
	    \ManiaLive\Utilities\Logger::log($message, true, "ElitePlugin_Console_Log-'" . $this->serverLogin . "'");
	}	
	}
    }
	
	public function Debug($message) {
    if ($this->config->Debug == true)
	{
	try
	{
        \ManiaLive\Utilities\Logger::log($message, true, "ElitePlugin_Debug_Log-'" . $this->serverLogin . "'");
	}
	catch(\Exception $e)
	{
	    \ManiaLive\Utilities\Logger::log($message, true, "ElitePlugin_Debug_Log-'" . $this->serverLogin . "'");
	}	
	}
	else 
	{
	try
	{
        \ManiaLive\Utilities\Logger::log($message, true, "ElitePlugin_Debug_Log-'" . $this->serverLogin . "'");
	}
	catch(\Exception $e)
	{
	    \ManiaLive\Utilities\Logger::log($message, true, "ElitePlugin_Debug_Log-'" . $this->serverLogin . "'");
	}	
	}
    }
	
	public function Callbacks($message) {
    if ($this->config->Callbacks == true)
	{
	try
	{
        \ManiaLive\Utilities\Logger::log($message, true, "ElitePlugin_Callbacks_Log-'" . $this->serverLogin . "'");
	}
	catch(\Exception $e)
	{
	    \ManiaLive\Utilities\Logger::log($message, true, "ElitePlugin_Callbacks_Log-'" . $this->serverLogin . "'");
	}	
	}
	else 
	{
	try
	{
        \ManiaLive\Utilities\Logger::log($message, true, "ElitePlugin_Callbacks_Log-'" . $this->serverLogin . "'");
	}
	catch(\Exception $e)
	{
	    \ManiaLive\Utilities\Logger::log($message, true, "ElitePlugin_Callbacks_Log-'" . $this->serverLogin . "'");
	}	
	}
    }

}