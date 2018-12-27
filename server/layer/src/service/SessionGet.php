<?php
/**
 * 会话数据获取
 */
use money\service\BaseService;

class SessionGet extends BaseService{

    protected $rule = [
        'sessionToken' => 'require',
    ];

    public function exec(){
        $sessionToken = $this->m_request['sessionToken'];
        $redisObj = new RedisUtil(ENV_REDIS_BASE_PATH);
        $sessionInfo = $redisObj->get_redis()->get(SESSION_PRE.$sessionToken);
        if(!($userInfo=json_decode($sessionInfo, true))){
            throw new \Exception('无会话数据', ErrMsg::RET_CODE_NOTLOGIN);
        }
        $this->packRet(ErrMsg::RET_CODE_SUCCESS, $userInfo);

    }
}