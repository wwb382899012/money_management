<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/6/23
 * Time: 23:26
 */

namespace money\command;

use money\base\AmqpUtil;
use money\console\Command;
use money\logic\AccountNotifyLogic;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class AccountNotifyCommand extends Command
{
    protected $name = 'account:notify';
    protected $description = '账户变更通知业务方';

    protected function configure()
    {
        $this->help = <<<EOF
账户变更通知业务方:

  <info>php %command.full_name% <file></info>

EOF;
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $amqp = new AmqpUtil();
        $exchange = $amqp->declareExchange(MQ_EXCHANGE_ACCOUNT);
        //通知队列
        $queue = $amqp->declareQueue(MQ_QUEUE_ACCOUNT_NOTIFY);
        $queue->bind(MQ_EXCHANGE_ACCOUNT, MQ_ROUT_ACCOUNT_NOTIFY);
        //通知延迟队列
        $args = [
            'x-dead-letter-exchange' => MQ_EXCHANGE_ACCOUNT,
            'x-dead-letter-routing-key' => MQ_ROUT_ACCOUNT_NOTIFY,
        ];
        $delayQueue = $amqp->declareQueue(MQ_DELAY_QUEUE_ACCOUNT_NOTIFY, AMQP_DURABLE, $args);
        $delayQueue->bind(MQ_EXCHANGE_ACCOUNT, MQ_DELAY_ROUT_ACCOUNT_NOTIFY);

        $startTime = time();
        $maxTime = 600; //最多执行十分钟重启
        $accountNotifyLogic = new AccountNotifyLogic();

        while(time() - $startTime < $maxTime){
            $dataObj = $queue->get();
            if(!$dataObj){
                sleep(1);
                continue;
            }
            try{
                if($dataObj->getExchangeName() == MQ_EXCHANGE_ACCOUNT){
                    $queue->ack($dataObj->getDeliveryTag());
                    if (!$accountNotifyLogic->batchPush(json_decode($dataObj->getBody(), true))) {
                        // 通知批量推送失败，放入延迟队列
                        $exchange->publish($dataObj->getBody(), MQ_DELAY_ROUT_ACCOUNT_NOTIFY, AMQP_NOPARAM, ['expiration' => MQ_MESSAGE_TTL_ACCOUNT_NOTIFY]);
                    }
                }
            }catch(\Exception $e){
                \CommonLog::instance()->getDefaultLogger()->error('账户通知消息队列消费发生错误，入参：'.$dataObj->getBody(), $e);
            }
        }
        $this->io->success('账户变更通知业务方完毕');
    }
}
