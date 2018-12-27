<?php

/**
 * Created by PhpStorm.
 * User: lewis.qing
 * Date: 2017/7/25
 * Time: 16:46
 */
class AutoloadService
{
    /**
     * common公共库默认加载的路径
     */
    private static $_common_group_path = array(
        'server.*',
        'util.*',
        'log_util.*',
        'network.http.*',
    );

    private static $_project_group_path = array(
        'src.service.*',
    );

    /**
     * @return array
     */
    public static function getCommonGroup()
    {
        return self::$_common_group_path;
    }

    public static function getProjectGroup()
    {
        return self::$_project_group_path;
    }
}