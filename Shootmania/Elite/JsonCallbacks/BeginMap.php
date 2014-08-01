<?php

namespace ManiaLivePlugins\Shootmania\Elite\JsonCallbacks;

use ManiaLivePlugins\Shootmania\Elite\Classes\RpcObject;

class BeginMap extends RpcObject {

    /** var integer */
    public $timestamp;

    /** var integer */
    public $mapNumber;
	
	/** var boolean */
    public $mapRestart = false;
}

