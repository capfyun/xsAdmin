xsAdmin
===============

## 安装方法

* 需要工具：
* * Composer
* * Bower（使用npm安装：npm install -g bower）

* 克隆或解压项目后：
* * 在根目录执行指令：composer install（安装PHP依赖库）
* * 在根目录执行指令：bower install（安装JS依赖库）
* * 将SQL文件mysql.sql手动导入数据库（之后版本会更新为安装方式）之后，更新数据库配置文件application/database.php

## 联系作者
联系方式 [QQ](http://wpa.qq.com/msgrd?v=3&uin=550373770&site=qq&menu=yes)。

## 项目介绍

xsAdmin是一款基于 `ThinkPHP5.0` + `AdminLTE` 的后台开发框架。

 + 完善的 Auth 验证的权限管理系统
 + 基于 AdminLTE 二次开发
 + 基于 Bootstrap 开发，自适应手机、平板、PC
 + 内置 API 管理系统，使接口开发更为便捷
 + 简洁高效的插件系统，便于系统扩展，减少模块之间的耦合
 + 独立的个人扩展库，便于制造车轮（设计库）
 + 使用 Composer 进行 PHP 包管理
 + Bower 进行前端组件包管理
 + RequireJS 进行 JS 模块管理，惰性加载
 + 内置各种常用类库，如：AES 和 RSA 加密、程序锁、消息队列、定时器、文件上传、QRCode 生成等
 + ...

> 运行环境要求PHP5.4以上。

## 目录结构

初始的目录结构如下：

~~~
www  WEB部署目录（或者子目录）
├─application           应用目录
│  ├─common             公共模块目录（可以更改）
│  ├─admin              后台模块目录
│  │  ├─config.php      模块配置文件
│  │  ├─common.php      模块函数文件
│  │  ├─controller      控制器目录
│  │  ├─model           模型目录
│  │  ├─view            视图目录
│  │  └─ ...            更多类库目录
│  ├─api                接口模块目录
│  │
│  ├─command.php        命令行工具配置文件
│  ├─common.php         公共函数文件
│  ├─config.php         公共配置文件
│  ├─route.php          路由配置文件
│  ├─tags.php           应用行为扩展定义文件
│  └─database.php       数据库配置文件
│
├─public                WEB目录（对外访问目录）
│  ├─resource           资源目录
│  │  └─asset           静态资源目录
│  ├─index.php          入口文件
│  ├─router.php         快速测试文件
│  └─.htaccess          用于apache的重写
│
├─thinkphp              框架系统目录
│
├─extend                扩展类库目录
├─lib                   自定义库目录
├─runtime               应用的运行时目录（需要可写权限）
├─vendor                第三方类库目录（Composer依赖库）
├─build.php             自动生成定义文件（参考）
├─composer.json         composer 定义文件
├─README.md             README 文件
├─think                 命令行入口文件
~~~
