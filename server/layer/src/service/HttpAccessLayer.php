<?php
/**
 * web ajax http 请求接入
 */
use money\service\BaseService;

class HttpAccessLayer extends BaseService{
    private $request;

    /**
     * web前端接入
     */
    public function access($params){
        try{
            $sessionToken = $this->filter();
        }catch(\Exception $e){
            return json_encode(['code'=>$e->getCode(), 'msg'=>$e->getMessage()]);
        }
        if(is_string($params)){
            $array = json_decode($params, true);  
        }else{
            $array = $params;
        }
        if(is_string($params) && !$array){
            $array = [];
        }
        // 目标服务
        $post = json_decode($this->request->rawContent(), true);
        $targetService = $post['targetService'];

        if($targetService == 'com.jyblife.logic.bg.user.UserLogin'){
            $array['remote_addr'] = isset($this->request->server['remote_addr']) ? $this->request->server['remote_addr']:'';
        }
        $array['sessionToken'] = $sessionToken;

        // xss 过滤
        $xssFilter = new XssFilter();
        $params = $xssFilter->result($array);

        $result = JmfUtil::call_Jmf_consumer($targetService, $params, 10);
        if (in_array($targetService, Params::GetcsrfTokenServer())){
            $csrfObj = new CsrfToken();
            $token = $csrfObj->getToken();
            $result['csrfToken'] = $token;
        }
        return $result;
    }

    /**
     * 获取csrftoken
     */
    public function csrfToken($params){
        $csrfObj = new CsrfToken();
        $token = $csrfObj->getToken();
        return $this->successRet(['token'=>$token]);
    }


    /**
     * 设置返回值
     */
    protected function successRet($array){
        return json_encode(['code'=>ErrMsg::RET_CODE_SUCCESS, 'data'=>$array, 'msg'=>'success']);
    }

    /**
     * 防御过滤
     */
    protected function filter(){
        $sessionToken = isset($this->request->cookie['Session-token']) ? $this->request->cookie['Session-token'] :'';
        $post = json_decode($this->request->rawContent(), true);
        if (!isset($post['targetService']) && $post['method']=='access'){
            throw new \Exception('targetService不存在', ErrMsg::RET_CODE_INVALID_TARGET_SERVICE_ERROR);
        }

        // csrf验证
        $targetService = $post['targetService'];
        if (in_array($targetService, Params::GetcsrfTokenServer())){
            if(!isset($this->request->header['csrf-token'])){
                throw new \Exception('csrf-Token值不存在', ErrMsg::RET_CODE_GENVERIFY_TOKEN_ERROR);
            }
            $csrfToken = $this->request->header['csrf-token'];
            $csrfObj = new CsrfToken();
            if(!$csrfToken || !$csrfObj->checkToken($csrfToken)){
                throw new \Exception('csrf-Token验证失败', ErrMsg::RET_CODE_GENVERIFY_TOKEN_ERROR);
            }
        }

        // 会话，权限验证
        if(!in_array($targetService, Params::noNeedSessionCheckServer()) && $post['method']=='access'){
            $redisObj = new RedisUtil(ENV_REDIS_BASE_PATH);
            if(!$sessionToken || !($sessionInfo = $redisObj->get_redis()->get(SESSION_PRE.$sessionToken))){
                throw new \Exception('会话验证失败', ErrMsg::RET_CODE_NOTLOGIN);
            }
            $sessionInfo = json_decode($sessionInfo, true);
            if(!$sessionInfo){
                throw new \Exception('会话数据错误', ErrMsg::RET_CODE_NOTLOGIN);
            }
            $redisObj->get_redis()->expire(SESSION_PRE.$sessionToken, SESSION_EXPIRE);
            //admin不校验targetService，部分公共模块无需校验
            if ($sessionInfo['username'] == 'admin' || in_array($targetService, Params::noNeedTargetServiceCheck())) {
                return $sessionToken;
            }
            if(empty($sessionInfo['targetService'])){
                $sessionInfo['targetService'] = [];
                foreach($sessionInfo['role'] as $urow){
                    $array['sessionToken'] = $sessionToken;
                    $array['role_uuid'] = $urow['uuid'];
                    $roleDetail = JmfUtil::call_Jmf_consumer('com.jyblife.logic.bg.role.RoleDetail', $array);
                    if($roleDetail['code'] != 0){
                        continue;
                    }
                    foreach($roleDetail['data']['module_uuids'] as $row){
                        $pre = $row['module_server'];
                        if(!$row['son']){
                            $sessionInfo['targetService'][] = $pre;
                        }else{
                            $this->m_logger->info(var_export($row['son'], true));
                            $sonService = (strstr($row['son'], ',')!==false) ? explode(",", $row['son']): [$row['son']];
                            foreach ($sonService as $value) {
                                $sessionInfo['targetService'][] = $pre.".".$value;
                            }
                        }
                    }                    
                }

                $redisObj->get_redis()->set(SESSION_PRE.$sessionToken, json_encode($sessionInfo), SESSION_EXPIRE);
            }
            if(!in_array($targetService, $sessionInfo['targetService'])){
                $this->m_logger->info(var_export($sessionInfo['targetService'], true));
                throw new \Exception('服务鉴权失败：'.$targetService, ErrMsg::RET_CODE_INVALID_PRIV_ERROR);
            }
        }
        return $sessionToken;
    }

    /**
     * 回设request对象
     */
    public function setRequest($request){
        $this->request = $request;
    }
}