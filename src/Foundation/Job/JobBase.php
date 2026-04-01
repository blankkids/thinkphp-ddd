<?php

namespace Blankkids\ThinkphpDdd\Foundation\Job;

use extend\utils\TraceTool;

/**
 * Class JobBase  - 队列基础类
 * @package Blankkids\ThinkphpDdd\Foundation\job
 */
class JobBase
{
    public function __construct()
    {
        $queueName = get_called_class();
        TraceTool::queueInLog($queueName);//性能日志-入点
    }

    public function __destruct()
    {
        TraceTool::queueOutLog();//性能日志-出点
    }
}