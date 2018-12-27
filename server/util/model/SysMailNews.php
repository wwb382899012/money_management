<?php

/**
 * Class SysMailNews
 */
namespace money\model;

class SysMailNews extends BaseModel
{
    protected $table = 'm_sys_email_news';
    /**
     * 添加邮件消息
     */
    public function addMsg($data){
    	if(empty($data['email_address'])){
    		return false;
    	}
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
     * 删除理财审核消息
     */
    public function deleteCashAuditMsg($businessUuid){
        $where = ['business_uuid' => $businessUuid, 'business_type' => 'financial.audit', 'business_son_type' => 'redemption.audit', 'is_delete' => self::DEL_STATUS_NORMAL];
        return $this->where($where)->update(['is_delete' => self::DEL_STATUS_DELED]);
    }

    /**
     * 获取邮件消息列表
     */
    public function mailList($page, $limit, $where=[]){
        $where['is_delete'] = self::DEL_STATUS_NORMAL;
        $col = "*";
        return $this->getDatasByPage($where, $col, $page, $limit);
    }

    /**
     * 更新邮件发送状态
     */
    public function updateStatus($uuid, $status){
        $where = [
            ['is_delete', '=', self::DEL_STATUS_NORMAL],
            ['news_status', '<>', $status],
            ['uuid', '=', $uuid],
        ];
        return $this->where($where)->update(['news_status' => $status]);
    }
}