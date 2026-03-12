#!/usr/bin/env php
<?php
namespace think;

// 定义应用目录
define('APP_PATH', __DIR__ . '/../../../application/');
// 加载基础文件 - 相对于项目根目录
require __DIR__ . '/../../../thinkphp/base.php';

// 兼容函数 - 如果不存在则定义
if (!function_exists('root_path')) {
    function root_path()
    {
        return defined('ROOT_PATH') ? ROOT_PATH : dirname(APP_PATH) . '/';
    }
}
if (!function_exists('base_path')) {
    function base_path($path = '')
    {
        return APP_PATH . ($path ? $path . DIRECTORY_SEPARATOR : $path);
    }
}
if (!function_exists('app_path')) {
    function app_path($path = '')
    {
        return APP_PATH . ($path ? $path . DIRECTORY_SEPARATOR : $path);
    }
}

// 加载应用数据库配置
$dbConfigFile = APP_PATH . 'database.php';
if (file_exists($dbConfigFile)) {
    $dbConfig = include $dbConfigFile;
    Config::set('database', $dbConfig);
}

$mysql = Config::get('database');

$opt= [
    'debug'=>false,//默认关闭
    'type' => 'mysql',
    'hostname' => $mysql["hostname"], 'hostport' => $mysql["hostport"],
    'database' => $mysql["database"], 'prefix' => $mysql["prefix"],
    'username' => $mysql["username"], 'password' => $mysql["password"],
    'root_path'=>root_path().'', //代码生成目录
    'app_path'=> base_path().'', //代码生成目录
    'md_path'=>root_path().'markdwon', //markdwon生成目录
    'mongodb'=>[
        'username' => 'root',
        'password' => 'root',
        'ssl' => false,
        'authSource' => 'admin'
    ]
];
//#########################################################################


/**
 * 命令流程
 *
 * php tool table:md qj_test_childs               生成 指定数据表 结构
 * php tool table:md qj_test_childs database      生成 指定数据表 结构 - 指定库
 * php tool table:md *                            生成 所有数据表 结构
 * php tool table:md * database                   生成 所有数据表 结构 - 指定库
 *
 * php tool yapi:psm  yapi/api.json  postman/api.json         yapi转psm 单文件
 * php tool yapi:psms  yapi/api.json  postman/api             yapi转psm 多文件
 *
 * php tool yapi-db:psm  13  postman/ypdb-api.json      yapi db数据 转psm 单文件
 * php tool yapi-db:psms  13  postman/ypdb-api          yapi db数据 转psm 多文件
 *
 */

require_once "extend/thinkex/MongoDB.php";
require_once "extend/thinkex/PdoDB.php";
require_once "extend/thinkex/ContentReplace.php";
require_once "extend/thinktest/base/api/TableBatchToDoc.php";
use extend\thinkex\tool;
use extend\thinktest\base\api\TableBatchToDoc;

$mdPath = $opt['md_path'];

//控制台逻辑
$command = $argv[1] ?? '';

// 显示帮助信息
if ($command === '--help' || $command === '-h' || $command === '') {
    echo "Tool - Database Documentation & API Conversion Tool\n";
    echo "\nUsage:\n";
    echo "  php tool <command> [options] [arguments]\n";
    echo "\nTable Documentation Commands:\n";
    echo "  table:md <table> [db]        Generate table structure markdown\n";
    echo "  table:md * [db]             Generate all tables structure markdown\n";
    echo "\nYAPI to Postman Commands:\n";
    echo "  yapi:psm <input> <output>   Convert YAPI JSON to Postman single file\n";
    echo "  yapi:psms <input> <output>  Convert YAPI JSON to Postman multiple files\n";
    echo "  yapi-db:psm <id> <output>   Convert YAPI MongoDB to Postman single file\n";
    echo "  yapi-db:psms <id> <output>  Convert YAPI MongoDB to Postman multiple files\n";
    echo "\nFor more information, see documentation\n";
    exit(0);
}

switch ($argv[1]) {
    case "":
    case "--help":
    case "-h":
        // Help is handled above
        break;
    case "table:md":
        // table:md has optional parameters, no validation needed
        $dbOptStr = null;
        if (isset($argv[3])) {
            $dbOptStr = $argv[3];
        }
        $TableBatchToDoc = new TableBatchToDoc($opt, 'table', $dbOptStr);

        $folders = ['table'];
        $TableBatchToDoc->makeFolderByArr($mdPath, $folders);

        $tbPath = $mdPath . '\\table';
        if (isset($argv[2])) {
            $TableBatchToDoc->batchWrite($argv[2], $tbPath, false);
        } else {
            $TableBatchToDoc->batchWrite(null, $tbPath, false);
        }
        break;
    case 'yapi:psm':
        if (empty($argv[2]) || empty($argv[3])) {
            echo "Error: Missing required arguments\n";
            echo "Usage: php tool yapi:psm <input> <output>\n";
            exit(1);
        }
        $tool = new tool($opt);
        $readPath = $argv[2];
        $putPath = $argv[3];

        $content = $tool->fileRead($readPath);
        $yapiArr = json_decode($content, true);

        $psmArr = $tool->yapiTurnAll($yapiArr);

        $content = json_encode($psmArr, JSON_UNESCAPED_UNICODE);
        $content = str_replace('\\/', '/', $content);

        $tool->filePut($putPath, $content);
        break;
    case 'yapi:psms':
        if (empty($argv[2]) || empty($argv[3])) {
            echo "Error: Missing required arguments\n";
            echo "Usage: php tool yapi:psms <input> <output>\n";
            exit(1);
        }
        $tool = new tool($opt);
        $readPath = $argv[2];
        $putPath = $argv[3];
        $content = $tool->fileRead($readPath);
        $yapiArr = json_decode($content, true);
        $tool->yapiTurSingle($yapiArr, $putPath);
        break;
    case 'yapi-db:psm':
        if (empty($argv[2]) || empty($argv[3])) {
            echo "Error: Missing required arguments\n";
            echo "Usage: php tool yapi-db:psm <project_id> <output>\n";
            exit(1);
        }
        $tool = new tool($opt);
        $projectId = (int)$argv[2];
        $putPath = $argv[3];

        $content = $tool->yapiProjRead($projectId);
        $yapiArr = json_decode($content, true);

        $psmArr = $tool->yapiTurnAll($yapiArr);

        $content = json_encode($psmArr, JSON_UNESCAPED_UNICODE);
        $content = str_replace('\\/', '/', $content);

        $tool->filePut($putPath, $content);
        break;
    case 'yapi-db:psms':
        if (empty($argv[2]) || empty($argv[3])) {
            echo "Error: Missing required arguments\n";
            echo "Usage: php tool yapi-db:psms <project_id> <output>\n";
            exit(1);
        }
        $tool = new tool($opt);
        $projectId = (int)$argv[2];
        $putPath = $argv[3];
        $content = $tool->yapiProjRead($projectId);
        $yapiArr = json_decode($content, true);
        $tool->yapiTurSingle($yapiArr, $putPath);
        break;
    case 'mongo:yp-psm':
        // Reserved for future use - commented out code
        break;
    default:
        echo "Error: Unknown command '{$argv[1]}'\n";
        echo "Run 'php tool --help' for usage information.\n";
        exit(1);
}