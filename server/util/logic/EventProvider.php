<?php
/**
 * 理财事件推送
 */

namespace money\logic;

class EventProvider {
    private $amqp;
    /**
     * 推送理财计划创建消息
     */
    public function createEvent($dbData){
        $amqpUtil = $this->amqp();
        $ex = $amqpUtil->exchange(FINANCIAL_EXCHANGE_NAME);
        $data = $dbData;
        $data['plan_uuid'] = $dbData['uuid'];

        return $ex->publish(json_encode($data), FINANCIAL_ROUT_CREATE);
    }

    /**
     * 理财审核事件
     */
    public function auditEvent($data){
        $amqpUtil = $this->amqp();
        $ex = $amqpUtil->exchange(FINANCIAL_EXCHANGE_NAME);
        return $ex->publish(json_encode($data), FINANCIAL_ROUT_AUDIT);
    }

    /**
     * 赎回审核事件
     */
    public function redemAuditEvent($data){
        $amqpUtil = $this->amqp();
        $ex = $amqpUtil->exchange(FINANCIAL_EXCHANGE_NAME);
        return $ex->publish(json_encode($data), FINANCIAL_ROUT_REDEMPTION_AUDIT);
    }

    protected function amqp(){
        if(!$this->amqp){
            $this->amqp = new \AmqpUtil();
        }
        return $this->amqp;
    }
}