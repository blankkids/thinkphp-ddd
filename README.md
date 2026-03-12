# ThinkEx - TP50 DDD 代码生成工具

## 简介

ThinkEx 是一个基于 ThinkPHP 5.0 的 DDD（领域驱动设计）代码生成扩展包，提供了完整的领域层基础架构和自动化代码生成工具。

## 包含内容

### 1. 命令行工具

- **thinkex** - 模块代码生成工具
- **tool** - 数据库文档生成工具

### 2. 扩展类 (extend/thinkex/)

- `ThinkEx.php` - 代码生成核心类
- `ContentReplace.php` - 内容替换类
- `PdoDB.php` - PDO 数据库操作类
- `MongoDB.php` - MongoDB 操作类
- `tool.php` - 工具类

### 3. 基础领域层 (domain/base/)

- `controller/BaseController.php` - 控制器基类
- `model/BaseModel.php` - 模型基类
- `repository/BaseRepository.php` - 仓储基类
- `srv/BaseSrv.php` - 服务基类
- `error/BaseError.php` - 错误码基类
- `enum/BaseEnum.php` - 枚举基类
- `request/BaseRequest.php` - 请求验证基类
- `tran/BaseTran.php` - 数据转换基类
- `job/JobBase.php` - 队列基类
- `console/CommandBase.php` - 命令基类
- `middleware/` - 中间件
- `behavior/` - 行为类

### 4. 示例模块 (domain/demo/)

作为代码生成的模板，展示了标准的 DDD 模块结构。

## 目录结构规范

```
domain/
    └── [模块名]/
        ├── port/                    # 应用层-端口业务
        │   ├── controller/          # 控制器
        │   ├── request/             # 输入验证
        │   ├── logic/               # 服务函数组合层
        │   └── trans/               # 输出过滤
        ├── config/                  # 领域层-模块配置
        ├── enum/                    # 领域层-常量枚举值
        ├── error/                   # 领域层-业务异常值
        ├── srv/                     # 领域层-服务函数层
        ├── repository/              # 领域层-仓储层
        ├── model/                   # 基础层-MySQL数据模型
        ├── aggr/                    # 领域层-聚合
        ├── entity/                  # 基础层-实体
        ├── edoc/                    # 基础层-ElasticSearch数据模型
        ├── job/                     # 领域层-队列
        └── console/                 # 领域层-业务指令
```

## 安装

```bash
composer require blankkids/thinkphp-ddd
```

## 使用方法

### thinkex 命令

```bash
# 创建模块目录结构
php thinkex module:make 模块名

# 创建子接口组（无数据库）
php thinkex module:base 控制器名 模块名

# 创建子接口组（基于数据库）
php thinkex module:base-on 控制器名 模块名 数据库配置

# 创建模型（无数据库）
php thinkex module:model 控制器名 模块名

# 创建模型（基于数据库）
php thinkex module:model-on 控制器名 模块名 数据库配置

# 创建业务指令
php thinkex module:cmd 控制器名 模块名
php thinkex module:cmd-on 控制器名 模块名 数据库配置

# 创建消息队列
php thinkex module:job 控制器名 模块名
php thinkex module:job-on 控制器名 模块名 数据库配置

# 创建路由
php thinkex module:route 控制器名 模块名
```

## 示例模块代码 按数据表设计 生成 基本curd业务

~~~

根据示例模块结构, 自动生成需要的子功能接口,
且按照相应功能或数据表结构, 提供CURD基本接口, 并能满足一般的增删查改需要, 取消注释就能使用.

> 创建模块+目录结构
php thinkex module:make test

----------------------------------------

> 以下指令,适用于此参数结构:
[php,thinkex,module:*,'控制器名(数据表名)','模块名','可选生成curd函数(-,c,u,r,d,bc,bu,br,bd,cj,cmd)','指定数据库连接配置','指定数据表前缀']
-可选生成curd函数: "-"-空类, c-增,u-改,r-读(单行),d-删,bc-批量增,bu-批量改,br-批量读(列表),bd-批量删,cj-单增队列,cmd-指令, 按需填, 可传单个值.

> 创建模块-路由 -无数据库
php thinkex module:route TestChild test c,u,r,d,br

> 创建子接口组 -无数据库
php thinkex module:base TestChild test c,u,r,d,br

> 创建子接口组 -有数据库
php thinkex module:base-on TestChild test c,u,r,d,br database -

----------------------------------------

> 以下指令,适用于此参数结构:
[php,thinkex,module:*,'控制器名(数据表名)','模块名','指定数据库连接配置','指定数据表前缀']
-数据库连接配置: 不传值-默认database, 跨库需要另外指定配置名.
-指定数据表前缀: 传"-"-不指定, 不传值-用默认前缀, 传前缀-自定义前缀, 注意,跨库时,看情况需指定前缀.

> 新建-模型 -无数据库
php thinkex module:model TestChild test   
> 新建-模型 -有数据库
php thinkex module:model-on TestChild test database -

> 更新-模型 过滤字段 -有数据库
php thinkex module:model-fields TestChild test database -
> 更新-转化器 过滤字段 -有数据库
php thinkex module:trans-fields TestChild test database - 

> 创建模块-业务指令 -无数据库
php thinkex module:cmd TestChild test     
> 创建模块-业务指令 -有数据库
php thinkex module:cmd-on TestChild test database -

> 创建模块-消息队列 -无数据库
php thinkex module:job TestChild test
> 创建模块-消息队列 -有数据库
php thinkex module:job-on TestChild test database -

~~~

### tool 命令

```bash
# 生成指定数据表结构文档
php tool table:md 表名

# 生成指定数据表结构文档（指定数据库）
php tool table:md 表名 数据库名

# 生成所有数据表结构文档
php tool table:md *

# 生成所有数据表结构文档（指定数据库）
php tool table:md * 数据库名
```

~~~
> 以下指令,适用于此参数结构:
[php,tool,table:*,'数据表名(全名)','指定数据库连接配置']

> 生成 指定数据表 结构
php tool table:md qj_test_childs

> 生成 指定数据表 结构 - 指定库
php tool table:md qj_test_childs database

> 生成 所有数据表 结构
php tool table:md *  

> 生成 所有数据表 结构 - 指定库
 php tool table:md * database
~~~

## 许可证

Apache-2.0
