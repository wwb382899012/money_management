<?php

/**
 * 数据字典
 * @author sun
 * @since 2018-03-28
 */
namespace money\model;

class DataDict extends BaseModel
{
	protected $table = 'm_data_dict';
	
	public function getDataByType($dict_type){
		return $this->getOne(['dict_type' => $dict_type]);
	}
	
	public function details($queryArray , $cols , $page , $pageSize){
		$cols = $cols?$cols:'*';
        $where[] = ['is_delete', '=', self::DEL_STATUS_NORMAL];
        isset($queryArray['dict_type']) && $where[] = ['dict_type', '=', $queryArray['dict_type']];
        isset($queryArray['dict_desc']) && $where[] = ['dict_desc', 'like', "%{$queryArray['dict_desc']}%"];

        return $this->getDatasByPage($where, $cols, $page, $pageSize);
	}
}
