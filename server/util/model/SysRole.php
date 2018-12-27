<?php

/**
 * Class Role
 * @package money\model
 */
namespace money\model;

class SysRole extends BaseModel
{
    protected $table = 'm_sys_role';

    /**
     * 列表数据
     */
    public function listData($page, $limit, $name=''){
        $where[] = ['is_delete', '=', self::DEL_STATUS_NORMAL];
        if($name){
            $where[] = ['name', 'like', "%$name%"];
        }
        $col = "uuid,name,info,status,update_time";
        return $this->getDatasByPage($where, $col, $page, $limit);
    }

    public function validateDulicate($name , $uuid){
    	$sql = "select * from m_sys_role where name='$name'  and is_delete=1 ";
    	if($uuid){
    		$sql = $sql."and uuid!='$uuid'";
    	}
    	$ret = $this->query($sql);
    	 
    	if(is_array($ret)&&count($ret)>0){
    		return true;
    	}
    	return false;
    }
    /**
     * 角色详情
     */
    public function detail($uuid){
        $col = "uuid,name,info,status";
        $result = $this->getOne(['uuid' => $uuid, 'is_delete' => self::DEL_STATUS_NORMAL], $col);
        if(!empty($result)){
            $col = "rm.module_uuid,rm.module_son_api as son,m.api_url as module_server";
            $result['module_uuids'] = $this->field($col)->table('m_sys_role_module rm')->leftJoin('m_sys_module m', 'rm.module_uuid=m.uuid')->where(['rm.role_uuid' => $uuid, 'rm.is_delete' => 1])->select()->toArray();
            return $result;
        }
        return null;
    }

    /**
     * 角色新增或编辑
     */
    public function saveRole($data, $uuid=''){
        if($uuid){
            $res = $this->where(['uuid' => $uuid, 'is_delete' => self::DEL_STATUS_NORMAL])->update($data);
        }else{
            $uuid = md5(uuid_create());
            $data['uuid'] = $uuid;
            $data['create_time'] = date('Y-m-d H:i:s');
            $res = $this->insert($data);
        }
        return $res ? $uuid : null;
    }

    /**
     * 保存角色权限
     * @param $data
     * @param $uuid
     * @return string|null
     */
    public function saveRoleAuth($data, $uuid='') {
        if (empty($data) || empty($uuid)) {
            return null;
        }
        //校验角色
        $res = $this->where(['uuid' => $uuid, 'is_delete' => self::DEL_STATUS_NORMAL])->count();
        if(empty($res)){
            return null;
        }
        $moduleValues = [];
        foreach($data as $row){
            $mUuid = array_keys($row)[0];
            $value = array_values($row)[0];
            $value = str_replace(['，', ' '], [',', ''], trim($value));
            $moduleValues[] = $mUuid;
            //校验模块
            $msData = $this->table('m_sys_module')->field('son_api')->where(['uuid' => $mUuid, 'is_delete' => self::DEL_STATUS_NORMAL])->find();
            if (empty($msData) || $this->isSonApiInvalid($msData['son_api'], $value)) {
                continue;
            }
            $seData = $this->table('m_sys_role_module')->field('uuid')->where(['role_uuid' => $uuid, 'module_uuid' => $mUuid])->find();
            if(!empty($seData)){
                $this->table('m_sys_role_module')->where(['uuid' => $seData['uuid']])->update(['module_son_api' => $value, 'is_delete' => self::DEL_STATUS_NORMAL]);
            }else{
                $data = [
                    'uuid' => md5(uuid_create()),
                    'role_uuid' => $uuid,
                    'module_uuid' => $mUuid,
                    'module_son_api' => $value,
                    'create_time' => date('Y-m-d H:i:s'),
                ];
                $this->table('m_sys_role_module')->insert($data);
            }
        }
        $where = [
            ['role_uuid', '=', $uuid],
            ['module_uuid', 'not in', $moduleValues],
            ['is_delete', '=', self::DEL_STATUS_NORMAL],
        ];
        $this->table('m_sys_role_module')->where($where)->update(['is_delete' => self::DEL_STATUS_DELED]);
        return $uuid;
    }

    /**
     * 角色删除
     */
    public function delRole($uuid){
        $this->where(['uuid' => $uuid])->update(['is_delete' => self::DEL_STATUS_DELED]);
        $this->table('m_sys_role_module')->where(['role_uuid' => $uuid])->update(['is_delete' => self::DEL_STATUS_DELED]);
        return true;
    }

    /**
     * 校验子节点是否无效
     * @param string $filter
     * @param string $input
     * @return bool
     */
    private function isSonApiInvalid($filter = '', $input = ''){
        $filterArr = explode(',', $filter);
        $inputArr = explode(',', $input);
        $allow = [];
        foreach ($filterArr as $v) {
            $tmp = explode('|', $v);
            $allow[] = trim($tmp[1]);
        }
        $diff = array_diff($inputArr, $allow);
        return count($diff) ? true : false;
    }
}