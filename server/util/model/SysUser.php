<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/5/29
 * Time: 11:17
 */
namespace money\model;

class SysUser extends BaseModel
{
    protected $table = 'm_sys_user';
    const STATUS_APPEND = 1;
    const STATUS_FORBID = 2;

    /**
     * 更新用户信息
     */
    public function updateInfo($userId, $data){
        return $this->where(['user_id' => $userId])->update($data);
    }

    public function getUserInfo($userId, $key='user_id'){
        return $this->getOne([$key => $userId]);
    }

    /**
     * 获取用户分页数据
     * @param int $page 当前页码
     * @param int $limit 每页限制
     * @param int $username 用户名
     * @param int $roleIds 角色ID
     * @param int $bodys 主体ID
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserList($page, $limit, $username='', $roleIds=[], $bodys=[]){
        $where = [];
        if($username){
            $where[] = ['username', 'like', "%$username%"];
        }
        if(!empty($roleIds)){
            $tmp = ['role_uuid' => $roleIds, 'is_delete' => self::DEL_STATUS_NORMAL];
            $userIds = $this->table('m_sys_user_role')->where($tmp)->column('user_id');
            $where[] = ['user_id', 'in', $userIds];
        }
        if(!empty($bodys)){
            $tmp = ['main_body_uuid' => $bodys, 'is_delete' => self::DEL_STATUS_NORMAL];
            $userIds1 = $this->table('m_sys_user_main_body')->where($tmp)->column('user_id');
            !empty($userIds) && $userIds1 = array_intersect($userIds, $userIds1);
            $where[] = ['user_id', 'in', $userIds1];
        }

        $col = "uuid,user_id,username,name,create_time,update_time,last_login_datetime,email,status";
        $result = $this->getDatasByPage($where, $col, $page, $limit);
        $userIds = [];
        foreach($result['data'] as $row){
            $userIds[] = $row['user_id'];
        }
        $roleData = $this->table('m_sys_user_role ur')->leftJoin('m_sys_role r', 'ur.role_uuid=r.uuid')->where(['ur.user_id' => $userIds,'r.is_delete'=> self::DEL_STATUS_NORMAL, 'ur.is_delete' => self::DEL_STATUS_NORMAL])->select()->toArray();
        $roleDataKey = [];
        foreach($roleData as $row){
            $roleDataKey[$row['user_id']]['uuid'][] = $row['role_uuid'];
            $roleDataKey[$row['user_id']]['name'][] = $row['name'];
        }
        foreach($result['data'] as &$row){
            $row['role_uuid'] = '';
            $row['role_name'] = '';
            if(isset($roleDataKey[$row['user_id']])){
                $row['role_uuid'] = implode(',', $roleDataKey[$row['user_id']]['uuid']);
                $row['role_name'] = implode(',', $roleDataKey[$row['user_id']]['name']);
            }
        }
        return $result;
    }

    /**
     * 用户详情数据
     */
    public function userDetail($userid, $userName = null, $auth = false){
        if (!empty($userid)) {
            $where = ['user_id' => $userid];
        } elseif (!empty($userName)) {
            $where = ['username' => $userName];
        } else {
            return null;
        }
        $result = $this->field('user_id, username, name, last_login_datetime,email, status')->where($where)->find();
        if (!empty($result)) {
            $result = $result->toArray();
            $result['role'] = $this->table('m_sys_user_role')->field('role_uuid as uuid')->where(['user_id' => $result['user_id'], 'is_delete' => self::DEL_STATUS_NORMAL])->select()->toArray();
            $result['main_body'] = $this->table('m_sys_user_main_body')->field('main_body_uuid as uuid')->where(['user_id' => $result['user_id'], 'is_delete' => self::DEL_STATUS_NORMAL])->select()->toArray();
            if (!empty($auth)) {
                $col = "rm.module_uuid,rm.module_son_api as son,m.api_url as module_server";
                $result['module_uuids'] = $this->field($col)
                    ->table('m_sys_role_module rm')
                    ->leftJoin('m_sys_module m', 'rm.module_uuid=m.uuid')
                    ->where(['rm.role_uuid' => array_column($result['role'], 'uuid'), 'rm.is_delete' => 1, 'm.status' => 0])
                    ->select()->toArray();
            }
        }

        //管理员
        if (isset($result['username']) && $result['username'] == 'admin') {
            $result['admin'] = 1;
        }
        return $result;
    }

    /**
     * 同步用户中心数据
     */
    public function syncData(){
        $array = [
            'cmd' => 80010005,
            'data' => [
                'client_id'=>USER_CLIENT_ID,
                'secret_key'=>md5(USER_CLIENT_ID.'-'.USER_SECRET),
            ]
        ];
        $array = \JmfUtil::call_Jmf_consumer('com.jyblife.logic.bg.user.UserCenter', $array);
         if($array['code']==0 && isset($array['data'])){
            foreach($array['data'] as $row){
                $result = $this->getOne(['user_id' => $row['user_id']]);
                if(empty($result)){
                    $data = [
                        'uuid' => md5(uuid_create()),
                        'user_id' => $row['user_id'],
                        'identifier' => $row['identifier'],
                        'username' => $row['user_name'],
                        'name' => $row['name'],
                        'create_time' => date('Y-m-d H:i:s'),
                    ];
                    $this->insert($data);
                }else if($result['name'] != $row['name']){
                    $data = [
                        'identifier' => $row['identifier'],
                        'name' => $row['name'],
                    ];
                    $this->where(['user_id' => $row['user_id']])->update($data);
                }
            }
            return true;
        } else {
             \CommonLog::instance()->getDefaultLogger()->warn('同步用户列表失败：'.json_encode($array, JSON_UNESCAPED_UNICODE));
         }
        return false;
    }

