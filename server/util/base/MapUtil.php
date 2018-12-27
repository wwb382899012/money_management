<?php
/**
 * 
 * @author sunjiaxiao
 * @since 2018-06-20
 */
namespace money\base;
use money\model\DataDictKv;
class MapUtil {
	
	//获取数据字典值
	public static function getValByKey($map_name , $key ){
		$params = array(
				'dict_type'=>$map_name
		);
		
		$obj = new DataDictKv();
		$ret = $obj->details($params,' v.dict_key,v.dict_value '
				,1,-1);
		foreach($ret['data'] as $k){
			if($k['dict_key']==$key){
				return $k['dict_value'];
			}
		}
		throw new \Exception('数据字典值不存在',\ErrMsg::RET_CODE_DATA_NOT_EXISTS);
	}
	
	//根据数据字典值获取key值
	public static function getKeyByVal($map_name , $value ){
		$params = array(
				'dict_type'=>$map_name
		);
		
		$obj = new DataDictKv();
		$ret = $obj->details($params,' v.dict_key,v.dict_value '
				,1,-1);
		foreach($ret['data'] as $k){
			if($k['dict_value']==$value){
				return $k['dict_key'];
			}
		}
		return -1;
	}
	
	/**
	 *	数据字典字段值转换类
	 */
	public static function getMapdArrayByParams($array , $key , $map_name)
	{
		$params = array(
				'dict_type'=>$map_name
		);
		$obj = new DataDictKv();
		$maps = $obj->details($params,' v.dict_key,v.dict_value '
				,1,-1);
		\CommonLog::instance()->getDefaultLogger()->info(json_encode($maps));
		foreach($maps['data'] as $k){
			$map[$k['dict_key']] = $k['dict_value'];
		}
		\CommonLog::instance()->getDefaultLogger()->info(json_encode($map));
			
		$ret = array();
		foreach ($array as $obj) {
			if (!isset($obj[$key]) || !isset($map[$obj[$key]])) {
				$ret[] = $obj;
			}else{
				$obj[$key] = $map[$obj[$key]];
				$ret[] = $obj;
			}
		}
		\CommonLog::instance()->getDefaultLogger()->info(json_encode($ret));
		return $ret;
	}
}