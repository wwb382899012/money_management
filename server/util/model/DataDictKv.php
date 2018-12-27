<?php

/**
 * 数据字典键值
 * @author sun
 * @since 2018-03-28
 */
namespace money\model;

class DataDictKv extends BaseModel
{
	protected $table = 'm_data_dict_kv';

	public function details($queryArray , $cols , $page , $pageSize){
        $cols = $cols?$cols:'*';
        $where = [
            ['v.is_delete', '=', self::DEL_STATUS_NORMAL],
            ['d.is_delete', '=', self::DEL_STATUS_NORMAL],
        ];
        !empty($queryArray['dict_type']) && $where[] = ['dict_type', '=', $queryArray['dict_type']];
        !empty($queryArray['dict_desc']) && $where[] = ['dict_desc', 'like', "%{$queryArray['dict_desc']}%"];

        $result = ['page'=>$page, 'limit'=>$pageSize, 'count'=>0, 'data'=>[]];
        if ($pageSize&&$pageSize<0) {
            $result['data'] = $this->field($cols)->table('m_data_dict d')->join('m_data_dict_kv v', 'd.uuid = v.dict_uuid')->where($where)->order(['d.dict_type', 'v.index'])->select()->toArray();
        } else {
            $count = $this->table('m_data_dict d')->join('m_data_dict_kv v', 'd.uuid = v.dict_uuid')->where($where)->count();
            if(!empty($count)){
                $result['count'] = $count;
                $result['data'] = $this->field($cols)->table('m_data_dict d')->join('m_data_dict_kv v', 'd.uuid = v.dict_uuid')->where($where)->order(['d.dict_type', 'v.index'])->page($page, $pageSize)->select()->toArray();
            }
        }

		return $result;
	}

	public function getListByDictType($type, $cols='*')
    {
        return $this->table('m_data_dict dd')
            ->leftJoin('m_data_dict_kv ddv', 'dd.uuid=ddv.dict_uuid')
            ->where(['dd.dict_type' => $type])
            ->column($cols);
    }
}
