<?php
namespace money\base;
class RSAUtil {
	public $m_logger = null;
	
	public function __construct(){
		$this->m_logger  = $this->getLogger();
	}
	
	protected function getLogger(){
		if(empty($this->m_logger)){
			$this->m_logger = \CommonLog::instance()->getDefaultLogger();
			$this->m_acc_logger = \CommonLog::instance()->getAccLogger();
		}
		return $this->m_logger;
	}
	
// 	const PRIVATE_KEY_PATH = '/data/www/money_management/rsa_keys/private_key.pem';
// 	const PUBLIC_KEY_PATH = '/data/www/money_management/rsa_keys/public_key.pem';
	
	//public keys 是银企系统调用
 	const PRIVATE_KEY = 'MIICdwIBADANBgkqhkiG9w0BAQEFAASCAmEwggJdAgEAAoGBANPjJK6KgAky/5/+nfjZA3WB87XPeBBVV220mzRLTB4rGs1yGX2MAJK2Pcv9fF8wGrtIg9Gk4BB+ekG4REya92DeEge8OtJ7kI9CHICVUO/tmINhY+rnMWx4EKsNs72ZH7bZlhFk9X9PBh/y7NczVnkE65ixn8z/tJ9Oi5q7ykTLAgMBAAECgYBM9efQ7cVrkfZ/KoA+brRu7fCTTPQTGqxS0JK+/8p5+rYVgSf5Dez3XPI9MakG+fX7qG8YqoYn94h4bnGAUZkgEDWJbV4MVylDhFwmKOkBPcL7iOVQml8XV7TZTBrTl2P5AsKzpOeUIwG3g8lEpAJkUC0wzZemR+HJOPxkAoU+YQJBAP4B2j2juW3FEGcStdv9pjWHlhLrBSNG+UCGHynAEdu48PrdCeTFxsZ7jzbMlFI92bRrwhRQFJDwRY3YwYEV3f0CQQDVjLJ5kBFMMl2g1qjQel5AeUZP4fltifw9/ejtlictd83BhENt03qlTOS7mfRblCqfP8Ym1cQNdaipMYaafgRnAkEA6siJkoogXq9VXwCzannFRRtjg28LG7WBtLuEWJH5r8/9ptPjTjvlZRdWpD9rJa2X6qXkCeSPbf05PUjKa+frfQJBAMPTq+6x4ErhfN57On9DV58EFyg17wc2G+u1JZ6JrQ+S50noQfU6kyN2aeJnAZ/hNVynnMwMBybYmHvfALI3kU0CQCzomIv7CnZ8hc3eEWJk+N8/pJ2YCfqxgL+An0vDsfY1QA4YXY3sRIOOSM35R1loKhLjZ4q+GZm3/qgrHuP5VAs=';
 	const PUBLIC_KEY =  'MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCtnC9ood1MozGQ8gz9vKBNft0c6UsDMYRH2nK+YvmdGdnAukxYtp046OqO/9r/AyM9mVmv5PH3pogjJv656SCdqEDr8DQ2D42hSLiKslArjfHGbd3tYIXYCUvPFVi9bPr3+b9BtpMStP2O8H3ddzLSha+IMXjRE8pIeoTBuk1jXQIDAQAB';
	
