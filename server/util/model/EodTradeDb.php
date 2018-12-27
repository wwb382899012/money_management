<?php
/**
 *	eod日终检查表工具类
 *	@author sunjiaxiao
 *	@since 2018-07-12
 */
namespace money\model;
use money\base\ParamsUtil;
class EodTradeDb extends BaseModel{
	protected $table = 'm_report_eod';
	protected $pk = 'id';
	
	const STATUS_CODE_UNOPT = 1;
	const STATUS_CODE_OPTED = 2;
	
	public static function dataCreate($params){
		$require_params = [
			'main_body_uuid','opt_uuid','trade_type'
		];
		if(ParamsUtil::validateParams($params, $require_params)){
			throw new \Exception('参数缺失',\ErrMsg::RET_CODE_DATA_NOT_EXISTS);
		}
		if(in_array($params['trade_type'],[1,3,5])){
			if(!isset($params['out_order_num'])){
				throw new \Exception('参数缺失',\ErrMsg::RET_CODE_DATA_NOT_EXISTS);
			}
		}else{
// 			if(!isset($params['transfer_num'])){
// 				throw new \Exception('参数缺失',\ErrMsg::RET_CODE_DATA_NOT_EXISTS);
// 			}
		}
		$obj = new static();
		
		$obj->params = [
			'out_order_num'=>ParamsUtil::getDataByArray($params, 'out_order_num'),
			'transfer_num'=>ParamsUtil::getDataByArray($params, 'transfer_num'),
			'main_body_uuid'=>ParamsUtil::getDataByArray($params, 'main_body_uuid'),
			'order_create_time'=>ParamsUtil::getDataByArray($params, 'order_create_time'),
			'order_opt_time'=>ParamsUtil::getDataByArray($params, 'order_opt_time'),
			'transfer_create_time'=>ParamsUtil::getDataByArray($params, 'transfer_create_time'),
			'limit_date'=>!empty($params['limit_date'])?$params['limit_date']:date('Y-m-d'),
			'opt_uuid'=>ParamsUtil::getDataByArray($params, 'opt_uuid'),
			'trade_type'=>ParamsUtil::getDataByArray($params, 'trade_type'),
			'create_time'=>date('Y-m-d H:i:s'),
			'status'=>self::STATUS_CODE_UNOPT
		];

		$datas = $obj->loadDatas(['opt_uuid'=>$params['opt_uuid'],'trade_type'=>$params['trade_type']]);
		if(is_array($datas)&&count($datas)>0){
			$obj->params['id'] = $datas[0]['id'];
			$obj->params['order_create_time'] = isset($params['order_create_time'])?$params['order_create_time']:$datas[0]['order_create_time'];
			$obj->params['order_opt_time'] =  isset($params['order_opt_time'])?$params['order_opt_time']:$datas[0]['order_opt_time'];
			$obj->params['create_time'] =  $datas[0]['create_time'];
		}
		$obj->saveOrUpdate();
	}
	
	public static function dataOpted($uuid , $trade_type){
		$obj = new static();
		$datas = $obj->loadDatas(['opt_uuid'=>$uuid,'trade_type'=>$trade_type]);
		
		foreach($datas as $d){
			$obj->params = [
				'id'=>$d['id'],
				'status'=>2,
				'update_time'=>date('Y-m-d H:i:s')
			];
			$obj->saveOrUpdate();
		}
	}
	
	public static function dataUpdate($opt_uuid , $trade_type , $params){
		$obj = new static();
		$datas = $obj->loadDatas(['opt_uuid'=>$opt_uuid,'trade_type'=>$trade_type]);
		if(!is_array($datas)||count($datas)==0){
			throw new \Exception('eod opt error',\ErrMsg::RET_CODE_DATA_NOT_EXISTS);
		}
		$id = $datas[0]['id'];
		$params['id'] = $id;
		$obj->params = $params;
		$obj->saveOrUpdate();
	}
	
	public static function updateLimitDate($uuid , $trade_type , $limit_date){
		$obj = new static();
		$datas = $obj->loadDatas(['opt_uuid'=>$uuid,'trade_type'=>$trade_type]);
		
		foreach($datas as $d){
			$obj->params = [
			'id'=>$d['id'],
			'limit_date'=>empty($limit_date)?date('Y-m-d'):$limit_date
			];
			$obj->saveOrUpdate();
		}
	}
	
	//获取订单详情列表
	public function details($params , $cols , $page , $pageSize){
		$cols = $cols?$cols:'*';
		$where[] = ['main_body_uuid','in',$params['main_body_uuids']];
		$where[] = ['status','=',self::STATUS_CODE_UNOPT];
		$where[] = ['limit_date','<=',date('Y-m-d')];
		return $this->getDatasByPage($where, $cols, $page, $pageSize);
	}
	
	
}

?>