<?php

/**
 * Class Module
 */
namespace money\model;

class Module extends BaseModel
{
    protected $table = 'm_sys_module';
    /**
     * 新增编辑模块
     */
    public function saveModule(array $data, $uuid=''){
        $data['son_api'] = str_replace(['，', ' '], [',', ''], trim($data['son_api']));
        if($uuid){
            $ret = $this->where(['uuid' => $uuid, 'is_delete' => self::DEL_STATUS_NORMAL])->update($data);
        }else{
            $uuid = md5(uuid_create());
            $data['uuid'] = $uuid;
            $data['create_time'] = date('Y-m-d H:i:s');
            $ret = $this->insert($data);
        }
        return $ret ? $uuid :false;
    }

    /**
     * 删除模块
     */
    public function delModule($uuid){
        return $this->where(['uuid|pid_uuid' => $uuid, 'is_delete' => self::DEL_STATUS_NORMAL])->update(['is_delete' => self::DEL_STATUS_DELED]);
    }

    /**
     * 模块数据获取
     */
    public function getModule(array $params){
        $where = [];
        foreach($params as $key=>$value){
            if($key == 'name'){
                $where[] = [$key, 'like', "%$value%"];
            }else{
                $where[] = [$key, '=', $value];
            }
        }

        $col = "uuid,pid_uuid";
        $list = $this->field($col)->where($where)->select()->toArray();
        return $this->generate(null, $list);
    }

    /**
     * 模块详情
     */
    public function moduleDetail($uuid){
        $col = "uuid,pid_uuid,name,sort,status,api_url,son_api,is_menu";
        return $this->getOne(['uuid' => $uuid], $col);
    }

    /**
     * 生成模块节点树
     * @param array $list
     * @param array $filter
     * @return array
     */
    private function generate($list = [], $filter = []) {
        $tree = [];
        empty($list) && $list = $this->getAllNode();
        foreach ($list as $val) {
            $pUuid = $val['pid_uuid'];
            $uuid = $val['uuid'];
            //判断是否为父节点
            if (empty($pUuid)) {
                //父节点不存在，则将父节点添加到节点树
                !isset($tree[$uuid]) && $tree[$uuid] = $val;
            } else {
                //父节点不存在，则将父节点添加到节点树
                if (!isset($tree[$pUuid])) {
                    //没有父节点，则丢弃子节点
                    if (!isset($list[$pUuid])) {
                        continue;
                    }
                    $tree[$pUuid] = $list[$pUuid];
                }
                //子节点不存在，则将子节点添加到节点树
                if (!isset($tree[$pUuid]['children'][$uuid])) {
                    $val['children'] = $this->getFunNodeList($uuid, $val['son_api']);
                    $tree[$pUuid]['children'][$uuid] = $val;
                }
            }
        }
        $this->mySort($tree);
        $this->filter($tree, $filter);
        return $tree;
    }

    /**
     * 获取所有节点
     * @param array $uuids
     * @return array
     */
    private function getAllNode() {
        return $this->where(['is_delete' => self::DEL_STATUS_NORMAL])->order('sort')->column('*', 'uuid');
    }

    private function getFunNodeList($uuid = '', $str = '') {
        if (empty($uuid) || empty($str)) {
            return [];
        } else {
            $fNodeList = array_map(function ($v) use ($uuid) {
                $tmp = explode('|', $v);
                $name = trim($tmp[0]);
                $apiUrl = trim($tmp[1]);
                $v = [
                    'uuid' => $uuid . '_' . $apiUrl,
                    'name' => $name,
                    'api_url' => $apiUrl,
                ];
                return $v;
            }, explode(',', $str));
            return $fNodeList;
        }
    }

    /**
     * 过滤节点树
     * @param array $tree
     * @param array $filter
     */
    private function filter(&$tree = [], $filter = []) {
        $tmp = [];
        foreach ($filter as $val) {
            $pUuid = $val['pid_uuid'];
            $uuid = $val['uuid'];
            if (empty($pUuid)) {
                $tmp[] = $uuid;
            } else {
                $tmp[] = $pUuid;
            }
        }
        $tree = array_filter($tree, function ($v) use ($tmp) {
            return in_array($v['uuid'], $tmp);
        });
        //移除数组key
        $tree = array_map(function ($v) {
            isset($v['children']) && $v['children'] = array_values($v['children']);
            return $v;
        }, array_values($tree));
    }

    /**
     * 按照sort排序，默认升序
     * @param array $tree
     * @param bool $asc
     */
    private function mySort(&$tree = [], $asc = true) {
        uasort($tree, function ($a, $b) use ($asc) {
            if (!isset($a['sort']) || $a['sort'] == $b['sort']) {
                return 0;
            }
            $flag = $a['sort'] < $b['sort'] ? -1 : 1;
            return $asc ? $flag : -$flag;
        });
    }
}