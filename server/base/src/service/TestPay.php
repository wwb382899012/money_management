<?php
use money\service\BaseService;

class TestPay extends BaseService{

	/* (non-PHPdoc)
	 * @see BaseService::exec()
	 */
	protected function exec() {

		$u = new YqzlUtil();
		['bank','noticeUrl','trnuId','fromAcctId','toAcctId','toName',
		'toBankDesc','toInterBank','toLocal','cursym',
		'trnAmt','purPose','ctycod','ctyName','jmgUserName',
		'jmgPassWord'];
		$payInfo = array(
				'bank'=>'1',
				'noticeUrl'=>'1',
				'trnuId'=>'1',
				'fromAcctId'=>'1',
				'toAcctId'=>'1',
				'toName'=>'1',
				'toBankDesc'=>'1',
				'toInterBank'=>'1',
				'toLocal'=>'1',
				'cursym'=>'1',
				'trnAmt'=>'1',
				'purPose'=>'1',
				'ctycod'=>'1',
				'ctyName'=>'1',
				'jmgUserName'=>'1',
				'jmgPassWord'=>'aabb'
			
		);
		$u->pay($payInfo);
	}
}