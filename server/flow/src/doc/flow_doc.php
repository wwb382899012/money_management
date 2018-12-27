<?php
/**
* @api {POST} /com.jyblife.logic.bg.flow.Start 发起流程
* @apiGroup flow
* @apiParam {string} flow_code 业务流程编号
* @apiParam {string} instance_id 业务数据唯一标示
* @apiParam {string} main_body_uuid 主体唯一标示
* @apiParam {string} info 审批建议 （非必填）
* @apiParam {string} sessionToken 会话token （非必填）
* @apiParam {string} params 其他参数。json格式传入。如{'a':'1','b':2}可以自定义，会透传到配置好的流程节点处理接口里
* @apiParamExample {json} post数据:
*	{
*		"flow_code":"test",
*		"instance_id":"1111",
*		"params":{"test":1}
*	}
* @apiParam (输出字段){string} uuid 工作流数据唯一标示
* @apiSuccessExample {json} 输出示例:
* {
*   "code": 0,
*   "msg": "success",
*   "data":{
*    "uuid":"11111"
*   }
* }
*/

/**
* @api {POST} /com.jyblife.logic.bg.flow.Approve 流程审批
* @apiGroup flow
* @apiParam {string} flow_code 业务流程编号
* @apiParam {string} instance_id 业务数据唯一标示
* @apiParam {string} info 审批建议 
* @apiParam {string} sessionToken 会话token
* @apiParam {string} params 其他参数。json格式传入。如{'a':'1','b':2}可以自定义，会透传到配置好的流程节点处理接口里
* @apiParam {int} approve_type 1 审批通过  2 审批拒绝
* @apiParamExample {json} post数据:
*	{
*		"flow_code":"test",
*		"instance_id":"1111",
*		"params":{"test":1},
*		"approve_type":1
*	}
* @apiParam (输出字段){string} uuid 工作流数据唯一标示
* @apiParam (输出字段){string} redirect_rule 跳转规则
* @apiSuccessExample {json} 输出示例:
* {
*   "code": 0,
*   "msg": "success",
*   "data":{
*    "uuid":"11111",
*	 "redirect_rule":"aaabbb"
*   }
* }
*/

/**
* @api {POST} /com.jyblife.logic.bg.flow.DetailList 流程列表
* @apiGroup flow
* @apiParam {string} flow_code 业务流程编号 (非必填)
* @apiParam {string} instance_id 业务数据唯一标示，可以传入多个，用逗号分隔(非必填)
* @apiParam {string} node_code 业务流程节点编号 (非必填)
* @apiParam {string} sessionToken 会话token
* @apiParam {string} status  流程状态查询类型。 1 待审批 2 审批通过 3 审批拒绝

* @apiParam {int} page 分页字段，默认为1 （非必填）
* @apiParam {int} pageSize 每页大小，默认为50，如果传入值小于0，则不分页。（非必填）
* @apiParamExample {json} post数据:
*	{
*		"flow_code":"test",
*		"instance_id":"1111,222"
*	}
* @apiParam (输出字段){string} flow_code 流程编码
* @apiParam (输出字段){string} instance_id 业务数据唯一标示
* @apiParam (输出字段){string} status  流程状态类型。 1 待审批 2 审批通过 3 审批拒绝
* @apiParam (输出字段){string} cur_node_auth 当前节点审批权限 1 有 2 无
* @apiSuccessExample {json} 输出示例:
* {
*   "code": 0,
*   "msg": "success",
*   "data":[
*    {"flow_code":"test",
*	 "instance_id":"1111",
*	 "status":"1", 
* 	 "main_body_uuid":"123123",
*	 "create_time":"2018-03-14 10:11:11",
*	 "update_time":"2018-03-14 10:11:11",
*	 "cur_node_auth":1,
*	 "node_list":[{
*		"node_id":1，
*		"node_desc":"节点1",
*	  	"node_status":"1", # 1 待审批 2 审批通过 3 驳回 4 流程结束
*		"node_code":"ccdd",
*		"creator":"张三", 
*		"optor":"李四", 
*		"is_current_node": 1 ,  #1 当前节点 2 历史节点
*		"create_time":"2018-03-14 10:11:11" ,
*		"update_time":"2018-03-14 10:11:11" 
*     }]
*
*	 }]
* }
*/

/**
* @api {POST} callback 回调接口格式
* @apiGroup flow
* @apiDescription 回调接口格式
* @apiParam {string} flow_code 业务流程编号
* @apiParam {string} node_code 当前业务流程节点编号
* @apiParam {string} instance_id 业务数据唯一标示
* @apiParam {string} uuid 工作流数据唯一标示
* @apiParam {string} optor 审批人名字
* @apiParam {string} optor_id	审批人id
* @apiParam {string} status  流程状态。 1 待审批 2 审批通过 3 审批拒绝
* @apiParam {string} node_status  流程节点状态。1 待审批 2 审批通过 3 驳回 4 流程结束
* @apiParam {string} sessionToken 会话token
* @apiParam {string} next_node_role_uuids 下一节点审批角色，可为多个，逗号分隔。如果当前流程已结束则没有下一节点
* @apiParam {string} params 其他参数。json格式传入。如{'a':'1','b':2}可以自定义，会透传到配置好的流程节点处理接口里
* @apiParamExample {json} post数据:
*	{
*		"flow_code":"test",
*		"node_code":"aaa",
*		"instance_id":"bbb",
*		"uuid":"ccc",
*		"optor":"张三",
*		"optor_id":"111",
*		"status":1,
*		"node_status":1,
*		"sessionToken":"asfasdf",
*		"next_node_role_uuids":"111,222",
*		"params":{
*			"test":111
*		}
*	}
* @apiParam (输出字段){int} code 调用成功标识，如果为0，则调用成功。
*/


?>