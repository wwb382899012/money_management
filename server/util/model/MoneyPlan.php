<?php
/**
 * 理财计划管理
 */
namespace money\model;

class MoneyPlan extends BaseModel {

    protected $table = "m_money_manager_plan";

    const PLAN_STATUS_SAVED = 0;  //已保存
    const PLAN_STATUS_WAITING = 1;   //待资金负责人审核
    const PLAN_STATUS_OPTED = 2;     //待权签人审核
    const PLAN_STATUS_COMFIRMED = 3;  //权签人审核通过
    const PLAN_STATUS_REJECT = 4;     //资金负责人驳回
    const PLAN_STATUS_CHECK_REJECT = 5;     //权签人人驳回
    const PLAN_STATUS_WAIT_TICKET_BACK = 19; //待上传回单
    const PLAN_STATUS_ARCHIVE = 20;   //已完结

    const PAY_STATUS_UNPAID = 0;
    const PAY_STATUS_PAYING = 1;
    const PAY_STATUS_PAID = 2;

    /**
     * 购买产品
     */
    public function savePlanAndCash($planData, &$cashData, $uuid=null){
        try{
            $this->startTrans();
            $uuid = $this->savePlan($planData, $uuid);
            if(!$uuid){
                throw new \Exception('添加理财计划失败', \ErrMsg::RET_CODE_SERVICE_FAIL);
            }
            if(!empty($cashData) && $this->saveCashFlow($cashData, $uuid) === false){
                throw new \Exception('添加理财现金流失败', \ErrMsg::RET_CODE_SERVICE_FAIL);
            }
            $this->commit();
            return $uuid;                  
        }catch(\Exception $e){
            $this->rollback();
            throw $e;
        }
    }

    /**
     * 保存产品计划
     */
    public function savePlan($data, $uuid=null){
        if (!empty($uuid)) {
            $res = $this->where(['uuid' => $uuid, 'is_delete' => self::DEL_STATUS_NORMAL])->update($data);
        } else {
            $uuid = $data['uuid'] = md5(uuid_create());
            $data['create_time'] = date('Y-m-d H:i:s');
            $res = $this->insert($data);
        }
        return !empty($res) ? $uuid : null;
    }

    /**
     * 保存产品现金流
     */
    public function saveCashFlow(&$cashData, $planUuid){
        try{
            $this->startTrans();
            foreach($cashData as &$row){
                $audit = $row['audit'] ?? null;
                unset($row['audit']);
                if(isset($row['uuid'])){
                    if ($this->table('m_money_manager_cash_flow')->where(['uuid' => $row['uuid']])->update($row) === false) {
                        throw new \Exception('现金流数据更新失败', \ErrMsg::RET_CODE_SERVICE_FAIL);
                    }
                }else{
                    $row['uuid'] = md5(uuid_create());
                    $row['create_time'] = date('Y-m-d H:i:s');
                    $row['money_manager_plan_uuid'] = $planUuid;
                    if ($this->table('m_money_manager_cash_flow')->insert($row) === false) {
                        throw new \Exception('现金流新增数据失败', \ErrMsg::RET_CODE_SERVICE_FAIL);
                    }
                }
                !is_null($audit) && $row['audit'] = $audit;
            }
            $this->commit();
            return true;
        }catch(\Exception $e){
            $this->rollback();
            return false;
        }
    }

    /**
     * 理财计划详情
     */
    public function detail($uuid){
        $data = $this->field('p1.*, p2.product_name as money_manager_product_name')
            ->alias('p1')
            ->leftJoin('m_money_manager_product p2', 'p1.money_manager_product_uuid=p2.uuid')
            ->where(['p1.uuid' => $uuid, 'p1.is_delete' => self::DEL_STATUS_NORMAL])
            ->find();
        if(empty($data)){
            return null;
        }
        $data = $data->toArray();
        $data['cash_flow'] = $this->table('m_money_manager_cash_flow')->field('*')->where(['money_manager_plan_uuid' => $uuid, 'is_delete' => self::DEL_STATUS_NORMAL])->order('repay_date, index, cash_flow_type')->select()->toArray();
        return $data;
    }

