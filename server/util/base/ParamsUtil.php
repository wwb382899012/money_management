<?php
/**
*	参数相关工具类
*	@author sun
*	@since 2018-03-06
*/
namespace money\base;
class ParamsUtil
{
	/*
	*	参数必传验证
	*/
	public static function validateParams($params,$keys){
        foreach($keys as $p){
            if(!isset($params[$p])){
                return \ErrMsg::RET_CODE_MISS_PARAMS;
            }
        }

        return 0;
    }

    /*
	*	参数必传验证
	*/
	public static function validateParamsByRule($params,$rules){
        foreach($rules as $key=>$r){

            if(!is_array($r)) {
                $r_array = explode(',' , $r);
            } else {
                $r_array = $r;
            }
            if(!in_array('required',$r_array)&&empty($params[$key])) {
                continue;
            }
            foreach($r_array as $rule){
                if(empty($rule)){
                        continue;
                }
                $fn = 'validate'.$rule;
                $val = isset($params[$key])?$params[$key]:null;
                $ret = self::$fn($val, $r);
                if($ret!=0) {
                    return $ret;
                }
            }
        }
        return 0;
    }

    private static function validatenum($param , $r=null){
        if(!is_numeric($param)){
            return \ErrMsg::RET_CODE_DATA_NOT_EXISTS;
        }

    }

    private static function validaterequired($param , $r=null){
        if(!isset($param)){
            return \ErrMsg::RET_CODE_MISS_PARAMS;
        }
    }
    
    public static function getDataByArray($params , $key){
    	return isset($params[$key])?$params[$key]:null;
    }
    
    public static function getArrayByKey($array , $key){
    	$ret = array();
    	foreach($array as $map){
    		$ret[] = $map[$key];
    	}
    	return $ret;
    }


}