    /**
     * 更新角色关联信息
     */
    public function updateRole(array $roleIds, $userId){
        $roleData = $this->table('m_sys_user_role')->where(['user_id' => $userId, 'is_delete' => self::DEL_STATUS_NORMAL])->select()->toArray();
        $oldRoleIds = [];
        foreach($roleData as $row){
            $oldRoleIds[] = $row['role_uuid'];
        }
        $addRoleIds = array_diff($roleIds, $oldRoleIds);
        $deleteRoleIds = array_diff($oldRoleIds, $roleIds);
        if(!empty($addRoleIds)){
            $data = [];
            foreach ($addRoleIds as $uid) {
                $data[] = [
                    'uuid' => md5(uuid_create()),
                    'user_id' => $userId,
                    'role_uuid' => $uid,
                    'create_time' => date('Y-m-d H:i:s'),
                ];
            }
            $this->table('m_sys_user_role')->insertAll($data);
        }
        if(!empty($deleteRoleIds)){
            $this->table('m_sys_user_role')->where(['user_id' => $userId, 'role_uuid' => $deleteRoleIds])->update(['is_delete' => self::DEL_STATUS_DELED]);
        }
        return true;
    }

    /**
     * 更新主体关联信息
     */
    public function updateMainBody(array $main_boyd_uuids, $userId){
        $bodyData = $this->table('m_sys_user_main_body')->where(['user_id' => $userId, 'is_delete' => self::DEL_STATUS_NORMAL])->select()->toArray();
        $oldBodyIds = [];
        foreach($bodyData as $row){
            $oldBodyIds[] = $row['main_body_uuid'];
        }
        $addIds = array_diff($main_boyd_uuids, $oldBodyIds);
        $deleteIds = array_diff($oldBodyIds, $main_boyd_uuids);
        if(!empty($addIds)){
            $data = [];
            foreach ($addIds as $uid) {
                $data[] = [
                    'uuid' => md5(uuid_create()),
                    'user_id' => $userId,
                    'main_body_uuid' => $uid,
                    'create_time' => date('Y-m-d H:i:s'),
                ];
            }
            $this->table('m_sys_user_main_body')->insertAll($data);
        }
        if(!empty($deleteIds)){
            $this->table('m_sys_user_main_body')->where(['user_id' => $userId, 'main_body_uuid' => $deleteIds])->update(['is_delete' => self::DEL_STATUS_DELED]);
        }
        return true;
    }

    /**
     * 插入登陆日志
     */
    public function addLoginLog($userId, $username, $name, $ip=''){
        $uuid = md5(uuid_create());
        $log = [
            'uuid' => $uuid,
            'user_id' => $userId,
            'username' => $username,
            'name' => $name,
            'ip_address' => $ip,
            'create_time' => date('Y-m-d H:i:s'),
        ];
        $this->table('m_sys_login_log')->insert($log);
        $this->where(['username' => $username])->update(['last_login_datetime' => date('Y-m-d H:i:s')]);
        return true;
    }

    /**
     * 获取某个主体所关联的所有用户id
     */
    public function getUserIdForMainUuid($main_uuid){
        return $this->table('m_sys_user_main_body')->where(['main_body_uuid' => $main_uuid, 'is_delete' => self::DEL_STATUS_NORMAL])->select()->toArray();
    }
    
    /**
     * 获取某个主体、角色所关联的所有用户id
     */
    public function getUserIdForMainUuidRoleId($main_uuid,$role_id_array){
    	$role_ids = "'".implode("','",$role_id_array)."'";
    	$sql = "select group_concat(b.user_id)users from m_sys_user_main_body b join m_sys_user_role r on b.user_id = r.user_id "
    			." where  b.main_body_uuid = '$main_uuid' and r.role_uuid in ($role_ids) and r.is_delete=1 and b.is_delete=1";
    	$ret =  $this->query($sql);
    	return $ret[0]['users'];
//     	return $this->table('m_sys_user_main_body')->where(['main_body_uuid' => $main_uuid, 'is_delete' => self::DEL_STATUS_NORMAL])->select()->toArray();
    }
    

    /**
     * 获取某实体数据(这个方法不该出现在这里)
     */
    public function getEntity($table, $uuid){
        $res = $this->table($table)->where(['uuid' => $uuid])->find();
        return !empty($res) ? $res->toArray() : null;
    }

    public function changeUidToName(&$list) {
        $userIds = array_column($list, 'create_user_id');
        $userList = $this->where(['user_id' => $userIds])->column('name', 'user_id');
        foreach ($list as &$row) {
            $row['create_user_name'] = $userList[$row['create_user_id']];
        }
    }
    
    public static function getUserInfoByIds($userIds){
    	$obj = new SysUser();
    	return $obj->table('m_sys_user')->where([['user_id','in' ,$userIds]])->select()->toArray();
    }
}