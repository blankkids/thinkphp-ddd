<?php

namespace Blankkids\ThinkphpDdd\Support;

use think\Config;

/**
 * QueryMatch - URL Query查询表达式参数获取工具
 *
 * 解析URL查询参数，构建ThinkPHP风格的查询条件，支持：
 * - where表达式: ?_where=key/value,key/>=/value
 * - whereIn表达式: ?_where_in=key/value1,value2
 * - whereInSort表达式: ?_where_in_sort=key/value1,value2
 * - 排序: ?_sort=-id,create_time
 * - 分页: ?_page=1&_page_size=20&_pagination=true
 * - 关联查询: ?_include=user,profile
 * - 字段筛选: ?_search=default
 *
 * @author 陈鸿扬 | @date 2022/4/4
 * @version 1.0.0
 */
class QueryMatch
{
    /** @var self|null 单例实例 */
    protected static $instance = null;

    /** @var array 请求查询参数 */
    protected $requestQuery = [];

    /**
     * 构造函数
     *
     * @param array $requestQuery GET请求参数数组
     */
    public function __construct(array $requestQuery = [])
    {
        $this->requestQuery = $requestQuery;
    }

    /**
     * 获取单例实例
     *
     * @param array $requestQuery GET请求参数数组
     * @return static
     */
    public static function instance(array $requestQuery = [])
    {
        if (!(self::$instance instanceof static)) {
            self::$instance = new static($requestQuery);
        }
        return self::$instance;
    }

    /**
     * 获取指定query key的值
     *
     * @param string $key 参数名
     * @param mixed $default 默认值
     * @return mixed
     */
    public function getQuery($key, $default = null)
    {
        if (isset($this->requestQuery["$key"])) {
            return $this->requestQuery["$key"];
        }
        return $default;
    }

    //query ?where=key/value 运算符转换
    protected function operator(&$item)
    {
        preg_match('/^[\w\s]+(>\/|<\/|><|>\/|<\/|>|<|\/|\\|).*$/i', $item, $m);
        if (isset($m[1])) {
            $item = explode($m[1], $item);
        }
        if (is_array($item)) {
            $item[2] = $item[1];
            switch ($m[1]) {
                default:
                    $item[1] = '=';
                    break;
                case "/":
                    $item[1] = '=';
                    break;
                case ">":
                    $item[1] = '>';
                    break;
                case "<":
                    $item[1] = '<';
                    break;
                case ">/":
                    $item[1] = '>=';
                    break;
                case "</":
                    $item[1] = '<=';
                    break;
                case "|":
                    $item[1] = 'like';
                    preg_match('/^\%/i', $item[2], $left);
                    preg_match('/\%$/i', $item[2], $right);
                    $item[2] = trim($item[2], '%');
                    if (isset($left[0]) && $left[0] == '%') {
                        $item[2] = '%%' . $item[2];
                    } else if (isset($right[0]) && $right[0] == '%') {
                        $item[2] = $item[2] . '%%';
                    } else {
                        $item[2] = '%%' . $item[2] . '%%';
                    }
                    break;
            }
        }

        //检查 where表达式是否合法
        if (!is_array($item)) {
            throw new \Exception("Invalid where expression: $item", 2000);
        }

        return $item;
    }

    //query ?where_in=key/value,value,.. 运算符转换
    protected function inOperator(&$item, &$sortItem = null)
    {
        preg_match('/^([\w\s]+)(\/)(.*)$/i', $item, $m);
        if (isset($m[1])) {
            $item = explode($m[1], $item);
        }
        if (is_array($item)) {
            $values = explode(',', $m[3]);
            $values = array_unique($values);
            $whereInArr = [$m[1], 'in', implode(',', $values)];
            $item = $whereInArr;
            $sortItem[0] = $m[1];
            $sortItem[1] = implode(',', $values);
        }

        //检查 where_in表达式是否合法
        if (!is_array($item)) {
            throw new \Exception("Invalid where_in expression: $item", 2001);
        }

        return $item;
    }

    /**
     * 解析 ?_where=key/value 条件
     *
     * @param array &$whereArr 引用返回的条件数组
     * @param bool $kv true返回键值对数组，false返回三维数组
     * @return array
     */
    public function where(&$whereArr, $kv = false)
    {
        $where = $this->getQuery('_where');
        if ($where) {
            $where = explode(',', $where);
            if (is_array($where)) {
                foreach ($where as $item) {
                    $this->operator($item);
                    if ($kv) {
                        $whereArr[$item[0]] = $item[2];
                    } else {
                        $whereArr[] = $item;
                    }
                }
                return $whereArr;
            }
        }
    }