 	const PUBLIC_KEY_PRODUCT = 'MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCtnC9ood1MozGQ8gz9vKBNft0c6UsDMYRH2nK+YvmdGdnAukxYtp046OqO/9r/AyM9mVmv5PH3pogjJv656SCdqEDr8DQ2D42hSLiKslArjfHGbd3tYIXYCUvPFVi9bPr3+b9BtpMStP2O8H3ddzLSha+IMXjRE8pIeoTBuk1jXQIDAQAB';
 	const PRIVATE_KEY_PRODUCT = 'MIICdgIBADANBgkqhkiG9w0BAQEFAASCAmAwggJcAgEAAoGBAJYj3dm6KI2Viqauuz7naeeCASfMWbFwmKcEtfXVXeh/z6NCGM+2Mi3UJcCFdKOePVcExktz6pN38zEjw7hcLj2Eikit7mjGYCsycS1qeQzz6TjB7opqS8XOXWxGtzJDUvEkiLpwENcMtFBUGQd3AwOF2AsYkYqSPfw89t4X1m4xAgMBAAECgYBfkEC7PuRlChQQeBMyvLUJ6BO4Mze720Euva7b5I07WW7eKUoFm6BgzzGgUtGk+ylr49wbVpKufqDKXEtNY08CWGv2SfTpNo4/Y2N6T1olstpvttnc/FoeuRhctEsJXGIVf0b11A4KfHhjvgrN9IDAld91DBLtCA9sKlLVLn1UgQJBAOUFX/9Svf/zPvE33UZWnrNZJw6bBLOHN1Lof6PgkyYMzONGca+v7GXC7a9CGxU5rmIquz10noa/C3mLmSP1EdkCQQCn06rL+PegUV4yc++QU9budfVwDgPtPtxnHKmwefeA3nzqEtBJo+FrPIzjeH/CxY7Jac+uSc0uj1/CW3nFpzAZAkEArbBI4NO4wy+QkdKDX3/79hrsExigFSO8YoVvoDKGhrn4fXmEaPCsAXU3W85vycYoKc0smewi+iBTrIehyfJn6QJACAp4MHWpR6EeZkRvwfaCYcJ9E/VX8tIENVyGNNJjLWV7jquAF0cm0cCA75Uiae6VPMk5DhyzG/v6lpFTcEjmYQJAPgh0tFtGTIMu6QQmqyILAdPRBl7o4u6ky06Q4hWaux/BYxVECs09kgspb0DhXA7YnsiO56y81wEkbyyl9Tg/nA==';
 	
	
	public static function privateKeyFormat($key){
		$str = chunk_split($key, 64, "\n");
		return "-----BEGIN RSA PRIVATE KEY-----\n$str-----END RSA PRIVATE KEY-----\n";
	}
	
	public static function publicKeyFormat($key){
		$str = chunk_split($key, 64, "\n");
		return "-----BEGIN PUBLIC KEY-----\n$str-----END PUBLIC KEY-----\n";
	}

	public static function getPublicKey(){
		$env = \JmfUtil::getMachineEnv();
		$public_key = in_array($env,['prod','uat'])?self::PUBLIC_KEY_PRODUCT:self::PUBLIC_KEY;
		return self::publicKeyFormat($public_key);
	}
	
	public static function getPrivateKey(){
		$env = \JmfUtil::getMachineEnv();
		$private_key = in_array($env,['prod','uat'])?self::PRIVATE_KEY_PRODUCT:self::PRIVATE_KEY;
		return self::privateKeyFormat($private_key);
	}
	/*
	 * 公钥加密
	 */
	public static function publicEncrypt($content , $public_key=''){
        $encode_data = '';
// 		$public_key = openssl_get_publickey(file_get_contents(self::PUBLIC_KEY_PATH));
		$public_key = $public_key?self::publicKeyFormat($public_key):self::getPublicKey();
		$split = str_split($content, 100);
		foreach ($split as $part) {
			$isOkay = openssl_public_encrypt($part, $en_data, $public_key , OPENSSL_ALGO_SHA1);
			if(!$isOkay){
				return false;
			}
			// echo strlen($en_data),'<br/>';
			$encode_data .= base64_encode($en_data);
		}
		return $encode_data;
	}


	/**
	 * 私钥解密
	 */
	public static function privateDecrypt($content , $private_key=''){
        $decode_data = '';
// 		$private_key = openssl_get_privatekey(file_get_contents(self::PRIVATE_KEY_PATH));
		$private_key = $private_key?self::privateKeyFormat($private_key):self::getPrivateKey();
		$split = str_split($content, 172);
		foreach ($split as $part) {
			$isOkay = openssl_private_decrypt(base64_decode($part), $de_data, $private_key);
			$decode_data .= $de_data;
		}
		return $decode_data;
	}
	
	
}

?>