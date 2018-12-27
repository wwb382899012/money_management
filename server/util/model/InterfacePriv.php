<?php

/**
 * 业务系统
 * @author sun
 * @since 2018-03-28
 */
namespace money\model;

class InterfacePriv extends BaseModel
{
	protected $table = 'm_interface_priv';
	
	public function details($queryArray , $cols , $page , $pageSize){
		$cols = $cols?$cols:'*';
        $where[] = ['is_delete', '=', self::DEL_STATUS_NORMAL];
        isset($queryArray['uuid']) && $where[] = ['uuid', '=', $queryArray['uuid']];
        isset($queryArray['status']) && $where[] = ['status', '=', $queryArray['status']];
        isset($queryArray['sys_name']) && $where[] = ['sys_name', 'like', "%{$queryArray['sys_name']}%"];

        return $this->getDatasByPage($where, $cols, $page, $pageSize);
	}
	
}
