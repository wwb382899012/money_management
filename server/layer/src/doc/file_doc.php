<?php
/**
* @api {POST} /com.jyblife.logic.bg.layer.FileUpload 文件上传接口
* @apiGroup layer
*
* @apiHeader {string="multipart/form-data"} Content-type
* @apiParamExample {json} post数据:
* {
* }
* @apiDescription 文件上传接口是通过请求头识别的
* @apiSuccessExample {json} 输出示例:
* {"code":0, "data":{"uuid":"xxxx"}, "msg":"success"}
*/

/**
* @api {POST} /com.jyblife.logic.bg.layer.FileDown 图片显示/文件下载
* @apiGroup layer
*
* @apiHeader {string} Cookie session-token登陆会话的cookie
* @apiParam {string="com.jyblife.logic.bg.layer.FileDown"} service 服务名称
* @apiParam {string="dev"} env 环境参数
* @apiParam {string=""} set 可为空
* @apiParam {string="*"} group
* @apiParam {string="1.0.0"} version
* @apiParam {string="access"} method
* @apiParam {string="{}"} params 输入的参数{"uuid":"xxx"}
* @apiParamExample  {curl} get数据:
*    http://localhost:8080/api?service=com.jyblife.logic.bg.layer.FileDown&env=dev&set=&group=%2A&version=1.0.0&method=access&params=%7B%22uuid%22%3A%226ac8a58affc443b15818941441fc181b%22%7D
*/
