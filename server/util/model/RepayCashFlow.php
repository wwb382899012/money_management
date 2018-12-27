<?php
namespace money\model;
class RepayCashFlow extends BaseModel{
	protected $table = 'm_repay_cash_flow';
	protected $pk = 'id';
	const STATUS_WAITING = 1;
	const STATUS_PAYING = 2;
	const STATUS_SUCC = 3;
	const STATUS_FAIL = 4;
	
}