    /**
     * 获取单个查询动作
     *
     * @param string $action 返回查询动作
     * @return mixed|string|null
     */
    public function searchAction(&$action = 'default')
    {
        $action = $this->getQuery('_search', 'default');
        return $action;
    }

    /**
     * 捕捉 ?type=1&status=1... 的值，转化成查询数组
     *
     * @param array &$searchArr 间接返回查询结构数组到外部引用值
     * @param array $rule 设置字段对应运算符: ["key_name"=>'>=']
     * @param array $filterArr 指定过滤字段: ['type','status']
     * @param string $filter 指定过滤类型: only-提取|except-排除
     * @return array 直接返回查询结构数组
     */
    public function search(&$searchArr = [], $rule = [], $filterArr = [], $filter = 'only')
    {
        //排除参数集
        $outQuery = [
            'pagination', 'page', 'page_size', 'per_page',
            '_pagination', '_page', '_page_size', '_per_page',
            '_where', '_where_in', '_where_in_sort', '_include', '_extend', '_search',
            '_sort', '_time'
        ];

        $query = $this->requestQuery;
        $queryArr = array_diff_key($query, array_flip($outQuery));

        if (!empty($filterArr) && $filter == 'only') {
            $queryArr = array_intersect_key($queryArr, array_flip($filterArr));
        }
        if (!empty($filterArr) && $filter == 'except') {
            $queryArr = array_diff_key($queryArr, array_flip($filterArr));
        }

        if (!empty($queryArr)) {
            array_walk($queryArr, function ($value, $keyName) use ($rule, &$searchArr, &$searchKeyArr) {
                if (isset($rule["$keyName"]) && !empty($rule["$keyName"])) {
                    $operator = $rule["$keyName"];
                } else {
                    $operator = '=';
                }
                $this->searchOperator($operator, $value);
                if ($value !== '') {
                    $currArr = ["$keyName", $operator, $value];
                    $searchArr[] = $currArr;
                }
            });
        }
        return $searchArr;
    }

    //筛选运算符预处理
    protected function searchOperator(&$operator, &$value)
    {
        switch ($operator) {
            case 'like':
                preg_match('/^\%/i', $value, $m);
                if (!isset($m[0])) {
                    $value = '%' . $value . '%';
                }
                break;
            case '=':
                preg_match('/\,|\%/i', $value, $m);
                if (isset($m[0]) && $m[0] == ',') {
                    $operator = 'in';
                }
                if (isset($m[0]) && $m[0] == '%') {
                    $operator = 'like';
                }
                break;
        }
    }

    /**
     * 获取where表达式中同字段筛选范围，如时间区间
     *
     * @param array $whereArr 条件数组
     * @param string $key 字段名
     * @param string $startDate 起始日期（引用返回）
     * @param string $endDate 结束日期（引用返回）
     */
    public function getStartEndForWhere($whereArr, $key, &$startDate, &$endDate)
    {
        $startDate = '';
        $endDate = '';
        if ($whereArr && count($whereArr) > 0) {
            foreach ($whereArr as $ind => $item) {
                if ($item[0] == $key && $item[1] == '>=') {
                    $startDate = $item[2];
                };
                if ($item[0] == $key && $item[1] == '<=') {
                    $endDate = $item[2];
                };
            }
        }
    }

    /**
     * 按where筛选 - 闭包
     *
     * @param array $whereArr 条件数组
     * @param \Closure $closure 闭包函数
     */
    public function whereClosure($whereArr, \Closure $closure)
    {
        if (!empty($whereArr)) {
            foreach ($whereArr as $ind => $data) {
                $closure($data);
            }
        }
    }

    /**
     * 按where_in id筛选 - 闭包
     *
     * @param array $whereInArr 条件数组
     * @param \Closure $closure 闭包函数
     */
    public function whereInClosure($whereInArr, \Closure $closure)
    {
        foreach ($whereInArr as $ind => $data) {
            $closure($data);
        }
    }

    /**
     * 解析 ?_where_in=key/value1,value2 条件
     *
     * @param array &$whereInArr 引用返回的条件数组
     * @param array|null &$sortItemArr 引用返回的排序数组
     * @return array
     */
    public function whereIn(&$whereInArr, &$sortItemArr = null)
    {
        $whereIn = $this->getQuery('_where_in');
        if ($whereIn) {
            $whereIn = explode('|', $whereIn);
            if (is_array($whereIn)) {
                $whereInArr = [];
                $sortItemArr = [];
                foreach ($whereIn as $item) {
                    $this->inOperator($item, $sortItem);
                    $whereInArr[] = $item;
                    $sortItemArr[] = $sortItem;
                }
                return $whereInArr;
            }
        }
    }

