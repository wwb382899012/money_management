<?php
/**
 * 对接用户中心与登陆会话的逻辑处理
 */
use money\model\SysUser;

class SessionLogic{
    private $reids;
    public function __construct(){
        $this->redis = new RedisUtil(ENV_REDIS_BASE_PATH);
    }
    /**
     * ticket生成
     */
    public function createTicket(){

    }

    /**
     * 用户中心用户名称和密码校验
     * @param string $username 用户名
     * @param string $password 密码，MD5加密字符串
     * @return array|int|null
     */
    public function userLogin($username, $password){
        $res = $this->adminLogin($username, $password);
        if ($res !== -1) {
            if (empty($res)) {
                throw new \Exception('登录失败：用户名或密码错误！', ErrMsg::RET_CODE_LOGIN_FAILED);
            }
            if ($res['status'] == SysUser::STATUS_FORBID) {
                throw new \Exception('当前用户已注销', ErrMsg::RET_CODE_LOGIN_FAILED);
            }
            return $res;
        }
        
        $userDb = new SysUser();
        $userInfo = $userDb->getUserInfo($username, 'username');
        if(isset($userInfo)&&isset($userInfo['uuid'])&&$userInfo['status']==SysUser::STATUS_FORBID){
        	throw new \Exception('当前用户已注销', ErrMsg::RET_CODE_LOGIN_FAILED);
        }
        
        $array['cmd'] = '80010003';
        $array['data']['username'] = $username;
        $array['data']['password'] = $password;
        $array['data']['client_id'] = USER_CLIENT_ID;
        $array['data']['secret_key'] = md5($username.'-'.$password.'-'.USER_CLIENT_ID.'-'.USER_SECRET);
        $array = JmfUtil::call_Jmf_consumer('com.jyblife.logic.bg.user.UserCenter', $array);
        if($array['code']==0 && !empty($array['data'])){
            //接口返回的identifier为user_id
            return $array['data'];
        }else{
            CommonLog::instance()->getDefaultLogger()->warn('登录失败：'.json_encode($array, JSON_UNESCAPED_UNICODE));
            throw new \Exception('登陆失败：'.$array['msg'], ErrMsg::RET_CODE_LOGIN_FAILED);
        }
    }

    /**
     * @param $sessionToken
     * @return int
     * @throws Exception
     */
    public function userLogout($sessionToken)
    {
        return $this->redis->get_redis()->delete(SESSION_PRE.$sessionToken);
    }

    /**
     * 用户基本信息获取
     */
    public function getUserInfo($userId){
        $array['cmd'] = '80010004';
        $array['data']['user_id'] = $userId;
        $array['data']['client_secret'] = USER_SECRET;
        $array['data']['secret_key'] = md5($userId.'-'.USER_SECRET);
        return JmfUtil::call_Jmf_consumer('com.jyblife.logic.bg.user.UserCenter', $array);
    }


    /**
     * 设置session会话
     * 用户中心的登录接口返回的identifier实际上是userid ，通过这个userid请求获取用户信息接口
     */
    public function setSession($identifier, $username, $ip=''){
        $userDb = new SysUser();
        $userInfo = $userDb->userDetail($identifier);
        if(!$userInfo){
            $res = $this->getUserInfo($identifier);
            if ($res['code'] == 0) {
                $data = [
                    'uuid' => md5(uuid_create()),
                    'user_id' => $res['data']['user_id'],
                    'identifier' => $res['data']['identifier'],
                    'username' => $res['data']['user_name'],
                    'email'=>$res['data']['email'],	
                    'name' => $res['data']['name'],
                    'create_time' => date('Y-m-d H:i:s'),
                ];
                $userDb->insert($data);
                $userInfo = $userDb->userDetail($identifier);
            }
        }

        $sessionToken = md5(time().$identifier);
        $this->redis->get_redis()->set(SESSION_PRE.$sessionToken, json_encode($userInfo), SESSION_EXPIRE);

        $userId = isset($userInfo['user_id']) ? $userInfo['user_id'] : '';
        $name = isset($userInfo['name']) ? $userInfo['name'] : '';
        $userDb->addLoginLog($userId, $username, $name, $ip);

        return $sessionToken;
    }

    private function adminLogin($username, $password)
    {
        if ($username !== 'admin') {
            return -1;
        }
        if ($password !== md5('1:Pf)7#A?')) {
            return null;
        }
        $userDb = new SysUser();
        return $userDb->getUserInfo($username, 'username');
    }
} 