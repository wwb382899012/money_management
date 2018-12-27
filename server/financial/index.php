<?php
/**
 * Created by PhpStorm.
 * User: lewis.qing
 * Date: 2017/7/19
 * Time: 20:00
 */

/**
 * 1、必填项，用于公共库自动加载的宏路径
 */
define('APPLICATION_PROJECT_PATH',__DIR__.DIRECTORY_SEPARATOR);

/**
 * 2、增加业务的config配置信息
 */
require_once APPLICATION_PROJECT_PATH.'src'.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'application.php';


/**
 * 3、业务服务初始化逻辑，及其他的一些业务准备工作
 */


/**
 * 4、依赖的其他服务的初始化及自动化加载逻辑
 * 如公共库common的加载
 */
$common_path = dirname(__DIR__).DIRECTORY_SEPARATOR.'common'.DIRECTORY_SEPARATOR.'index.php';

//后续微服务发布的文件结构为appname/{$version}/...
if (!file_exists($common_path))
{
    $common_path = dirname(dirname(__DIR__)).DIRECTORY_SEPARATOR.'common'.DIRECTORY_SEPARATOR.'index.php';
}
require_once $common_path;

JmfUtil::RequireJmfApiInit('financial', __DIR__);

//composer
require_once dirname(__DIR__).DIRECTORY_SEPARATOR."vendor".DIRECTORY_SEPARATOR.'autoload.php';