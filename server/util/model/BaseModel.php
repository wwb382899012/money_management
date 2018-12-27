<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/5/29
 * Time: 11:17
 * @link https://github.com/top-think/think-orm
 */
namespace money\model;

class BaseModel extends Model
{
    public $params = [];

    protected $pk = 'uuid';
    protected $autoWriteTimestamp = 'datetime';

    const DEL_STATUS_NORMAL = 1;
    const DEL_STATUS_DELED = 2;
    const DEL_STATUS_WAIT_DEL_APPROVE = 3;

    public static function getDataById($id, $fields = '*') {
        $model = new static();
        $result = $model->field($fields)->where($model->pk, $id)->find();
        return !empty($result) ? $result->toArray() : null;
    }

    public function saveOrUpdate() {
        if (isset($this->params[$this->pk])) {
            $this->where([$this->pk => $this->params[$this->pk]])->update($this->params);
        } else {
        	!isset($this->params[$this->createTime]) && $this->params[$this->createTime] = date('Y-m-d H:i:s');
        	if($this->pk=='uuid'){
            	$this->params[$this->pk] = md5(uuid_create());
            	$this->insert($this->params);
        	}else{
        		$this->insert($this->params);
        		$this->params[$this->pk] = $this->getLastInsID();
        	}
        }
        return $this->params[$this->pk];
    }

    public function del($uuid)
    {
        return $this->where([$this->pk => $uuid])->update(['is_delete' => self::DEL_STATUS_DELED]);
    }

    /**
     *	根据条件获取
     */
    public function loadDatas($where, $fields = '*')
    {
        return $this->field($fields)->where($where)->select()->toArray();
    }

    /**
     * 根据分页条件获取
     */
    public function getDatasByPage($where = [], $fields = '*', $page = 1, $pageSize = 20, $order = ['create_time' => 'desc'])
    {
        (!isset($page) || $page < 1) && $page = 1;
        !isset($pageSize) && $pageSize = 20;
        $result = ['page'=>$page, 'limit'=>$pageSize, 'count'=>0, 'data'=>[]];
        if ($pageSize&&$pageSize<0) {
            $result['data'] = $this->field($fields)->where($where)->order($order)->select()->toArray();
        } else {
            $count = $this->where($where)->count();
            if(!empty($count)){
                $result['count'] = $count;
                $result['data'] = $this->field($fields)->where($where)->order($order)->page($page, $pageSize)->select()->toArray();
            }
        }

        return $result;
    }


    /**
     *	数据字典字段值转换类
     */
    public static function getMapdArrayByParams($array , $key , $map_name)
    {
        $params = array(
            'dict_type'=>$map_name,
            'sessionToken' => 'test',
        );
        $res = \JmfUtil::call_Jmf_consumer('com.jyblife.logic.bg.base.DictKvList' ,$params);

        if($res['code'] != 0 || empty($res['data']['data'])){
            return $array;
        }
        foreach($res['data']['data'] as $k){
            $map[$k['dict_key']] = $k['dict_value'];
        }

        foreach ($array as &$obj) {
            if (!isset($obj[$key]) || !isset($map[$obj[$key]])) {
                continue;
            }
            $obj[$map_name] = $map[$obj[$key]];
        }
        return $array;
    }

    /**
     * @param array $where
     * @param string $fields
     * @param int $page
     * @param int $pageSize
     * @param mixed $order
     * @param string $table
     * @return array|null
     */
    public function getList($where = [], $fields = '*', $page = 1, $pageSize = 20, $order = ['create_time' => 'desc'], $table = '')
    {
        return parent::getList($where, $fields, $page, $pageSize, $order, $table);
    }
}