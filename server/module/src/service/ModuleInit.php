<?php
/**
 * 初始化固定模块(临时类)
 */
use money\service\BaseService;
use money\model\Module;

class ModuleInit extends BaseService{

    public function exec(){
        $config = [
            ['name'=>'理财产品', 'sort'=>'10', 'api_url'=>'', 'is_menu'=>1, 'child'=>[
                ['name'=>'理财列表', 'sort'=>5, 'api_url'=>'com.jyblife.logic.bg.financial', 'son_api'=>'列表|ListProduct,注销|StatusProduct,编辑|UpdateProduct,赎回|RedemptionPlan,回填|PlanBankWaterBack', 'is_menu'=>1],
                ['name'=>'购买理财', 'sort'=>6, 'api_url'=>'com.jyblife.logic.bg.financial', 'son_api'=>'购买|AddPlan']
            ]],
            [
                'name' => '消息管理', 'sort'=>0, 'api_url'=>'com.jyblife.logic.bg.news', 'son_api'=>'
                已读|ReadNews,列表|ListNews'
            ],
            [
                'name' => '用户管理', 'sort'=>7, 'api_url'=>'', 'is_menu'=>1, 'child'=>[
                    ['name'=>'角色列表', 'sort'=>7, 'api_url'=>'com.jyblife.logic.bg.role', 'is_menu'=>1,'son_api'=>'
                    列表|RoleList,删除|RoleDel,新增|RoleAdd,更新|RoleUpdate,详情|RoleDetail'],
                    ['name'=>'用户列表', 'sort'=>5, 'api_url'=>'com.jyblife.logic.bg.user', 'is_menu'=>1, 'son_api'=>'
                    列表|UserList,更新|UserUpdate,详情|UserDetail'],
                    ['name'=>'模块列表', 'sort'=>5, 'api_url'=>'com.jyblife.logic.bg.module','son_api'=>'
                    列表|ModuleList,删除|ModuleDel,新增|ModuleAdd,更新|ModuleUpdate,详情|ModuleDetail']
                ]
            ]
        ];
        $db = new Module();
        $roleModuleData = [];
        foreach($config as $row){
            $ret = $db->getModule(['name'=>$row['name'],'api_url'=>$row['api_url']]);
            $array = $row;
            if(empty($ret)){
                $uuid = '';
            }else{
                $uuid = $ret[0]['uuid'];
            }
            if(isset($row['son_api'])){
                $tmpArray = explode(',', $row['son_api']);
                $sonStr = [];
                foreach ($tmpArray as $k => $v) {
                    $sonStr[] = substr($v, strpos($v, '|')+1);
                }
                $roleModuleData[] = [$uuid=>implode(',', $sonStr)];
            }
            if(isset($row['child'])){
                unset($array['child']);
            }
            $uuid = $db->saveModule($array, $uuid);
            if(isset($row['child'])){
                foreach($row['child'] as $srow){
                    $ret = $db->getModule(['name'=>$srow['name'],'api_url'=>$srow['api_url']]);
                    $srow['pid_uuid'] = $uuid;
                    $suuid = empty($ret) ? '':$ret[0]['uuid'];
                    $suuid = $db->saveModule($srow, $suuid);
                    if(isset($srow['son_api'])){
                        $tmpArray = explode(',', $srow['son_api']);
                        $sonStr = [];
                        foreach ($tmpArray as $k => $v) {
                            $sonStr[] = substr($v, strpos($v, '|')+1);
                        }
                        $roleModuleData[] = [$suuid=>implode(',', $sonStr)];
                    }
                }
            }
        }

        $this->initRole($roleModuleData);

        $this->packRet(ErrMsg::RET_CODE_SUCCESS, []);
    }

    /**
     * 初始化管理员角色
     */
    protected function initRole($roleModuleData){
        $listRole = JmfUtil::call_Jmf_consumer('com.jyblife.logic.bg.role.RoleList', ['sessionToken'=>'1','limit'=>10,'page'=>1,'name'=>'_管理员_']);
        if($listRole['data']['count'] == 0){
            $roleData = [
                'name' => '_管理员_',
                'info' => '系统初始化的角色',
                'status' => 1,
                'module_uuids' => $roleModuleData,
                'sessionToken' => 1
            ];
            $this->m_logger->info(var_export($roleData, true));
            JmfUtil::call_Jmf_consumer('com.jyblife.logic.bg.role.RoleAdd', $roleData);            
        }else if($listRole['data']['count']>0){
            $roleUuid = $listRole['data']['data'][0]['uuid'];
            $roleData['role_uuid'] = $roleUuid;
            $roleData['name'] = '_管理员_';
            $roleData['status'] = 1;
            $roleData['module_uuids'] = $roleModuleData;
            $roleData['sessionToken'] = 1;
            JmfUtil::call_Jmf_consumer('com.jyblife.logic.bg.role.RoleUpdate', $roleData);            
        }
    }
}