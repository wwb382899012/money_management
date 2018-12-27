<?php
/**
 * Desc:
 * User: yanjiang.chen
 * Email: yanjiang.chen@yunjiaplus.com
 * Date: 2018/11/30
 * Time: 14:42
 */

namespace money\command;

use money\base\AmqpUtil;
use money\console\Command;
use money\model\MainBody;
use money\model\SysUser;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class OutMainBodyAuthCommand extends Command
{
    protected $name = 'outMainBody:auth';
    protected $description = '新增外部主体授权用户';

    protected function configure()
    {
        $this->help = <<<EOF
新增外部主体授权用户:

  <info>php %command.full_name% <file></info>

EOF;
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $amqp = new AmqpUtil();
        $amqp->declareExchange(MQ_EXCHANGE_MAINBODY);
        //通知队列
        $queue = $amqp->declareQueue(MQ_QUEUE_MAINBODY_ADD_AUTOAUTH);
        $amqp->bind(MQ_QUEUE_MAINBODY_ADD_AUTOAUTH, MQ_EXCHANGE_MAINBODY, MQ_ROUT_MAINBODY_ADD);

        $startTime = time();
        $maxTime = 600; //最多执行十分钟重启

        while(time() - $startTime < $maxTime){
            $dataObj = $queue->get();
            if(!$dataObj){
                sleep(1);
                continue;
            }
            try{
                if($dataObj->getExchangeName() == MQ_EXCHANGE_MAINBODY){
                    $queue->ack($dataObj->getDeliveryTag());
                    $data = json_decode($dataObj->getBody(), true);
                    $mainBodyUuid = (string) ($data['uuid'] ?? "");
                    \CommonLog::instance()->getDefaultLogger()->info(sprintf("新增外部主体[%s]授权", $mainBodyUuid));

                    //获取主体信息
                    $mainBodyModel = new MainBody();
                    $mainBodyData = $mainBodyModel->where([
                        "uuid" => $mainBodyUuid,
                        "is_delete" => 1,
                        "status" => 1,
                    ])->find();

                    if (empty($mainBodyData)) {
                        \CommonLog::instance()->getDefaultLogger()->error(sprintf("外部主体[%s]没有找到", $mainBodyUuid));
                        continue;
                    }

                    //获取所有有效用户
                    $userModel = new SysUser();
                    $users = $userModel->where([
                        "status" => 1,
                    ])->select();
                    $userIds = array_column($users->toArray(), "user_id");

                    $userMainBodyData = $userModel->table("m_sys_user_main_body")
                        ->where(['user_id' => $userIds, 'main_body_uuid' => $mainBodyData["uuid"]])->select();
                    $userMainBodyMap = [];
                    foreach ($userMainBodyData as $k => $v) {
                        $userMainBodyMap[$v["user_id"]] = $v;
                        unset($userMainBodyData[$k]);
                    }

                    $insertData = [];
                    foreach ($users as $user) {
                        if (isset($userMainBodyMap[$user["user_id"]])) {
                            continue;
                        }

                        $insertData[] = [
                            'uuid' => md5(uuid_create()),
                            'user_id' => $user["user_id"],
                            'main_body_uuid' => $mainBodyData["uuid"],
                            'create_time' => date('Y-m-d H:i:s'),
                        ];
                    }

                    if (!empty($insertData)) {
                        $ret = $userModel->table('m_sys_user_main_body')->insertAll($insertData);
                        \CommonLog::instance()->getDefaultLogger()->info(sprintf("新增外部主体自动授权用户权限：%d", $ret));
                    }

                }
            }catch(\Exception $e){
                \CommonLog::instance()->getDefaultLogger()->error('新增外部主体队列消费发生错误，入参：'.$dataObj->getBody(), $e);
            }
        }
        $this->io->success('新增外部主体自动授权用户完毕');



    }
}