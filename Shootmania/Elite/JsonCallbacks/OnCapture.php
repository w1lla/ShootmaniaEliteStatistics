<?php

namespace ManiaLivePlugins\Shootmania\Elite\JsonCallbacks;

use ManiaLivePlugins\Shootmania\Elite\Classes\RpcObject;

class OnCapture extends RpcObject {

    /** @var integer */
    public $timestamp = 0;

    /** @var integer */
    public $turnNumber = 0;

    /** @var integer */
    public $startTime = 0;

    /** @var integer */
    public $endTime = 0;

    /** @var integer */
    public $poleTime = 0;

    /** @var Event_OnCapture */
    public $event;

}

