OIshare
===============

基于swoole框架的全协程框架：

 + 基于命名空间和众多PHP新特性
 + 强化路由功能
 + 更灵活的控制器
 + 重构的模型和数据库类
 + 配置文件可分离
 + 简化扩展机制
 + API支持完善
 + REST支持
 + 分布式环境支持

> Oishare的运行环境建议PHP7以上。

## 目录结构

初始的目录结构如下：

~~~
www  WEB部署目录（或者子目录）
├─app           应用目录
│  ├─module_name        模块目录
│  │  ├─controller      控制器目录
│  │  ├─model           模型目录
│  │  └─Logic           逻辑目录
│
├─config                配置目录
│  ├─local              开发环境配置目录
│  │  ├─appTcp.php      appTcp配置
│  │  ├─mysql.php       数据库配置
│  │  └─swoole.php      swoole配置
│
├─consts                常量定义目录
│  └─Database.php       语言文件目录
│
├─core                  核心文件目录
|  ├─Base               基本类目录
|  ├─Behavior           行为类目录
|  ├─Client             客户端操作类目录
|  ├─Database           数据库操作类目录
|  ├─Error              异常操作类目录
|  ├─Factory            工厂模式类目录
|  ├─Swoole             Swoole扩展类
|  ├─Traits             扩展类 
|  ├─BaseObject.php     基础类
|  ├─Bootstrap.php      启用类
|  ├─Conf.php           配置类
|  ├─Event.php          事件类
|  ├─Func.php           函数
|  ├─Loader.php         类自定义加载
|  └─Register.php       类自定义注册
│
├─index.php             入口文件
├─README.md             README 文件
~~~

> 开启http：php index -m http
>
> 开启tcp：php index -m tcp
>
> 开启webSocker: php index -m webSocket
>
> 支持nginx+php的web访问

## 命名规范

OIshare的命名规范遵循`PSR-2`规范以及`PSR-4`自动加载规范。

## 参与开发
注册并登录 Github 帐号， fork 本项目并进行改动。

## 版权信息

OIshare遵循Apache2开源协议发布，并提供免费使用。

版权所有Copyright © 2020 by OIshare (http://www.oishare.com)

All rights reserved。
