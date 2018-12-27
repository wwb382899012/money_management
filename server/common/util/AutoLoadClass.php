<?php

/**
 * Created by PhpStorm.
 * User: lewis.qing
 * Date: 2017/7/20
 * Time: 14:16
 */



class AutoLoadClass
{
    public static function loadClass($className)
    {
        //先搜索common目录下的类
        $common_auto_path = AutoloadService::getCommonGroup();
        $classPath = self::findClassFile(COMMON_PATH,$common_auto_path, $className);
        if (empty($classPath))
        {
            //再搜索业务server目录下的
            if (defined('APPLICATION_PROJECT_PATH'))
            {
                $project_auto_path = AutoloadService::getProjectGroup();

                $autoclass = APPLICATION_PROJECT_PATH.DIRECTORY_SEPARATOR.'src'.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'autoclass.conf';
                if (file_exists($autoclass))
                {
                    $autoClassList = parse_ini_file($autoclass, TRUE);
                    if (isset($autoClassList['import']))
                    {
                        $project_auto_path = array_merge($project_auto_path, $autoClassList['import']);
                    }
                }
                $classPath = self::findClassFile(APPLICATION_PROJECT_PATH, $project_auto_path, $className);
            }
        }

        if (isset($classPath))
        {
            require_once $classPath;
        }
    }

    private static function findClassFile($path, $file, $className)
    {
        //寻找逻辑采用通配格式，如protected.service.*
        if (is_array($file))
        {
            foreach ($file as $key => $value )
            {
                $temp = self::string_repace($value,'.',DIRECTORY_SEPARATOR);
                $temp = self::string_repace($temp, '*', $className);
                $classFile = $path.DIRECTORY_SEPARATOR.$temp.'.php';
                if (file_exists($classFile))
                {
                    return $classFile;
                }
            }
        }
        return null;
    }

    private static function string_repace($path, $find, $replace)
    {
        return str_replace( $find, $replace,$path);
    }
}

spl_autoload_register(array ('AutoLoadClass', 'loadClass'));