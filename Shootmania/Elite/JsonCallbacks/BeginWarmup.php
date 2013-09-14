<?php

namespace ManiaLivePlugins\Shootmania\Elite\JsonCallbacks;

use ManiaLivePlugins\Shootmania\Elite\Classes\RpcObject;

class BeginWarmup extends RpcObject {

    /** var integer */
    public $timestamp = 0;

    /** var boolean */
    public $allReady = false;

}

