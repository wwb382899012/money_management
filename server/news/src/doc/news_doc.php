<?php
/**
* @api {POST} /com.jyblife.logic.bg.news.ListNews 消息列表
* @apiGroup News
*
* @apiParam {string} sessionToken 会话token前缀
* @apiParam {Number} page 页码
* @apiParam {Number} limit 每页限制
* @apiParam {Number} status 1不限制 2未阅读 3阅读 默认2(非必填)
* @apiParamExample {json} post数据:
* {
*   "sessionToken":"e2fddd7da76988a24fc2a2b1887fdeise4",
*   "page": 1, 
*   "limit": 10 
* }
* @apiSuccessExample {json} 输出示例:
* {"code":0, "msg":"success"
*  "data":{"count":10, "page":1, "limit":10, "data":[
*  {
*  "uuid":"xxxxx",              #消息uuid    
*  "content":"消息内容", 
*  "business_type":"financial", #消息业务类型
*  "business_status":"audit",   #消息业务状态
*  "status":1,                  #消息状态 1:未读 2:已读
*  "business_uuid": "xxx"        #业务主键
*  }
* ]}
* }
*/

/**
* @api {POST} /com.jyblife.logic.bg.news.ReadNews 标记消息已读
* @apiGroup News
*
* @apiParam {string} sessionToken 会话token前缀
* @apiParam {Array} news_uuids 消息uuid
* @apiParamExample {json} post数据:
* {
*   "sessionToken":"e2fddd7da76988a24fc2a2b1887fdeise4",
*   "news_uuids": [xxxx,xxxx] 
* }
* @apiSuccessExample {json} 输出示例:
* {"code":0, "msg":"success"
*  "data":{}
*/