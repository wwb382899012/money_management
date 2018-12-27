<?php
/**
 * Created by PhpStorm.
 * User: xupengpeng
 * Date: 2018/8/27
 * Time: 11:21
 */

namespace money\base;


class AmqpUtil extends \AmqpUtil
{
    /**
     * @brief Declares a new Queue on the broker
     * @param $name
     * @param $flags
     * @param $arguments
     */
    public function declareQueue($name, $flags = AMQP_DURABLE, $arguments = [])
    {
        $queue = new \CAMQPQueue($this->getChannel());
        $queue->setFlags($flags);
        $queue->setName($name);
        if (!empty($arguments)) {
            $queue->setArguments($arguments);
        }
        $queue->declareQueue();
        return $queue;
    }
}