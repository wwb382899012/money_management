<?php
/**
 * Class ReadNews
 */
use money\service\BaseService;
use money\model\SysWebNews;

class ReadNews extends BaseService{

    protected $rule = [
        'sessionToken' => 'require',
        'news_uuids' => 'require|array',
    ];

    public function exec(){
        $uuids = $this->m_request['news_uuids'];
        $db = new SysWebNews();
        $rows = $db->updateStatus($uuids, 2);
        $this->packRet(ErrMsg::RET_CODE_SUCCESS, ['rows'=>$rows]);
    }
}