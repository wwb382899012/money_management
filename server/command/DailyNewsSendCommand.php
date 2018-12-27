<?php

namespace money\command;

use money\console\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use money\model\Repay;
use money\model\MainBody;
use money\model\SysUser;
use money\model\SysWebNews;
use money\model\SysMailNews;

class DailyNewsSendCommand extends Command{
	protected $name = 'news:daily-msg';
	protected $description = '定时消息生成';
	
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		\CommonLog::instance()->getDefaultLogger()->info('repay daily news opt begin');
		$repay = new Repay();
		$sql = "select * from m_repay where forecast_date<='"
				.date("Y-m-d",strtotime('+3 day'))
				."' and repay_transfer_status="
						.Repay::CODE_REPAY_TRANSFER_STATUS_WAITING;
		$ret = $repay->query($sql);
		if(!is_array($ret)||count($ret)==0)
			return;	
		
		foreach($ret as $row){
			$businessSonType = 'repay.change';
			$title = '借款还款还有三日到期';
			$content = 'deal_user_name您好，借款还款调拨交易（order_num）即将到期，还款方为collect_main_body，还款金额为amount元，请登录系统进行处理';
		
			$array['business_type'] = 'loan';
			$array['business_son_type'] = 'repay.transfer.audit';
			$array['business_uuid'] = $row['id'];
			$array['send_datetime'] = date('Y-m-d H:i:s');
			$array['create_time'] = date('Y-m-d H:i:s');
		
			$m = MainBody::getDataById($row['repay_main_body_uuid']);
			
			$u = new SysUser();
			$users = explode(',',$u->getUserIdForMainUuidRoleId($row['repay_main_body_uuid'], ['97ca0cb2db78667e332584cb6018fd54']));
            $users = SysUser::getUserInfoByIds($users);
			if(!is_array($users)||count($users)==0)
				continue;
			foreach($users as $su){
				$replace = [
					'deal_user_name' => $su['name'],
					'order_num' => $row['repay_transfer_num'],
					'collect_main_body' => $m['full_name'],
					'amount' => round($row['amount']/100, 2)
				];
				$array['content'] = str_replace(array_keys($replace), array_values($replace), $content);
				$array['deal_user_id'] = $su['user_id'];
				
				$mail = $array;
				$mail['title'] = $title;
				$mail['deal_user_name'] = $su['name'];
				$mail['email_address'] = $su['email'];
				
				$webDb =  new SysWebNews();
				$mailDb = new SysMailNews();
				\CommonLog::instance()->getDefaultLogger()->info('repay daily news opt|'.json_encode($array));
				$webDb->addMsg($array);
				$mailDb->addMsg($mail);
			}
		}
	}
}