<?php

namespace Blankkids\ThinkphpDdd\Foundation\Console;

use extend\utils\TraceTool;
use think\console\Command;

/**
 * Class CommandBase - 指令基础类
 * @package Blankkids\ThinkphpDdd\Foundation\console
 */
class CommandBase extends Command
{
    //性能日志-入点
    protected function commandInLog()
    {
        $name = $this->getName();
        $desc = $this->getDescription();
        TraceTool::commandInLog($name . " | " . $desc);
    }

    //性能日志-出点
    protected function commandOutLog()
    {
        TraceTool::commandOutLog();
    }

}