<?php

/**
*	主体
*	@author sun
*	@since 2018-03-11
*/
namespace money\model;

class MainBody extends BaseModel
{
	protected $table = "m_main_body";

	// 内部主体
	const TYPE_INTERNAL = 1;
	const TYPE_OUTSIDE = 2;

	public static function changeUuidToName($objs , $from_key , $to_key)
	{
        $uuids = array_unique(array_column($objs, $from_key));
        $obj = new static();
        $ret = $obj->field('uuid, full_name')->where(['uuid' => $uuids])->select()->toArray();
		if(empty($ret)){
// 			throw new \Exception('主体id不存在！',\ErrMsg::RET_CODE_DATA_NOT_EXISTS);
			return $objs;
		}
		$map = array();
		foreach($ret as $mb)
		{
			$map[$mb['uuid']] = $mb['full_name'];
		}

		$ret = array();
		foreach($objs as $obj)
		{
			$obj[$to_key] = $map[$obj[$from_key]];
			$ret[] = $obj;
		}
		return $ret;
	}

	public function details($queryArray , $cols , $page , $pageSize){
		$cols = $cols?$cols:'*';
        $where[] = ['is_delete', '=', self::DEL_STATUS_NORMAL];
        isset($queryArray['uuid']) && $where[] = ['uuid', '=', $queryArray['uuid']];
        isset($queryArray['status']) && $where[] = ['status', '=', $queryArray['status']];
        isset($queryArray['name']) && $where[] = ['short_name|full_name', 'like', "%{$queryArray['name']}%"];
        isset($queryArray['is_internal']) && $where[] = ['is_internal', '=', $queryArray['is_internal']];
		isset($queryArray['uuids'])&& $where[] = ['uuid' , 'in' , $queryArray['uuids']];
        return $this->getDatasByPage($where, $cols, $page, $pageSize, ["update_time" => "desc"]);
	}
    	
	public static function getByName($name){
		$obj = new self();
		$params = array(
			'full_name'=>$name,
			'is_delete'=>1,
			'status'=>1
		);
        return $obj->getOne($params);
	}
	
	public static function validateAuth($sessionToken , $main_body_uuid){
		$sessionInfo = \JmfUtil::call_Jmf_consumer('com.jyblife.logic.bg.layer.SessionGet', ['sessionToken'=>$sessionToken]);
		if(!isset($sessionInfo['code']) || $sessionInfo['code'] != '0' || !isset($sessionInfo['data']['user_id'])){
			$code = isset($sessionInfo['code']) ? $sessionInfo['code'] : \ErrMsg::RET_CODE_SERVICE_FAIL;
			$msg = isset($sessionInfo['msg']) ? $sessionInfo['msg'] : '获取会话信息失败';
			throw new \Exception($msg, $code);
		}
		$obj = new MainBody();
		$sql = "select main_body_uuid from m_sys_user_main_body where is_delete=1 and user_id = ".$sessionInfo['data']['user_id'];
		$ret = $obj->query($sql);
		if(count($ret)==0){
			throw new \Exception('用户未绑定主体',\ErrMsg::RET_CODE_DATA_VALIDATE_ERROR);
		}
		$ids = array_column($ret, 'main_body_uuid');
		if(!in_array($main_body_uuid , $ids)){
			throw new \Exception('该用户无权访问这条数据',\ErrMsg::RET_CODE_DATA_VALIDATE_ERROR);
		}
	}
	
	public static function getMainBodys($sessionToken){
		$sessionInfo = \JmfUtil::call_Jmf_consumer('com.jyblife.logic.bg.layer.SessionGet', ['sessionToken'=>$sessionToken]);
		if(!isset($sessionInfo['code']) || $sessionInfo['code'] != '0' || !isset($sessionInfo['data']['user_id'])){
			$code = isset($sessionInfo['code']) ? $sessionInfo['code'] : \ErrMsg::RET_CODE_SERVICE_FAIL;
			$msg = isset($sessionInfo['msg']) ? $sessionInfo['msg'] : '获取会话信息失败';
			throw new \Exception($msg, $code);
		}
		$obj = new MainBody();
		$sql = "select main_body_uuid from m_sys_user_main_body where is_delete=1 and user_id = ".$sessionInfo['data']['user_id'];
		$ret = $obj->query($sql);
		return array_column($ret, 'main_body_uuid');
	}
	
    public function validateDulicate($full_name , $short_name ,$short_code, $uuid=null){
    	$sql = "select * from m_main_body where (full_name = '$full_name' or short_name = '$short_name' or (short_code is not null and short_code!='' and short_code='$short_code'))  and is_delete=1 ";
    	if($uuid){
    		$sql = $sql."and uuid!='$uuid'";
    	}
    	$ret = $this->query($sql);
    	
    	if(is_array($ret)&&count($ret)>0){
    		return true;
    	}
    	return false;
    }

    /**
     * 推送银行账户主体变更的消息到队列
     * @param $uuid
     */
    public function pushAccountMsgToMq($uuid)
    {
        if ($mainBody = $this->getOne(['uuid' => $uuid, 'is_delete' => self::DEL_STATUS_NORMAL], 'uuid, full_name')) {
            $mBankAccount = new BankAccount();
            $where = [
                'main_body_uuid' => $uuid,
                'status' => 0,
                'deal_status' => 1,
                'is_delete' => BankAccount::DEL_STATUS_NORMAL,
            ];
            $bankAccountList = $mBankAccount->getAll($where, 'uuid');
            $amqpUtil = new \AmqpUtil();
            foreach ($bankAccountList as $item) {
                $ex = $amqpUtil->exchange(MQ_EXCHANGE_ACCOUNT);
                $ex->publish(json_encode(['uuid' => $item['uuid'], 'status' => 0, 'deal_status' => 1, 'old_main_body_name' => $mainBody['full_name']]), MQ_ROUT_ACCOUNT_NOTIFY);
            }
        }
    }

    /**
     * 发布外部主体新增消息
     * @param $uuid
     */
    public function pushNewOutsideMainBodyMsg($uuid)
    {
        $amqpUtil = new \AmqpUtil();
        $ex = $amqpUtil->exchange("");
        $ex->publish();

    }
}
