<?php
namespace money\model;

class MoneyProduct extends BaseModel {

    protected $table = "m_money_manager_product";

    /**
     * 保存产品
     */
    public function saveProduct($data, $uuid=null){
        if($uuid){
            $res = $this->where(['uuid' => $uuid, 'is_delete' => 1])->count();
            if(empty($res)){
                return null;
            }
            $res = $this->where(['uuid' => $uuid])->update($data);
            return $res ? $uuid : null;
        }else{
            $data['uuid'] = md5(uuid_create());
            $data['create_time'] = date('Y-m-d H:i:s');
            $this->insert($data);
            return $data['uuid'];
        }
    }

    /**
     * 列表数据
     */
    public function listData($page, $pageSize, $where=[]){
        $w = [
            ['is_delete', '=', 1]
        ];
        foreach ($where as $key => $value) {
            if(in_array($key, ['product_name','bank_dict_value'])){
                $w[] = [$key, 'like', "%$value%"];
            }else{
                $w[] = [$key, '=', $value];
            }
        }

        return $this->getDatasByPage($w, '*', $page, $pageSize);
    }

    /**
     * 根据uuid获取批量数据
     */
    public function getForUuid($uuids){
        return $this->where(['uuid' => $uuids, 'is_delete' => 1])->select()->toArray();
    }

    /**
     * 注销/启用产品
     * @param string $uuid 理财产品uuid
     * @param inter $status 1正常 2注销
     */
    public function statusProduct($uuid, $status){
        $data = $this->where(['uuid' => $uuid, 'is_delete' => 1])->count();
        if(empty($data)){
            return null;
        }
        return $this->where(['uuid' => $uuid])->update(['status' => $status]);
    }

    /**
     * 产品详情
     */
    public function detail($uuid){
        return $this->getOne(['uuid' => $uuid, 'is_delete' => 1]);
    }
}