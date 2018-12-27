<?php

/**
 * Class SysWebNews
 */
namespace money\model;

class SysWebNews extends BaseModel
{
    protected $table = 'm_sys_web_news';
    /**
     * 增加消息
     */
    public function addMsg($data){
        $data['uuid'] = md5(uuid_create());
        !isset($data['create_time']) && $data['create_time'] = date('Y-m-d H:i:s');
        $res = $this->insert($data);
        return $res ? $data['uuid'] : false;
    }

    /**
     * 删除理财到期消息
     */
    public function deleteCashMsg($businessUuid){
        $where = ['business_uuid' => $businessUuid, 'business_type' => 'financial', 'business_son_type' => 'redemption', 'is_delete' => self::DEL_STATUS_NORMAL];
        return $this->where($where)->update(['is_delete' => self::DEL_STATUS_DELED]);
    }

    /**
     * 删除理财到期消息
     */
    public function deleteCashAuditMsg($businessUuid){
        $where = ['business_uuid' => $businessUuid, 'business_type' => 'financial.audit', 'business_son_type' => 'redemption.audit', 'is_delete' => self::DEL_STATUS_NORMAL];
        return $this->where($where)->update(['is_delete' => self::DEL_STATUS_DELED]);
    }

    /**
     * 获取web消息列表数据
     */
    public function listData($page, $limit, $curUserId, $status=null){
        $result = ['page'=>$page, 'limit'=>$limit, 'count'=>0, 'data'=>[]];
        $threeDayAgo = date('Y-m-d H:i:s', time()-3*24*3600);
        $whereSql = "(deal_user_id=:user_id or deal_user_id=0) and is_delete=".self::DEL_STATUS_NORMAL." and (send_datetime<='".date('Y-m-d H:i:s')."' or (business_son_type='redemption' and send_datetime<='".$threeDayAgo."'))";
        $bind = ['user_id' => $curUserId];
        if($status){
            $whereSql .= " and news_status=:status";
            $bind['status'] = $status;
        }
        $col = "*";
        if($limit&&$limit<0) {
            $result['data'] = $this->field($col)->whereRaw($whereSql, $bind)->order(['create_time' => 'desc'])->select()->toArray();
        } else {
            $count = $this->whereRaw($whereSql, $bind)->count();
            if(!empty($count)){
                $result['count'] = $count;
                $result['data'] = $this->field($col)->whereRaw($whereSql, $bind)->order(['create_time' => 'desc'])->page($page, $limit)->select()->toArray();
            }
        }

        return $result;
    }

    /**
     * 更新消息状态
     */
    public function updateStatus(array $uuids, $status){
        $where = [
            ['uuid', 'in', $uuids],
            ['news_status', '<>', $status],
            ['is_delete', '=', self::DEL_STATUS_NORMAL],
        ];
        return $this->where($where)->update(['news_status' => $status]);
    }
}