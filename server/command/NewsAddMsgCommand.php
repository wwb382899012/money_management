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
use money\logic\FinancialConsumer;
use money\logic\LoanConsumer;
use money\logic\OrderConsumer;
use money\logic\InnerConsumer;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class NewsAddMsgCommand extends Command
{
    protected $name = 'news:add-msg';
    protected $description = '批量添加消息';

    protected function configure()
    {
        $this->help = <<<EOF
批量添加消息:

  <info>php %command.full_name% <file></info>

EOF;
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $queue = $this->bindQueue();

        $startTime = time();
        $maxTime = 600; //最多执行十分钟重启
        $financial = new FinancialConsumer();
        $loan = new LoanConsumer();
        $order = new OrderConsumer();
        $inner = new InnerConsumer();

        while(time() - $startTime < $maxTime){
            $dataObj = $queue->get();
            if(!$dataObj){
                sleep(1);
                continue;
            }
            try{
                if($dataObj->getExchangeName() == FINANCIAL_EXCHANGE_NAME){
                    $financial->start($dataObj->getRoutingKey(), json_decode($dataObj->getBody(), true));
                }else if($dataObj->getExchangeName() == LOAN_EXCHANGE_NAME){
                    $loan->start($dataObj->getRoutingKey(), json_decode($dataObj->getBody(), true));
                }else if($dataObj->getExchangeName() == ORDER_EXCHANGE_NAME){
                    $order->start($dataObj->getRoutingKey(), json_decode($dataObj->getBody(), true));
                }else if($dataObj->getExchangeName() == INNER_EXCHANGE_NAME){
                    $inner->start($dataObj->getRoutingKey(), json_decode($dataObj->getBody(), true));
                }
            }catch(\Exception $e){
                \CommonLog::instance()->getDefaultLogger()->error('系统消息队列消费发生错误，入参：'.$dataObj->getBody(), $e);
            }
            // 消息应答
            $queue->ack($dataObj->getDeliveryTag());
        }
        $this->io->success('批量添加消息完毕');
    }

    /**
     * 绑定队列
     */
    protected function bindQueue(){
        $amqp = new AmqpUtil();
        $amqp->declareExchange(FINANCIAL_EXCHANGE_NAME);
        $queue = $amqp->declareQueue(NEWS_QUEUE);
        $amqp->bind(NEWS_QUEUE, FINANCIAL_EXCHANGE_NAME, FINANCIAL_ROUT_CREATE);
        $amqp->bind(NEWS_QUEUE, FINANCIAL_EXCHANGE_NAME, FINANCIAL_ROUT_AUDIT);
        $amqp->bind(NEWS_QUEUE, FINANCIAL_EXCHANGE_NAME, FINANCIAL_ROUT_REDEMPTION_AUDIT);

        $amqp->declareExchange(LOAN_EXCHANGE_NAME);
        $amqp->bind(NEWS_QUEUE, LOAN_EXCHANGE_NAME, LOAN_ROUT_AUDIT);
        $amqp->bind(NEWS_QUEUE, LOAN_EXCHANGE_NAME, LOAN_ROUT_AUDIT_TRANSFER);
        $amqp->bind(NEWS_QUEUE, LOAN_EXCHANGE_NAME, REPAY_ROUT_AUDIT_CASH_FLOW);

        $amqp->declareExchange(ORDER_EXCHANGE_NAME);
        $amqp->bind(NEWS_QUEUE, ORDER_EXCHANGE_NAME, ORDER_ROUT_AUDIT);
        $amqp->bind(NEWS_QUEUE, ORDER_EXCHANGE_NAME, ORDER_ROUT_AUDIT_TRANSFER);
        $amqp->declareExchange(INNER_EXCHANGE_NAME);
        $amqp->bind(NEWS_QUEUE, INNER_EXCHANGE_NAME, INNER_ROUT_AUDIT);

        return $queue;
    }
}
