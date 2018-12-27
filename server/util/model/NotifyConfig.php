<?php

/*
 * service_type参数
 * 服务类型：jmf；cmd；rest
 *
 * service_name参数
 * 服务名称：微服务名称，cmd接口名称，restful接口
 *
 * service_options参数
 * @param string base_uri   基准URI
 * @param array headers     请求头
 * @param float timeout     请求超时时间
 * @param bool is_data_sign     数据是否签名
 * @param bool is_data_encrypt  数据是否加密
 */

namespace money\model;

class NotifyConfig extends BaseModel
{
	protected $table = 'm_notify_config';
    protected $pk = 'id';
}