    /**
     * 按where_insort id筛选+排序 - 闭包
     *
     * @param array $whereInSortArr 条件数组
     * @param array $sortItem 排序项
     * @param \Closure $closure 闭包函数
     */
    public function whereInSortClosure($whereInSortArr, $sortItem = [], \Closure $closure)
    {
        if (!empty($whereInSortArr)) {
            foreach ($whereInSortArr as $ind => $data) {
                $rawStr = '';
                if (isset($sortItem[$ind])) {
                    $sortData = $sortItem[$ind];
                    $rawStr = "FIND_IN_SET(" . $sortData[0] . ",'" . $sortData[1] . "'" . ')';
                }
                $closure($data, $rawStr);
            }
        }
    }

    /**
     * 解析 ?_where_in_sort=key/value1,value2 按id顺序返回结果
     *
     * @param array &$whereInArr 引用返回的条件数组
     * @param array|null &$sortItemArr 引用返回的排序数组
     * @return array
     */
    public function whereInSort(&$whereInArr, &$sortItemArr = null)
    {
        $whereIn = $this->getQuery('_where_in_sort');
        if ($whereIn) {
            $whereIn = explode('|', $whereIn);
            if (is_array($whereIn)) {
                $whereInArr = [];
                $sortItemArr = [];
                foreach ($whereIn as $item) {
                    $this->inOperator($item, $sortItem);
                    $whereInArr[] = $item;
                    $sortItemArr[] = $sortItem;
                }
                return $whereInArr;
            }
        }
    }

    /**
     * 解析排序参数 ?_sort=-id,create_time
     * 默认 order id=desc 排序
     *
     * @param array &$sortArr 引用返回的排序数组
     */
    public function order(&$sortArr)
    {
        $order = $this->getQuery('_sort', '-id');
        if (!empty($order)) {
            $orders = $this->sortOperator($order);
            $sortArr = $orders;
        }
    }

    /**
     * 解析分组参数 ?_group=field1,field2
     *
     * @param array &$groupArr 引用返回的分组数组
     */
    public function group(&$groupArr)
    {
        $group = $this->getQuery('_group');
        if (!empty($group)) {
            $groups = $this->groupOperator($group);
            $groupArr = $groups;
        }
    }

    protected function groupOperator($groupStr)
    {
        $groupMap = explode(',', $groupStr);
        $groupArr = [];
        foreach ($groupMap as $ind => $gStr) {
            $groupArr[] = $gStr;
        }
        return $groupArr;
    }

    /**
     * 排序批量处理 - 闭包
     *
     * @param array $sortArr 排序数组
     * @param \Closure $closure 闭包函数
     */
    public function sortClosure($sortArr, \Closure $closure)
    {
        if (!empty($sortArr)) {
            foreach ($sortArr as $k => $v) {
                $closure($k, $v);
            }
        }
    }

    /**
     * 解析排序参数 ?_sort=-id,create_time
     * 无默认排序
     *
     * @param array &$sortArr 引用返回的排序数组
     */
    public function sort(&$sortArr)
    {
        $order = $this->getQuery('_sort');
        if (!empty($order)) {
            $orders = $this->sortOperator($order);
            $sortArr = $orders;
        }
    }

    //排序-sort参数转换
    protected function sortOperator($orderStr)
    {
        $sortMap = explode(',', $orderStr);
        $sortArr = [];
        foreach ($sortMap as $ind => $sortStr) {
            $orderFields = 'id';
            $orderType = 'desc';
            preg_match("/^(-|)(.*)$/i", $sortStr, $m);
            if ($m[0]) {
                switch ($m[1]) {
                    default:
                        $orderType = 'asc';
                        break;
                    case "-":
                        $orderType = 'desc';
                        break;
                }
                $orderFields = $m[2];
            }
            $sortArr[$orderFields] = $orderType;
        }
        return $sortArr;
    }

