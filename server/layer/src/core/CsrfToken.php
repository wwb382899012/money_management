<?php
/**
 * cstf token 验证类
 */
class CsrfToken{
    private $redis_token_pre = 'm_money_redis_csrf_token_pre';

    private $redis_obj;
    public function __construct(){
        $this->redis_obj = new RedisUtil(ENV_REDIS_BASE_PATH);
    }

    /**
     * 验证token
     */
    public function checkToken($token){
        $token = $this->encryptToken($token);
        $result = $this->redis_obj->get_redis()->get($this->redis_token_pre.$token);
        if($result){
            $this->redis_obj->get_redis()->del($this->redis_token_pre.$token);
            return true;
        }
        return false;
    } 

    /**
     * 获取加密后的token
     */
    public function getToken(){
        $token = md5(uniqid().rand(1000,9999).time());
        $this->redis_obj->get_redis()->set($this->redis_token_pre.$token, 1);
        $this->redis_obj->get_redis()->expire($this->redis_token_pre.$token, 600);
        return $token;
    }

    /**
     * 解密token
     */
    protected function encryptToken($token){
        $filePath = APPLICATION_PROJECT_PATH.'src'.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'rsa_1024_priv.pem';
        $fp = fopen($filePath,"r");
        $priv_key=fread($fp,8192);
        fclose($fp);
        $res = openssl_get_privatekey($priv_key);
        openssl_private_decrypt(base64_decode($token),$newsource,$res);
        return $newsource;
    }
}