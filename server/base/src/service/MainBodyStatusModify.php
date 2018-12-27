<?php
/**
 * 主体状态变更
 * @author sun
 * @since 2018-03-28
 */
use money\service\BaseService;
use money\model\MainBody;

class MainBodyStatusModify extends BaseService{

    protected $rule = [
        'sessionToken'=>'require',
        'uuid'=>'require',
        'status'=>'require|integer',
    ];

	public function exec() {
		$obj = new MainBody();
		$params['uuid'] = $this->m_request['uuid'];
		$params['status'] = $this->m_request['status'];

        $obj->save($params, ['uuid' => $params['uuid']]);
        $obj->pushAccountMsgToMq($this->m_request['uuid']);
		$this->packRet(ErrMsg::RET_CODE_SUCCESS, null);
	}
}