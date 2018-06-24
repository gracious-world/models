<?php

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

/**
 * 各任务基类
 *
 * @author Winter
 */
class ActivityBaseTask extends BaseTask {

    public static function addTask($sCommand, $data, $sQueue, $iDelaySeconds = 0, & $sRealQueue = null) {
        $bActivityStatus = SysConfig::readValue('activity_status');
        if ($bActivityStatus == Activity::STATUS_OPEN) {
            return parent::addTask($sCommand, $data, $sQueue, $iDelaySeconds, $sRealQueue);
        } else {
            return true;
        }
    }

}
