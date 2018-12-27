<?php
/**
 * 全量交易报表
 */
use money\service\BaseService;
use money\model\ReportFullTrade;
use money\model\MainBody;
class FullTrade extends BaseService{

    protected $rule = [
        'sessionToken' => 'require',
        'page' => 'require',
        'limit' => 'require',
    ];

    public function exec(){
        $params = $this->m_request;
        $page = $this->m_request['page'];
        $limit = $this->m_request['limit'];
        $main_body_ids = MainBody::getMainBodys($this->m_request['sessionToken']);
        if(count($main_body_ids)==0){
        	$result = ['page'=>$this->getDataByArray($params, 'page'), 'limit'=>$this->getDataByArray($params, 'limit'), 'count'=>0, 'data'=>[]];
        	$this->packRet(ErrMsg::RET_CODE_SUCCESS, $result);
        	return;
        }
        $params['pay_main_body_uuids'] = $main_body_ids;
        $db = new ReportFullTrade();
        $result = $db->listData($page, $limit, $params);
        $this->packRet(ErrMsg::RET_CODE_SUCCESS, $result);
    }
}