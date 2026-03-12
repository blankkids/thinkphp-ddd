<?php

namespace extend\utils;

use think\Cache;
use think\Config;
use think\Request;

/**
 * ApiCache - Redis哈希表API缓存工具
 *
 * 基于Redis哈希表实现API级数据缓存，支持：
 * - 按类+方法生成缓存key
 * - 按请求参数生成子key
 * - 缓存集合的筛选与清理
 * - 缓存开关控制（_time参数）
 *
 * @author 陈鸿扬 | @date 2022/4/28
 * @version 1.0.0
 */
class ApiCache
{
    /** @var mixed Redis handler */
    protected $Redis;

    /**
     * 构造函数
     *
     * @param int $tableNum Redis数据库编号，0表示使用框架配置的库
     */
    public function __construct($tableNum = 0)
    {
        $this->Redis = Cache::store('redis')->handler();

        if ($tableNum == 0) {
            $tableNum = Config::get('cache.select');
        }
        $this->Redis->select($tableNum);
    }

    /**
     * 生成哈希KEY - 通过类和函数
     *
     * @param string $classAndMethod 类和函数命名空间，如: SampleController::class.'@sampleIndex'
     * @return string
     */
    public static function makeHKeyByClassMethod($classAndMethod)
    {
        $prefix = Config::get('redis.prefix') ?? '';
        $hKey = $prefix . 'api_cache:' . implode('_', explode('\\', $classAndMethod));
        return $hKey;
    }

    /**
     * 生成 QUERY KEY / HEADER KEY - 通过GET请求参数
     *
     * @param array $requestQuery GET请求参数
     * @param array $requestHeader 请求头参数
     * @param array $filter 头参数过滤器
     * @return string
     */
    public static function makeQueryKeyByRequest(array $requestQuery, array $requestHeader = [], array $filter = [])
    {
        if (isset($requestQuery['_time'])) {
            unset($requestQuery['_time']);
        }

        $query = '';
        if (!empty($requestHeader)) {
            $temp = [];
            if (!empty($filter)) {
                array_walk($filter, function ($key) use ($requestHeader, &$temp) {
                    if (isset($requestHeader[$key])) {
                        $temp[$key] = $requestHeader[$key];
                    }
                });
                $requestHeader = $temp;
            }
            ksort($requestHeader);
            $query .= '/&' . http_build_query($requestHeader);
        }

        if (!empty($requestQuery)) {
            ksort($requestQuery);
            $query .= '/&' . http_build_query($requestQuery);
        }

        if (empty($query)) {
            $query = '-';
        }

        return $query;
    }

    /**
     * 缓存集合存储
     *
     * @param string $hKey 哈希键 - 相当于数据集合名称
     * @param string $queryKey 子键 - GET请求参数字典序升序排列文本
     * @param \Closure $closure 闭包返回数据 - 必须是数组，不要对象
     * @param int $expire 过期时间（秒），0或负数表示不缓存
     * @return mixed
     */
    public function collect($hKey, $queryKey, \Closure $closure, $expire = 300)
    {
        $time = Request::instance()->get('_time', 0);

        if ($expire > 0 && $time == 0) {
            $data = $this->getDataByMineKey($hKey, $queryKey);
            if (!$data) {
                $result = $closure();
                $data = $this->setDataByMineKey($hKey, $queryKey, json_encode($result, JSON_UNESCAPED_UNICODE), $expire);
            }
            $result = json_decode($data, true);
        } elseif ($expire > 0 && $time == 1) {
            $result = $closure();
            $this->setDataByMineKey($hKey, $queryKey, json_encode($result, JSON_UNESCAPED_UNICODE), $expire);
        } else {
            $result = $closure();
        }

        return $result;
    }

    /**
     * 缓存集合筛选
     *
     * @param string $hKey 哈希键
     * @param string|null $queryKey 筛选关键字
     * @param string $wildcard 通配符
     * @return array
     */
    public function getCollect($hKey, $queryKey = null, $wildcard = '.*')
    {
        $data = [];
        $tempMapArr = [];
        $allData = $this->Redis->hgetall($hKey);

        if (!empty($allData)) {
            array_walk($allData, function ($value, $keyName) use ($queryKey, &$tempMapArr, $wildcard) {
                preg_match("/(" . $wildcard . $queryKey . $wildcard . ")/", $keyName, $match);
                if (isset($match[0])) {
                    $tempMapArr[$keyName] = $value;
                }
            });
            $allData = $tempMapArr;
            ksort($allData);
        }

        $data["data"] = $allData;
        $data["meta"] = [
            "total" => count($allData)
        ];
        return $data;
    }

    /**
     * 缓存集合清理
     *
     * @param string $hKey 哈希键
     * @param string|null $queryKey 清理关键字，为空则清除整个集合
     * @param string $wildcard 通配符
     * @return mixed
     */
    public function dropCollect($hKey, $queryKey = null, $wildcard = '.*')
    {
        if (empty($queryKey)) {
            return $this->Redis->expire($hKey, -1);
        }

        $hKeysArr = $this->Redis->hKeys($hKey);
        if (!empty($hKeysArr)) {
            $tempKeysArr = [];
            array_walk($hKeysArr, function ($keyName) use ($queryKey, &$tempKeysArr, $wildcard) {
                preg_match("/(" . $wildcard . $queryKey . $wildcard . ")/", $keyName, $match);
                if (isset($match[0])) {
                    $tempKeysArr[] = $keyName;
                }
            });
            if (!empty($tempKeysArr)) {
                $this->Redis->hDel($hKey, $tempKeysArr);
                $this->updateDbInfo($hKey);
                $hKeysArr = $tempKeysArr;
            }
        }
        return $hKeysArr;
    }

    /**
     * 获取数据
     *
     * @param string $hKey 哈希键
     * @param string $queryKey 子键
     * @return mixed
     */
    public function getDataByMineKey($hKey, $queryKey)
    {
        return $this->Redis->hGet($hKey, $queryKey);
    }

    /**
     * 保存数据
     *
     * @param string $hKey 哈希键
     * @param string $queryKey 子键
     * @param string $value 值（JSON字符串）
     * @param int $expire 过期时间
     * @return mixed
     */
    public function setDataByMineKey($hKey, $queryKey, $value, $expire = 300)
    {
        $this->setDbInfo($hKey, $expire);
        $this->Redis->hSet($hKey, $queryKey, $value);
        $this->updateDbInfo($hKey);
        return $this->Redis->hGet($hKey, $queryKey);
    }

    /**
     * 设置db集合全局信息
     *
     * @param string $hKey 哈希键
     * @param int $expire 过期时间
     */
    protected function setDbInfo($hKey, $expire = 300)
    {
        $hKeysArr = $this->Redis->hKeys($hKey);
        if (empty($hKeysArr)) {
            $this->Redis->hSet($hKey, 'db_total', 0);
            $this->Redis->hSet($hKey, 'db_expire', $expire);
            $this->Redis->hSet($hKey, 'db_create_time', date('Y-m-d H:i:s'));
            $this->Redis->hSet($hKey, 'db_update_time', date('Y-m-d H:i:s'));
            $this->Redis->expire($hKey, $expire);
        }
    }

    /**
     * 更新db集合全局信息
     *
     * @param string $hKey 哈希键
     */
    protected function updateDbInfo($hKey)
    {
        $hKeysArr = $this->Redis->hKeys($hKey);
        if (!empty($hKeysArr)) {
            $this->Redis->hMset($hKey, ['db_total' => count($hKeysArr) - 4]);
            $this->Redis->hMset($hKey, ['db_update_time' => date('Y-m-d H:i:s')]);
        }
    }
}
