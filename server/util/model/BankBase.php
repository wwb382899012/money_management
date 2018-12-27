<?php

namespace money\model;

class BankBase extends BaseModel
{
	protected $table = 'm_bank_base';
	protected $pk = 'id';

    /**
     * @param $subBranchName
     * @param $bankName
     * @return array|null
     */
	public function getBankBySubBranchName($subBranchName, $bankName)
    {
        if(mb_strpos($subBranchName, "有限公司")) {
            return $this->getOne(['sub_branch_name' => $subBranchName]);
        } else {
            $subBranchName = mb_substr($subBranchName, mb_strpos($subBranchName, "银行") + 2);

            if (!empty($bankName)) {
                return $this->getOne([
                    ['sub_branch_name', "like", "%" . $subBranchName],
                    ['bank_name',"like", "%" . $bankName],
                ]);
            } else {
                return [];
            }
        }
    }
}