    /**
     * 理财审核处理
     * @param int $step 0未提交 1处理中 2已处理 3已驳回 20完结归档 
     */
    public function auditStep($uuid, $step, $payStatus=null){
        //更新现金流表的本金支付
        $where = [
            ['money_manager_plan_uuid', '=', $uuid],
            ['cash_flow_type', '=', 1],
            ['is_delete', '=', self::DEL_STATUS_NORMAL],
        ];
        $this->table('m_money_manager_cash_flow')->where($where)->update(['status' => $step]);

        //更新理财计划表
        $where = [
            ['uuid', '=', $uuid],
            ['plan_status', '<>', $step],
            ['is_delete', '=', self::DEL_STATUS_NORMAL],
        ];
        $data['plan_status'] = $step;
        !empty($payStatus) && $data['pay_status'] = $payStatus;
        $step == self::PLAN_STATUS_ARCHIVE && $data['pay_status'] = self::PAY_STATUS_PAID;


        return $this->where($where)->update($data);
    }

    /**
     * 批量赎回审核处理
     * @param $cashFlows
     * @param $before
     * @param $after
     * @return bool
     */
    public function batchRedemAuditStep($cashFlows, $before, $after)
    {
        try{
            $this->startTrans();
            foreach($cashFlows as $row){
                if ($this->redemAuditStep($row, $before, $after) === false) {
                    throw new \Exception('现金流数据更新失败');
                }
            }
            $this->commit();
            return true;
        }catch(\Exception $e){
            $this->rollback();
            throw $e;
        }
    }

    /**
     * 赎回审核处理
     * @param $cashFlow
     * @param int $before 0未提交 1处理中 2已处理 3已驳回 20完结归档
     * @param int $after 0未提交 1处理中 2已处理 3已驳回 20完结归档
     */
    public function redemAuditStep($cashFlow, $before, $after){
        $where = [
            ['uuid', '=', $cashFlow['uuid']],
            ['is_delete', '=', self::DEL_STATUS_NORMAL],
            ['status', '=', $before],
        ];
        $data = [
            'change_amount' => $cashFlow['change_amount'] ?? 0,
            'info' => $cashFlow['info'] ?? '',
            'status' => $after,
            'bank_water' => $cashFlow['bank_water'] ?? '',
            'bank_img_file_uuid' => $cashFlow['bank_img_file_uuid'] ?? '',
        ];
        return $this->table('m_money_manager_cash_flow')->where($where)->update($data);
    }

    /**
     * 理财计划设置为已还清
     * @param $planUuid
     */
    public function setPayOff($planUuid)
    {
        $where = [
            ['money_manager_plan_uuid', '=', $planUuid],
            ['is_delete', '=', self::DEL_STATUS_NORMAL],
        ];
        $list = $this->getAll($where, '*', 'cash_flow_type asc', 'm_money_manager_cash_flow');
        $investmentAmount = 0;
        $redemptionAmount = 0;
        foreach ($list as $item) {
            if ($item['cash_flow_type'] == 1) {
                $investmentAmount = $item['amount'] - $item['change_amount'];
            } elseif ($item['cash_flow_type'] == 2 && in_array($item['status'], [self::PLAN_STATUS_WAIT_TICKET_BACK, self::PLAN_STATUS_ARCHIVE])) {
                $redemptionAmount += $item['amount'] - $item['change_amount'];
            }
        }
        //1本金支付 2本金回款 3利息回款
        //当本金金额总和（除去已驳回的本金回款）等于投资总本金时，视为此笔理财结束，不可再发起赎回。
        if ($investmentAmount == $redemptionAmount) {
            $this->savePlan(['is_pay_off' => 2], $planUuid);
        }
    }

