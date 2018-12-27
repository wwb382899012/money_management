<?php
/**
 * 全量交易报表
 */
use money\service\BaseService;
use money\model\EodTradeDb;
use money\model\MainBody;
class EodTrade extends BaseService{

    protected $rule = [
        'sessionToken' => 'require',
    ];

    public function exec(){
//         $params = $this->m_request;
//         if (!empty($params['generate'])) {
//             $lEodTrade = new EodTradeLogic();
//             $lEodTrade->start();
//         }
//         $page = $this->m_request['page'];
//         $limit = $this->m_request['limit'];
//         $mEodTrade = new ReportEodTrade();
//         $result = $mEodTrade->listData($page, $limit);
    	$params = $this->m_request;
    	$main_body_ids = MainBody::getMainBodys($this->m_request['sessionToken']);
		if(count($main_body_ids)==0){
			$result = ['page'=>$this->getDataByArray($params, 'page'), 'limit'=>$this->getDataByArray($params, 'limit'), 'count'=>0, 'data'=>[]];
			$this->packRet(ErrMsg::RET_CODE_SUCCESS, $result);
			return;
		}		
		$queryArray['main_body_uuids'] = $main_body_ids;
		$obj = new EodTradeDb();
		$ret = $obj->details($queryArray,'*',$this->getDataByArray($params, 'page')
				,$this->getDataByArray($params, 'limit'));
		
		$data = $ret['data'];
		$data = MainBody::changeUuidToName($data , 'main_body_uuid' , 'main_body');
		$ret['data'] = $data;
        $this->packRet(ErrMsg::RET_CODE_SUCCESS, $ret);
    } 
}