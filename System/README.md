FastPHP Version 6.0
=======================
FastPHP is a lightweight PHP framework.


框架设计说明
-----------------------
该框架基于“约定优于配置”原则，采用【约定型规则】设计。 **警示信息：该框架为了运行速度所做的优化，可能不适用于其他项目。**


System内核目录
-----------------------

* Core     内核主程序
* Driver   系统驱动
* Library  系统依赖的第三方包
* Plugin   系统插件，可以直接对外访问
* Shell    系统命令行，具体参考请在命令行下执行: `php bin`
* Util     系统工具包

对应System命名空间为System\\{DIR}：

* System\Core
* System\Driver
* System\Plugin
* System\Shell
* System\Util


Apps应用目录
-----------------------
App应用目录下包含一个共享目录（/Apps/Shared/）和多个应用目录，应用目录：Apps/{AppName}

### 共享目录 ###
共享目录下子目录包括：Controller、Model、View、Helper，一般只用到公共Controller和公共Model

### 应用目录 ###
应用目录下子目录包括：Controller、Model、View、Helper、Config、Data，其中Data为可读可写目录，主要存放App相关cache数据


Controller、Model、View
-----------------------

### Controller ###
Controller为控制器，不含子目录，命名空间为 Apps\\{AppName}\Controller，如：Apps\Web\Controller\Main

### Model ###
Model为数据对象，不含子目录，命名空间为 Apps\\{AppName}\Model，通过命名空间来调用具体的的Model

### View ###
View为模板，下面包含Layout和Widget目录，命名方式为 {控制器名称}_{方法名}，且为小写，如：main_index，main_list


AppHelper
----------------------
**根据项目的需求是否需要**，AppHelper作为公共助手放在 /Apps/Shared/Helper/


应用配置
----------------------
配置目录 Apps/{AppName}/Config
配置文件 App.php，{AppName}.app，Routes.php，Sys.dev.php，Database.dev.php

* App.php          为当前App的配置
* App.dev.php      为当前App的开发配置
* App.{IP}.php     为当前App的对应服务器的配置
* {AppName}.php    为当前App的配置，在同一个App目录下，可以指定为不同的App，不同的App间以不同的配置区分
* Routes.php       为当前App的Route配置
* Sys.dev.php      为当前站点的配置备份，当Sys.php存在时，则从文件加载站点配置，否则从数据库表“sys_config”加载站点配置项
* Database.dev.php 为初始MySQL文件，供建库参考


系统数据表
----------------------
系统内核调用到的三个数据表：sys_config，sys_bad_words，sys_ip_banned

* sys_config     站点配置表
* sys_bad_words  敏感词语配置表
* sys_ip_banned  IP屏蔽配置表


模板引擎语法设计说明
----------------------
* `{$var1,$var2,$var3}`        为简单输出，编译后： `<?=$var1,$var2,$var3?>` 或 `<?php echo $var1,$var2,$var3; ?>`
* `{=date()}`                  为简单函数调用，编译后： `<?=date()?>` 或 `<?php echo date(); ?>`
* `{?if($i>0):}Hello{?endif;}` 为简单语句，编译后： `<?php if($i>0): ?>Hello<?php endif; ?>`
* `{%print_r($GLOBALS);}`      等同于{?，编译后： `<?php print_r($GLOBALS); ?>`
* `{#use System\Core\App;}`    等同于{?，编译后： `<?php use System\Core\App; ?>`
* `{#include layout/header}`   为模板引擎语句，用于加载子模板


添加FastPHP到项目
----------------------
在项目中使用的时候，需要将FastPHP的根目录名称设置为System。**FastPHP根目录命名必须为System，否则会出错**

在项目根目录下执行： `git submodule add https://bitbucket.org/fastphp/fastphp.git System`