<?php

namespace Blankkids\ThinkphpDdd\Generator;

use PDO;
use PDOException;

/**
 * PdoDB - PDO Database Helper
 *
 * A simple PDO wrapper for database operations
 *
 * @author blankkids
 * @version 1.0.0
 */
class PdoDB
{
    /** @var PDO */
    protected $dbh;

    /** @var array Configuration options */
    protected $opt;

    /**
     * Constructor
     *
     * @param array $opt Database configuration
     * @throws PDOException
     */
    public function __construct($opt)
    {
        try {
            $this->opt = $opt;
            $connect = $opt['type'] . ':host=' . $opt['hostname'] . ';dbname=' . $opt['database'] . ';charset=utf8mb4';
            $this->dbh = new PDO($connect, $opt['username'], $opt['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (PDOException $e) {
            throw new PDOException("Database connection failed: " . $e->getMessage());
        }
    }

    /**
     * Destructor
     */
    public function __destruct()
    {
        $this->dbh = null;
    }

    /**
     * Get table fields
     *
     * @param string $childName Table name
     * @return array
     */
    function getTableFields($childName)
    {
        $result = [];
        $childName = strtolower($childName);

        //无前缀处理
        if ($this->opt['prefix'] == '-') {
            $this->opt['prefix'] = '';
        }

        // 修复: 表名直接使用 childName，不再自动追加后缀
        $tableQuery = $this->opt['prefix'] . $childName;
        $stmt = $this->dbh->query('SELECT * from ' . $tableQuery . ' limit 1 ');

        //检查是否查错 - 提示
        $methodExist = method_exists($stmt, "columnCount");
        if (!$methodExist) {
            $database = $this->opt['database'];
            $msg = "Waring : " . $database . "." . $tableQuery . " | DataTable | is not Exists !";
            $this->console($msg, "yellow");
            die();
        }

        try {
            for ($i = 0; $i < $stmt->columnCount(); $i++) {
                $result[] = $stmt->getColumnMeta($i);
            }
        } catch (PDOException $e) {
            print "Error!: " . $e->getMessage() . "<br/>";
            die();
        }

        return $result;
    }

    function showFullColumns($childName)
    {
        $result = null;
        $childName = strtolower($childName);

        //无前缀处理
        if ($this->opt['prefix'] == '-') {
            $this->opt['prefix'] = '';
        }

        // 修复: 表名直接使用 childName，不再自动追加后缀
        $tableName = $childName;
        $stmt = $this->dbh->query('SHOW FULL COLUMNS FROM ' . $this->opt['prefix'] . $tableName);

        try {
            $result = $stmt->fetchAll();
        } catch (PDOException $e) {
            print "Error!: " . $e->getMessage() . "<br/>";
            die();
        }

        return $result;
    }

    function showTableStatus($childName)
    {
        $result = null;
        $childName = strtolower($childName);

        //无前缀处理
        if ($this->opt['prefix'] == '-') {
            $this->opt['prefix'] = '';
        }

        // 修复: 表名直接使用 childName，不再自动追加后缀
        $tableName = $childName;
        $stmt = $this->dbh->query('SHOW TABLE STATUS LIKE \'' . $this->opt['prefix'] . $tableName . '\'');

        try {
            $result = $stmt->fetch();
            $result["TableName"] = $tableName;
        } catch (PDOException $e) {
            print "Error!: " . $e->getMessage() . "<br/>";
            die();
        }

        return $result;
    }

    function query($queryStr)
    {
        $stmt = $this->dbh->query($queryStr);
        return $stmt;
    }


    private function console($msg, $color = null)
    {
        switch ($color) {
            default:
                $first = "\033[0m";
                break;
            case "red":
                $first = "\033[31m";
                break;
            case "lemon":
                $first = "\033[32m";
                break;
            case "yellow":
                $first = "\033[33m";
                break;
            case "blue":
                $first = "\033[34m";
                break;
            case "purple":
                $first = "\033[35m";
                break;
            case "green":
                $first = "\033[36m";
                break;
        }
        flush();
        print($first . $msg . "\n\033[0m");
    }
}