    /**
     * 产品计划列表
     */
    public function listData($page, $pageSize, $params=[]){
        $where = [
            ['is_delete', '=', self::DEL_STATUS_NORMAL],
        ];
        if (isset($params['money_manager_plan_num'])) {
            $where[] = ['money_manager_plan_num', '=', $params['money_manager_plan_num']];
        }
        if (isset($params['plan_status'])) {
            $where[] = ['plan_status', '=', $params['plan_status']];
        }
        if (isset($params['pay_status'])) {
            $where[] = ['pay_status', '=', $params['pay_status']];
        }
        if(isset($params['is_pay_off'])){
            $where[] = ['is_pay_off', '=', $params['is_pay_off']];
        }
        if(!empty($params['plan_main_body_uuid'])){
            $where[] = ['plan_main_body_uuid', '=', $params['plan_main_body_uuid']];
        }
        if(!empty($params['pay_bank_name'])) {
            $where[] = ['pay_bank_name', 'like', "%{$params['pay_bank_name']}%"];
        }
        if(!empty($params['apply_begin_time'])){
            $where[] = ['create_time', '>=', $params['apply_begin_time']];
        }
        if(!empty($params['apply_end_time'])){
            $where[] = ['create_time', '<=', $params['apply_end_time']];
        }
        if(!empty($params['approve_begin_time'])){
            $where[] = ['update_time', '>=', $params['approve_begin_time']];
        }
        if(!empty($params['approve_end_time'])){
            $where[] = ['update_time', '<=', $params['approve_end_time']];
        }
        
        if(!empty($params['main_body_uuids'])&&is_array($params['main_body_uuids'])&&count($params['main_body_uuids'])>0){
        	$where[] = ['plan_main_body_uuid','in',$params['main_body_uuids']];
        }
        return $this->getDatasByPage($where, '*', $page, $pageSize);
    }

    /**
     * 赎回详情
     */
    public function redemDetail($plan_id){
        $where = [
            ['money_manager_plan_uuid', '=', $plan_id],
            ['is_delete', '=', self::DEL_STATUS_NORMAL],
            ['status', '<>', MoneyPlan::PLAN_STATUS_CHECK_REJECT],
            ['cash_flow_type', 'in', [2, 3]],
        ];
        return $this->getAll($where, '*', null, 'm_money_manager_cash_flow');
    }

    /** 
     * 删除理财
     */
    public function deletePlan($uuid){
        return $this->where(['uuid' => $uuid])->update(['is_delete' => self::DEL_STATUS_DELED]);
    }

    /**
     *	获取内部理财计划编号
     */
	public static function getOrderNum($main_body_code){
		// return date('Ymd').$redisObj->incr(self::REDIS_KEY_PAY_ORDER_NUM_KEY);
		$util = new \RedisUtil ( ENV_REDIS_BASE_PATH );
		$redis = $util->get_redis ();
		
		$nday = date ( "Ymd" );
		$key = '05-TR-' . $main_body_code . '-' . $nday;
		$seqid = 0;
		if (! ($seqid = $redis->incr ( $key ))) {
			throw new \Exception ( '获取编号失败', \ErrMsg::RET_CODE_LOAD_ORDER_NUM_ERROR );
		}
		
		if ($seqid == 1) {
			$redis->expire ( $key, 86400 );
		}
		
		$t_seq = sprintf ( "%05d", $seqid );
		
		return '05-TR-' . $main_body_code . '-' . $nday . '-' . $t_seq;
	}

	public function finishRedemption($cashData, $planUuid)
    {
        try{
            $this->startTrans();
            foreach($cashData as $row){
                $where = [
                    ['uuid', '=', $row['uuid']],
                    ['cash_flow_type', 'IN', [2, 3]],
                    ['status', '=', self::PLAN_STATUS_WAIT_TICKET_BACK],
                    ['is_delete', '=', self::DEL_STATUS_NORMAL],
                ];
                $data = [
                    'bank_water' => $row['bank_water'] ?? '',
                    'bank_img_file_uuid' => $row['bank_img_file_uuid'] ?? '',
                    'status' => MoneyPlan::PLAN_STATUS_ARCHIVE,
                ];
                if ($this->table('m_money_manager_cash_flow')->where($where)->update($data) === false) {
                    throw new \Exception('现金流数据更新失败', \ErrMsg::RET_CODE_SERVICE_FAIL);
                }
            }
            $this->setPayOff($planUuid);
            $this->commit();
            return true;
        }catch(\Exception $e){
            $this->rollback();
            return false;
        }
    }
}