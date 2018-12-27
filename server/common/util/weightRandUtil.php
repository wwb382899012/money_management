<?php
/**
 * Created by PhpStorm.
 * User: lewis.qing
 * Date: 2017/6/26
 * Time: 17:06
 */


class weightRandUtil
{
    public static function getWeightCount($urls)
    {
        $WeightCount = 0;
        foreach($urls as $index => $url)
        {
            $WeightCount += self::getRouterWeight($url);
        }
        return $WeightCount;
    }

    public static function getRouterWeight($url)
    {
        return $url->getDefaultWeight(100);
    }

    public static function getIndexByWeightRand($urlList)
    {
        $index = 0;
        $count = self::getWeightCount($urlList);
        $randValue = mt_rand(0,$count-1);
        $startValue = 0;
        foreach($urlList as $index => $value)
        {
            $weightValue = self::getRouterWeight($value);
            if ($startValue <= $randValue && $randValue < $startValue+$weightValue)
            {
                break;
            }
            else
            {
                $startValue += $weightValue;
            }
        }
        return $index;
    }

    public static function getUrlInfoByWeightRand($urlList)
    {
        $url = null;
        $count = self::getWeightCount($urlList);
        $randValue = mt_rand(0,$count-1);
        $startValue = 0;
        foreach($urlList as $key => $url)
        {
            $weightValue = self::getRouterWeight($url);
            if ($startValue <= $randValue && $randValue < $startValue+$weightValue)
            {
                break;
            }
            else
            {
                $startValue += $weightValue;
            }
        }
        return $url;

    }

    public static function parseWeightInfo($versionWeightList)
    {
        $versionLists = array();
        $versionWeight = explode(",", $versionWeightList);
        foreach ($versionWeight as $index => $value)
        {
            $versionWeight = explode(":" ,$value);
            if (count($versionWeight) == 1)
            {
                $versionLists[$value] = 0;
            }
            else
            {
                $versionLists[$versionWeight[0]] = $versionWeight[1];
            }
        }
        return $versionLists;
    }
    public static function getWeightNum($versionLists)
    {
        $weightSum = 0;
        foreach ($versionLists as $key => $value)
        {
            $weightSum += $value;
        }

        return $weightSum;
    }
    public static function getVersionByWeight($versionWeightList)
    {
        $version = $versionWeightList;
        $versionLists = self::parseWeightInfo($versionWeightList);
        $weightSum = self::getWeightNum($versionLists);
        if ($weightSum > 0)
        {
            $startValue = 0;
            $dstValue = mt_rand(0, $weightSum-1);
            foreach ($versionLists as $key => $value)
            {
                if($dstValue >= $startValue && $dstValue < $value + $startValue)
                {
                    $version = $key;
                    break;
                } else
                {
                    $startValue += $value;
                }
            }
        }
        return $version;
    }
}