    /**
     * 解析关联查询参数 ?_include=user,profile
     *
     * @param array &$includeArr 引用返回的关联数组
     * @param string|null $classPath 模型类路径，用于验证方法存在性
     * @param array|null $except 排除的关联名
     */
    public function include(&$includeArr, $classPath = null, $except = null)
    {
        $include = $this->getQuery('_include');
        if (isset($include) && !empty($include)) {
            $joins = explode(',', $include);
            $includeArr = $joins;
        } else {
            $includeArr = [];
        }

        if (!empty($includeArr) && !empty($classPath)) {
            foreach ($includeArr as $ind => $name) {
                $methodName = $this->toHumpName($name);
                $methodExits = method_exists($classPath, $methodName);
                if (!$methodExits) {
                    unset($includeArr[$ind]);
                }
            }
        }

        if (!empty($includeArr) && !empty($except)) {
            $includeArr = array_diff_assoc($includeArr, $except);
            $tempArr = [];
            array_walk($includeArr, function ($name) use (&$tempArr, $except) {
                if (!in_array($name, $except)) {
                    $tempArr[] = $name;
                }
            });
            $includeArr = $tempArr;
        }
    }

    /**
     * 检查关联查询模型 - 闭包
     *
     * @param object $class 模型实例
     * @param array $includeArr 关联数组
     * @param \Closure $closure 闭包函数
     */
    public function incModelHaveClosure($class, $includeArr, \Closure $closure)
    {
        if (!empty($includeArr)) {
            foreach ($includeArr as $ind => $name) {
                $methodName = $this->toHumpName($name);
                $methodExits = method_exists($class, $methodName);
                if ($methodExits) {
                    $closure($methodName);
                }
            }
        }
    }

    /**
     * 检查关联查询模型是否存在
     *
     * @param array &$joins 引用返回的关联数组
     */
    public function incModelHave(&$joins)
    {
        foreach ($joins as $ind => $name) {
            $methodName = $this->toHumpName($name);
            $methodExits = method_exists($this, $methodName);
            if (!$methodExits) {
                unset($joins[$ind]);
            };
        }
    }

    /**
     * 小写名称转驼峰 - 如 user_name -> userName
     *
     * @param string $name 下划线名称
     * @return string
     */
    public function toHumpName($name)
    {
        $nameArr = explode('_', $name);
        $newName = '';
        foreach ($nameArr as $ind => $str) {
            if ($ind == 0) {
                $newName .= strtolower($str);
            } else {
                $newName .= ucwords($str);
            }
        }
        return $newName;
    }

    /**
     * 翻页查询
     *
     * @param int &$per_page 每页条数
     * @param int &$page 当前页码
     * @param bool &$pagination 是否开启分页
     * @param int &$row 偏移量
     * @return array
     */
    public function pagination(&$per_page = 20, &$page = 1, &$pagination = false, &$row = 0)
    {
        $per_page = Config::get('paginate.page_size_default') ?? 20;
        $page = 1;
        $pagination = $this->getQuery("_pagination");
        if (!$pagination) {
            $pagination = $this->getQuery("pagination");
        }

        if ($pagination != 'false') {
            self::pageParamFormat($per_page, $page, true);
        } else {
            self::pageParamFormat($per_page, $page, false);
        }

        if ($page < 1) {
            $page = 1;
        };
        $row = ($page - 1) * $per_page;

        return [
            'pagination' => $pagination,
            'per_page' => $per_page,
            'page' => $page,
            'row' => $row,
        ];
    }

    /**
     * 翻页参数格式化
     *
     * @param int &$per_page 每页条数
     * @param int &$page 当前页码
     * @param bool $pageAble 是否启用分页
     */
    public function pageParamFormat(&$per_page, &$page, $pageAble = true)
    {
        if ($pageAble) {
            if ($this->getQuery('_page_size')) {
                $per_page = (int)$this->getQuery('_page_size', 20);
            } else if ($this->getQuery('page_size')) {
                $per_page = (int)$this->getQuery('page_size', 20);
            }

            if ($this->getQuery('_per_page')) {
                $per_page = (int)$this->getQuery('_per_page', 20);
            } else if ($this->getQuery('per_page')) {
                $per_page = (int)$this->getQuery('per_page', 20);
            }

            if ($this->getQuery('_page')) {
                $page = (int)$this->getQuery('_page', 1);
            } else if ($this->getQuery('page')) {
                $page = (int)$this->getQuery('page', 1);
            }
        } else {
            $per_page = (int)100;
            if ($this->getQuery('_page')) {
                $page = (int)$this->getQuery('_page', 1);
            } else if ($this->getQuery('page')) {
                $page = (int)$this->getQuery('page', 1);
            }
        }
    }
}
