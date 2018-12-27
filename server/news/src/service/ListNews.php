<?php
/**
 * Class ListNews
 */
use money\service\BaseService;
use money\model\SysWebNews;

class ListNews extends BaseService{

    protected $rule = [
        'sessionToken' => 'require',
        'page' => 'integer',
        'limit' => 'integer',
    ];

    public function exec(){
//$this->test1($this->m_request['sessionToken']);

        $status = isset($this->m_request['status']) ? $this->m_request['status']:null;
        if($status == 1){
            $status = null;
        }else if($status == 2){
            $status = 1;
        }else if($status == 3){
            $status = 2;
        }
        $page = isset($this->m_request['page']) ? $this->m_request['page'] : 1;
        $limit = isset($this->m_request['limit']) ? $this->m_request['limit'] : 50;
        $sessionToken = $this->m_request['sessionToken'];
        $sessionInfo = JmfUtil::call_Jmf_consumer('com.jyblife.logic.bg.layer.SessionGet', ['sessionToken'=>$sessionToken]);
        if(!isset($sessionInfo['code']) || $sessionInfo['code'] != '0' || !isset($sessionInfo['data']['user_id'])){
            $code = isset($sessionInfo['code']) ? $sessionInfo['code'] : ErrMsg::RET_CODE_SERVICE_FAIL;
            $msg = isset($sessionInfo['msg']) ? $sessionInfo['msg'] : '获取会话信息失败';
            throw new \Exception($msg, $code);
        }
        $curUserId = $sessionInfo['data']['user_id'];

        $db = new SysWebNews();
        $result = $db->listData($page, $limit, $curUserId, $status);

        foreach($result['data'] as &$row){
            if(!strtotime($row['send_datetime'])){
                continue;
            }
            if($row['business_son_type'] == 'redemption'){
                $day = ceil(strtotime($row['send_datetime']) - strtotime(date('Y-m-d').' 00:00:01')/(24*3600));
                if($day == 0){
                    $str = '今';
                }else{
                    $str = $day;
                }
                $row['content'] = str_replace('{day}', $str, $row['content']);
            }
        }
        $this->packRet(ErrMsg::RET_CODE_SUCCESS, $result);
    }
}