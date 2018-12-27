<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/5/29
 * Time: 11:17
 * @link https://github.com/top-think/think-orm
 */
namespace money\model;

class OaModel extends Model
{
    protected $connection = 'OA';

    // 付款业务映射表，workflow_requestbase表的字段workflowid，currentnodeid与formtable_maing表的关系
    private $oaCallConfig = [];
    private $oaNotifyConfig = [];

    /**
     * 设置OA请求指令的配置
     * @param array $config
     */
    public function setOaCallConfig(array $config)
    {
        $this->oaCallConfig = $config;
    }

    /**
     * 获取OA付款指令的配置
     * @param int $workflowId
     * @param int $currentNodeId
     * @return mixed
     */
    public function getOaCallConfig($workflowId, $currentNodeId)
    {
        foreach ($this->oaCallConfig as $config) {
            if ($workflowId == $config['workflowid'] && $currentNodeId == $config['currentnodeid']) {
                return $config;
            }
        }
        return [];
    }

    /**
     * 设置指令通知OA的配置
     * @param array $config
     */
    public function setOaNotifyConfig(array $config)
    {
        $this->oaNotifyConfig = $config;
    }

    /**
     * 获取指令通知OA的配置
     * @param string $type
     * @param array $fields
     * @return mixed
     */
    public function getOaNotifyConfig($type = null, array $fields = [])
    {
        foreach ($this->oaNotifyConfig as $config) {
            if (!empty($type) && $type != $config['type']) {
                continue;
            }
            if (!empty($fields)) {
                $flag = true;
                foreach ($config['fields'] as $k => $v) {
                    if (!isset($fields[$k]) || $fields[$k] != $v) {
                        $flag = false;
                        break;
                    }
                }
                if ($flag) {
                    return $config;
                }
            } else {
                return $config;
            }
        }
        return [];
    }

    /**
     * 获取workflow_requestbase表数据条数
     * @param array $where
     * @param string $whereRaw
     * @return int|string
     */
    public function getWorkflowRequestBaseCount($where = [], $whereRaw = '')
    {
        return $this->table('workflow_requestbase')->where($where)->whereRaw($whereRaw)->count();
    }

    /**
     * 获取workflow_requestbase表数据
     * @param array $where
     * @param string $whereRaw
     * @param int $page
     * @param int $pageSize
     * @return array
     */
    public function getWorkflowRequestBaseList($where = [], $whereRaw = '', $page = 1, $pageSize = 100)
    {
        $fields = 'requestid, workflowid, currentnodeid, lastoperatedate, lastoperatetime';
        return $this->table('workflow_requestbase')->field($fields)->where($where)->whereRaw($whereRaw)->page($page, $pageSize)->order('requestid')->select()->toArray();
    }

    /**
     * 获取节点操作人
     * @param $requestId
     * @param $workflowId
     * @param $nodeId
     * @param string $fields
     * @return array|mixed
     */
    public function getWorkflowNodeOperator($requestId, $workflowId, $nodeId, $fields = '*')
    {
        $where = ['requestid' => $requestId, 'workflowid' => $workflowId, 'nodeid' => $nodeId];
        //如果需要根据某字段排序，则待查询字段必须包含排序字段
        $operators = $this->getList($where, 'userid, showorder', null, null, 'showorder desc', 'workflow_currentoperator');
        return $this->getHrmResource(array_column($operators, 'userid'), $fields)[0] ?? [];
    }

    /**
     * 模板变量解析,支持使用函数
     * 格式： {$varname|function1|function2=arg1,arg2}
     * @param array $fields 字段
     * @param array $origin db原始数据
     * @return array
     */
    public function parseVar($fields, $origin)
    {
        $data = $tmp = [];
        foreach ($fields as $k => $v) {
            $v = trim($v);
            $parseStr = '';
            //如果已经解析过该变量字串，则直接返回变量值
            if (isset($tmp[$v])) {
                $data[$k] = $tmp[$v];
                continue;
            }
            if (!empty($v)) {
                $varArray = explode('|', $v);
                //取得变量名称
                $var = array_shift($varArray);
                $parseStr = isset($origin[$var]) ? trim($origin[$var]) : null;
                //对变量使用函数
                $parseStr = $this->parseVarFunction($parseStr, $varArray, $origin);
            }
            $data[$k] = $tmp[$v] = trim($parseStr);
        }

        return $data;
    }

    /**
     * 判断节点是否有驳回记录
     * @param $requestId
     * @param $nodeId
     * @param int $timestamp
     * @return bool
     */
    public function hasNodeRejectRecord($requestId, $nodeId, $timestamp = 0)
    {
        $where = [
            ['requestid', '=', $requestId],
            ['nodeid', '=', $nodeId],
            ['operatetype', '=', 'reject'],
            ['operatedate', '>=', date('Y-m-d', $timestamp)],
        ];
        //日期和时间两个字段，需拼接后进行比较
        $whereRaw = "[operatedate] + ' ' + [operatetime] > '".date('Y-m-d H:i:s', $timestamp)."'";
        if (!$this->table('workflow_requestoperatelog')->where($where)->whereRaw($whereRaw)->count()) {
            return false;
        }
        return true;
    }

    /**
     * OA系统处理资金系统通知
     * @param array $data
     * @return mixed
     */
    public function handleNotify($data)
    {
        //获取OA通知处理函数
        $notifyConfig = $this->getOaNotifyConfig(null, $data);
        $method = isset($notifyConfig['type']) ? $notifyConfig['type'].'Handler' : null;
        if (empty($method) || !is_callable([$this, $method])) {
            return packRet(__LINE__, '无法处理此通知');
        }
        //查询workflow_requestbase表
        $where = [
            ['requestid', '=', $data['request_id']],
            ['currentnodetype', '<>', 3],
        ];
        $workflowRequestBase = $this->getOne($where, 'workflowid, currentnodeid', null, 'workflow_requestbase');
        if (empty($workflowRequestBase)) {
            return packRet(__LINE__, 'OA没有此流程或者此流程节点已经发生流转');
        }
        $config = $this->getOaCallConfig($workflowRequestBase['workflowid'], $workflowRequestBase['currentnodeid']);
        //流程节点未在配置信息中查询到，说明流程的当前节点已经发生流转或者未配置此付款业务，无需通知OA处理
        if (empty($config) || empty($config['workflowid']) || empty($config['currentnodeid'])) {
            return packRet(__LINE__, 'OA付款业务配置有误或者此流程节点已经发生流转');
        }
        //获取workflowtype字段
        $workflowBase = $this->getWorkflowBase($workflowRequestBase['workflowid'], 'workflowtype');
        $config['workflowtype'] = $workflowBase['workflowtype'] ?? 0;
        //通知处理逻辑
        return call_user_func_array([$this, $method], [$config, $data]);
    }

    /**
     * 对模板变量使用函数
     * 格式 {$varname|function1|function2=arg1,arg2}
     * @param string $name 变量名
     * @param array $varArray 函数列表
     * @param array $origin db原始数据
     * @return string
     */
    private function parseVarFunction($name, $varArray, $origin)
    {
        //对变量使用函数
        $length = count($varArray);
        for ($i = 0; $i < $length; $i++) {
            $args = explode('=', $varArray[$i], 2);
            //模板函数过滤
            $fun = trim($args[0]);
            //参数过滤
            $params = empty($args[1]) ? [] : explode(',', trim($args[1]));
            if (in_array('###', $params)) {
                $params = array_map(function ($v) use ($name) {
                    return $v == '###' ? $name : $v;
                }, $params);
            } else {
                array_unshift($params, $name);
            }
            foreach ($params as &$v) {
                $this->parseVarItem($origin, $v, $v);
            }
            //调用模板函数
            if ($fun == 'default') {
                empty($name) && $name = array_pop($params);
            } elseif (is_callable($fun)) {
                $name = call_user_func_array($fun, $params);
            } elseif (is_callable([$this, $fun])) {
                $name = call_user_func_array([$this, $fun], $params);
            } elseif ($this->parseVarItem($name, $fun, $name)) {//点语法
                continue;
            } else {
                break;
            }
        }
        return $name;
    }

    /**
     * 解析模板变量的字段
     * @param $input
     * @param $key
     * @param $output
     * @return bool
     */
    private function parseVarItem($input, $key, &$output)
    {
        $result = true;
        if (is_string($key) && 0 === strpos($key, '.')) {
            $key = ltrim($key, '.');
            if (is_array($input) && isset($input[$key])) {
                //获取数组元素
                $output = $input[$key];
            } elseif (is_object($input) && property_exists($input, $key)) {
                //获取对象属性
                $output = $input->$key;
            } else {
                $result = false;
            }
        } else {
            $result = false;
        }
        return $result;
    }

    /**
     * 获取人力资源信息
     * @param array $ids
     * @param string $fields
     * @return array
     */
    private function getHrmResource($ids, $fields = '*')
    {
        return $this->getList(['id' => $ids], $fields, null, null, null, 'HrmResource');
    }

    /**
     * 获取人力资源lastname
     * @param array|int $id
     * @return mixed
     */
    private function getHrmResourceLastName($id)
    {
        $list = $this->getHrmResource((array)$id, 'id, lastname');
        $list = array_column($list, 'lastname', 'id');
        return is_array($id) ? $list : ($list[$id] ?? '');
    }

    private function stripTags($str)
    {
        $str = strip_tags($str);
        $str = str_replace(["&nbsp;","&amp;nbsp;","\t","\r\n","\r","\n"],["","","","","",""], $str);

        return $str;
    }

    /**
     * 获取选择框元素
     * @param array $ids
     * @param int $fieldId
     * @param string $fields
     * @return array
     */
    private function getWorkflowSelectItem($ids, $fieldId, $fields = '*')
    {
        return $this->getList(['selectvalue' => $ids, 'fieldid' => $fieldId], $fields, null, null, null, 'workflow_SelectItem');
    }

    /**
     * 获取选择框元素的名称
     * @param array|int $id
     * @param int $fieldId
     * @return mixed
     */
    private function getWorkflowSelectItemName($id, $fieldId)
    {
        $list = $this->getWorkflowSelectItem((array)$id, $fieldId, 'selectvalue, selectname');
        $list = array_column($list, 'selectname', 'selectvalue');
        return is_array($id) ? $list : ($list[$id] ?? '');
    }

    /**
     * 获取客户信息
     * @param array $ids
     * @param string $fields
     * @return array
     */
    private function getCrmCustomerInfo($ids, $fields = '*')
    {
        return $this->getList(['id' => $ids], $fields, null, null, null, 'CRM_CustomerInfo');
    }

    /**
     * 获取客户信息
     * @param array|int $id
     * @return mixed
     */
    private function getCrmCustomerName($id)
    {
        $list = $this->getCrmCustomerInfo((array)$id, 'id, name');
        $list = array_column($list, 'name', 'id');
        return is_array($id) ? $list : ($list[$id] ?? '');
    }

    private function getCrmCustomerAccountName($id)
    {
        $list = $this->getCrmCustomerInfo((array)$id, 'id, accountName');
        $list = array_column($list, 'accountName', 'id');
        return is_array($id) ? $list : ($list[$id] ?? '');
    }

    /**
     * 获取银行信息
     * @param $id
     * @param string $fields
     * @return mixed
     */
    private function getCusFieldData($id, $fields = '*')
    {
        static $list = [];
        if (empty($list[$id])) {
            $list[$id] = $this->getOne(['id' => $id, 'scopeid' => 1], $fields, null, 'cus_fielddata');
        }
        return $list[$id];
    }

    /**
     * 根据开户行名称获取银行名称
     * @param array|string $bankType
     * @return mixed
     */
    private function getBankName($bankType)
    {
        $bankTypes = array_unique((array)$bankType);
        //简称
        $shortBankList = [
            '工行' => '工商银行',
            '农行' => '农业银行',
            '中行' => '中国银行',
            '建行' => '建设银行',
            '交行' => '交通银行',
            '招行' => '招商银行',
            '中信' => '中信银行',
            '浦东发展银行' => '浦发银行',
        ];
        //全称
        static $bankList = [];
        if (empty($bankList)) {
            $oDictKv = new DataDictKv();
            $bankList = $oDictKv->getListByDictType('bank', 'dict_value');
        }

        $list = [];
        foreach ($bankTypes as $bankType) {
            //从简称中查找
            foreach ($shortBankList as $key => $item) {
                if (strpos($bankType, $key) !== false) {
                    $list[$bankType] = $item;
                    break;
                }
            }
            if (isset($list[$bankType])) {
                continue;
            }
            //从全称中查找
            foreach ($bankList as $item) {
                if (strpos($bankType, $item) !== false) {
                    $list[$bankType] = $item;
                    break;
                }
            }
            //未查找到
            if (!isset($list[$bankType])) {
                $list[$bankType] = $bankType;
            }
        }
        return is_array($bankType) ? $list : ($list[$bankType] ?? '');
    }

    /**
     * 获取附件URL
     * @param array|string $ids
     * @param $requestId
     * @return string
     */
    private function getAttachmentUrl($ids, $requestId)
    {
        !is_array($ids) && $ids = explode(',', $ids);
        //获取文件名
        $list = $this->getList(['docid' => $ids], 'imagefileid, imagefilename', null, null, null, 'DocImageFile');
        $result = [];
        foreach ($list as $row) {
            $result[] = [
                'name' => $row['imagefilename'],
                'url' => constant('ENV_OA_BASE_URL')."/weaver/weaver.file.FileDownload?f_weaver_belongto_userid=1&f_weaver_belongto_usertype=null&fileid={$row['imagefileid']}&download=1&requestid={$requestId}&desrequestid=0",
            ];
        }
        return json_encode($result, JSON_UNESCAPED_UNICODE);
    }

    /**
     * 获取workflow_base表数据
     * @param int $id
     * @param string $fields
     * @return array|null
     */
    private function getWorkflowBase($id, $fields = '*')
    {
        static $list = [];
        $key = $id.$fields;
        if (!isset($list[$key])) {
            $list[$key] = $this->getOne(['id' => $id], $fields, null, 'workflow_base');
        }
        return $list[$key];
    }

    /**
     * 业务成功的处理
     * @param $config
     * @param $origin
     * @return array
     * @throws \think\exception\PDOException
     */
    private function successHandler($config, $origin)
    {
        $workflowType = $config['workflowtype'];
        $workflowId = $config['workflowid'];
        $currentNodeId = $config['currentnodeid'];
        $nextNodeId = $config['success_nodeid'];
        $startNodeId = $config['reject_nodeid'];
        $requestId = $origin['request_id'];
        list($operateDate, $operateTime) = explode(' ', $origin['update_time']);

        try {
            $remark = $origin['audit_log']['deal_remark'] ?? '';
            //当前节点操作人
            $operator = $this->getWorkflowNodeOperator($requestId, $workflowId, $currentNodeId, 'id, departmentid');
            $operatorId = $operator['id'] ?? 0;
            $operatorDept = $operator['departmentid'] ?? 0;
            //下一节点操作人，即归档节点操作人，和发起节点的一致
            $nextNodeOperator = $this->getWorkflowNodeOperator($requestId, $workflowId, $startNodeId, 'id, lastname');
            $nextNodeOperatorId = $nextNodeOperator['id'] ?? 0;
            $nextNodeOperatorName = $nextNodeOperator['lastname'] ?? null;
            $receivedPersonIds = !empty($nextNodeOperatorId) ? implode([$nextNodeOperatorId, ''], ',') : '';
            $receivedPersons = !empty($nextNodeOperatorName) ? implode([$nextNodeOperatorName, ''], ',') : '';

            $where = [
                ['workflowid', '=', $workflowId],
                ['nodeid', '=', $currentNodeId],
                ['destnodeid', '=', $nextNodeId],
            ];
            $nodeLink = $this->getOne($where, 'linkname', null, 'workflow_nodelink');
            $linkName = $nodeLink['linkname'] ?? '出口';

            //开始事务
            $this->startTrans();
            //写入workflow_requestbase表
            $where = [
                ['requestid', '=', $requestId],
                ['workflowid', '=', $workflowId],
                ['currentnodeid', '=', $currentNodeId],
                ['currentnodetype', '<>', 3],
            ];
            $data = [
                'lastnodeid' => $currentNodeId,
                'currentnodeid' => $nextNodeId,
                'currentnodetype' => 3,
                'status' => $linkName,
                'totalgroups' => 2,//TODO 操作组数
                'lastoperator' => $operatorId,//当前节点操作人ID
                'lastoperatedate' => $operateDate,
                'lastoperatetime' => $operateTime,
                'hrmids' => '0',
            ];
            if (!$this->table('workflow_requestbase')->where($where)->update($data)) {
                throw new \Exception('workflow_requestbase表更新数据失败');
            }

            //写入workflow_nownode表
            $where = [
                ['requestid', '=', $requestId],
            ];
            $data = [
                'nownodeid' => $nextNodeId,
                'nownodetype' => 3,
            ];
            if ($this->table('workflow_nownode')->where($where)->update($data) === false) {
                throw new \Exception('workflow_nownode表更新数据失败');
            }

            //写入workflow_requestLog表
            $data = [
                'requestid' => $requestId,
                'workflowid' => $workflowId,
                'nodeid' => $currentNodeId,
                'logtype' => 0,
                'operatedate' => $operateDate,
                'operatetime' => $operateTime,
                'operator' => $operatorId,//当前节点操作人ID
                'remark' => $remark,
                'clientip' => \SystemUtil::getLocalHost(),
                'operatortype' => 0,
                'destnodeid' => $nextNodeId,
                'receivedPersons' => $receivedPersons,//下一节点操作人，逗号分隔，逗号结尾
                'showorder' => 1,
                'agentorbyagentid' => -1,
                'agenttype' => 0,
                'requestLogId' => -1,
                'operatorDept' => $operatorDept,//当前节点操作人部门ID
                'HandWrittenSign' => 0,
                'SpeechAttachment' => 0,
                'receivedpersonids' => $receivedPersonIds,//下一节点操作人ID，逗号分隔，逗号结尾
            ];
            if ($this->table('workflow_requestLog')->insert($data) === false) {
                throw new \Exception('workflow_requestLog表插入数据失败');
            }
            $requestLogId = $this->getLastInsID();

            //写入workflow_requestoperatelog表
            $data = [
                'requestid' => $requestId,
                'nodeid' => $currentNodeId,
                'isremark' => 0,
                'operatorid' => $operatorId,//当前节点操作人ID
                'operatortype' => 0,
                'operatedate' => $operateDate,
                'operatetime' => $operateTime,
                'operatetype' => 'submit',
                'operatename' => '提交',
                'operatecode' => '1',
            ];
            if ($this->table('workflow_requestoperatelog')->insert($data) === false) {
                throw new \Exception('workflow_requestoperatelog表插入数据失败');
            }
            $requestOperatorLogId = $this->getLastInsID();

            //写入workflow_currentoperator表
            $where = [
                ['requestid', '=', $requestId],
                ['workflowid', '=', $workflowId],
            ];
            $data = [
                'iscomplete' => 1,
            ];
            //更新所有节点的操作记录
            if ($this->table('workflow_currentoperator')->where($where)->update($data) === false) {
                throw new \Exception('workflow_currentoperator表更新数据失败');
            }
            $where = [
                ['requestid', '=', $requestId],
                ['workflowid', '=', $workflowId],
                ['nodeid', '=', $currentNodeId],
                ['operatedate', 'null', ''],
                ['operatetime', 'null', ''],
            ];
            $data = [
                'isremark' => 2,
                'viewtype' => -2,
                'operatedate' => $operateDate,
                'operatetime' => $operateTime,
                'needwfback' => 0,
            ];
            //更新当前节点的操作记录
            if ($this->table('workflow_currentoperator')->where($where)->update($data) === false) {
                throw new \Exception('workflow_currentoperator表更新数据失败');
            }
            //写入下一节点的操作记录
            $data = [
                'requestid' => $requestId,
                'userid' => $nextNodeOperatorId,//下一节点操作人ID
                'groupid' => 3,//TODO 下一节点操作组ID
                'workflowid' => $workflowId,
                'workflowtype' => $workflowType,
                'isremark' => 4,
                'usertype' => 0,
                'nodeid' => $nextNodeId,
                'agentorbyagentid' => -1,
                'agenttype' => 0,
                'showorder' => 1,
                'receivedate' => $operateDate,
                'receivetime' => $operateTime,
                'viewtype' => 0,
                'iscomplete' => 1,
                'islasttimes' => 1,
                'groupdetailid' => 0,//TODO 下一节点操作组明细ID
                'preisremark' => 0,
                'needwfback' => 1,
            ];
            if ($this->table('workflow_currentoperator')->insert($data) === false) {
                throw new \Exception('workflow_currentoperator表插入数据失败');
            }
            $currentOperatorId = $this->getLastInsID();

            //写入workflow_approvelog表
            $data = [
                'requestid' => $requestId,
                'nodeid' => $currentNodeId,
                'operator' => $operatorId,//当前节点操作人ID
                'logdate' => $operateDate,
                'logtime' => $operateTime,
            ];
            if ($this->table('workflow_approvelog')->insert($data) === false) {
                throw new \Exception('workflow_approvelog表插入数据失败');
            }

            //写入workflow_requestoperatelog_oi表
            $where = [
                ['requestid', '=', $requestId],
                ['workflowid', '=', $workflowId],
            ];
            $requestLogCount = $this->table('workflow_requestLog')->where($where)->count();
            $currentOperatorCount = $this->table('workflow_currentoperator')->where($where)->count();
            $data = [
                ['requestid' => $requestId, 'optlogid' => $requestOperatorLogId, 'entitytype' => 1, 'entityid' => $currentOperatorId, 'count' => $currentOperatorCount],
                ['requestid' => $requestId, 'optlogid' => $requestOperatorLogId, 'entitytype' => 2, 'entityid' => $requestLogId, 'count' => $requestLogCount],
            ];
            $this->table('workflow_requestoperatelog_oi')->insertAll($data);//写入失败没什么影响，故不抛出异常

            //写入formtable_main表
            if (!empty($origin['formtable_main'])) {
                $table = $config['table'];
                //只有部分表有此字段
                $diff = array_diff(['lsh', 'hdlj'], $this->getTableFields($table));
                if (empty($diff)) {
                    $where = [
                        ['requestid', '=', $requestId],
                    ];
                    $data = [
                        'lsh' => $origin['formtable_main']['lsh'] ?? '',
                        'hdlj' => $origin['formtable_main']['hdlj'] ?? '',
                    ];
                    if ($this->table($table)->where($where)->update($data) === false) {
                        throw new \Exception($table.'表更新数据失败');
                    }
                }
            }

            $this->commit();
        } catch (\Exception $e) {
            $this->rollback();
            return packRet(__LINE__, 'OA工作流数据更新失败：'.$e->getMessage().'，SQL：'.$this->getLastSql());
        }
        return packRet(0, 'success');
    }

    /**
     * 业务驳回的处理
     * @param $config
     * @param $origin
     * @return array
     * @throws \think\exception\PDOException
     */
    private function rejectHandler($config, $origin)
    {
        $workflowType = $config['workflowtype'];
        $workflowId = $config['workflowid'];
        $currentNodeId = $config['currentnodeid'];
        $nextNodeId = $config['reject_nodeid'];
        $startNodeId = $config['reject_nodeid'];
        $requestId = $origin['request_id'];
        list($operateDate, $operateTime) = explode(' ', $origin['update_time']);

        try {
            $remark = $origin['audit_log']['deal_remark'] ?? '';
            //当前节点操作人
            $operator = $this->getWorkflowNodeOperator($requestId, $workflowId, $currentNodeId, 'id, departmentid');
            $operatorId = $operator['id'] ?? 0;
            $operatorDept = $operator['departmentid'] ?? 0;
            //下一节点操作人，即发起节点操作人
            $nextNodeOperator = $this->getWorkflowNodeOperator($requestId, $workflowId, $startNodeId, 'id, lastname');
            $nextNodeOperatorId = $nextNodeOperator['id'] ?? 0;
            $nextNodeOperatorName = $nextNodeOperator['lastname'] ?? null;
            $receivedPersonIds = !empty($nextNodeOperatorId) ? implode([$nextNodeOperatorId, ''], ',') : '';
            $receivedPersons = !empty($nextNodeOperatorName) ? implode([$nextNodeOperatorName, ''], ',') : '';

            $where = [
                ['workflowid', '=', $workflowId],
                ['nodeid', '=', $currentNodeId],
                ['destnodeid', '=', $nextNodeId],
            ];
            $nodeLink = $this->getOne($where, 'linkname', null, 'workflow_nodelink');
            $linkName = $nodeLink['linkname'] ?? '出口';

            //开始事务
            $this->startTrans();
            //写入workflow_requestbase表
            $where = [
                ['requestid', '=', $requestId],
                ['workflowid', '=', $workflowId],
                ['currentnodeid', '=', $currentNodeId],
                ['currentnodetype', '<>', 3],
            ];
            $data = [
                'lastnodeid' => $currentNodeId,
                'currentnodeid' => $nextNodeId,
                'currentnodetype' => 0,
                'status' => $linkName,
                'totalgroups' => 1,//TODO 操作组数
                'lastoperator' => $operatorId,//当前节点操作人ID
                'lastoperatedate' => $operateDate,
                'lastoperatetime' => $operateTime,
            ];
            if (!$this->table('workflow_requestbase')->where($where)->update($data)) {
                throw new \Exception('workflow_requestbase表更新数据失败');
            }

            //写入workflow_nownode表
            $where = [
                ['requestid', '=', $requestId],
            ];
            $data = [
                'nownodeid' => $nextNodeId,
                'nownodetype' => 0,
            ];
            if ($this->table('workflow_nownode')->where($where)->update($data) === false) {
                throw new \Exception('workflow_nownode表更新数据失败');
            }

            //写入workflow_requestLog表
            $data = [
                'requestid' => $requestId,
                'workflowid' => $workflowId,
                'nodeid' => $currentNodeId,
                'logtype' => 3,
                'operatedate' => $operateDate,
                'operatetime' => $operateTime,
                'operator' => $operatorId,//当前节点操作人ID
                'remark' => $remark,
                'clientip' => \SystemUtil::getLocalHost(),
                'operatortype' => 0,
                'destnodeid' => $nextNodeId,
                'receivedPersons' => $receivedPersons,//下一节点操作人，逗号分隔，逗号结尾
                'showorder' => 1,
                'agentorbyagentid' => -1,
                'agenttype' => 0,
                'requestLogId' => -1,
                'operatorDept' => $operatorDept,//当前节点操作人部门ID
                'HandWrittenSign' => 0,
                'SpeechAttachment' => 0,
                'receivedpersonids' => $receivedPersonIds,//下一节点操作人ID，逗号分隔，逗号结尾
            ];
            if ($this->table('workflow_requestLog')->insert($data) === false) {
                throw new \Exception('workflow_requestLog表插入数据失败');
            }
            $requestLogId = $this->getLastInsID();

            //写入workflow_requestoperatelog表
            $data = [
                'requestid' => $requestId,
                'nodeid' => $currentNodeId,
                'isremark' => 0,
                'operatorid' => $operatorId,//当前节点操作人ID
                'operatortype' => 0,
                'operatedate' => $operateDate,
                'operatetime' => $operateTime,
                'operatetype' => 'reject',
                'operatename' => '退回',
                'operatecode' => '2',
            ];
            if ($this->table('workflow_requestoperatelog')->insert($data) === false) {
                throw new \Exception('workflow_requestoperatelog表插入数据失败');
            }
            $requestOperatorLogId = $this->getLastInsID();

            //写入workflow_currentoperator表
            $where = [
                ['requestid', '=', $requestId],
                ['workflowid', '=', $workflowId],
            ];
            $data = [
                'isreject' => 1,
            ];
            //更新所有节点的操作记录
            if ($this->table('workflow_currentoperator')->where($where)->update($data) === false) {
                throw new \Exception('workflow_currentoperator表更新数据失败');
            }
            $where = [
                ['requestid', '=', $requestId],
                ['workflowid', '=', $workflowId],
                ['nodeid', '=', $currentNodeId],
                ['operatedate', 'null', ''],
                ['operatetime', 'null', ''],
            ];
            $data = [
                'isremark' => 2,
                'viewtype' => -2,
                'operatedate' => $operateDate,
                'operatetime' => $operateTime,
            ];
            //更新当前节点的操作记录
            if ($this->table('workflow_currentoperator')->where($where)->update($data) === false) {
                throw new \Exception('workflow_currentoperator表更新数据失败');
            }
            //写入下一节点的操作记录
            $data = [
                'requestid' => $requestId,
                'userid' => $nextNodeOperatorId,//下一节点操作人ID
                'groupid' => 3,//TODO 下一节点操作组ID
                'workflowid' => $workflowId,
                'workflowtype' => $workflowType,
                'isremark' => 0,
                'usertype' => 0,
                'nodeid' => $nextNodeId,
                'agentorbyagentid' => -1,
                'agenttype' => 0,
                'showorder' => 1,
                'receivedate' => $operateDate,
                'receivetime' => $operateTime,
                'viewtype' => 0,
                'iscomplete' => 0,
                'islasttimes' => 1,
                'groupdetailid' => 0,//TODO 下一节点操作组明细ID
                'preisremark' => 0,
                'needwfback' => 1,
            ];
            if ($this->table('workflow_currentoperator')->insert($data) === false) {
                throw new \Exception('workflow_currentoperator表插入数据失败');
            }
            $currentOperatorId = $this->getLastInsID();

            //写入workflow_approvelog表
            $where = [
                ['requestid', '=', $requestId],
            ];
            if ($this->table('workflow_approvelog')->where($where)->delete() === false) {
                throw new \Exception('workflow_approvelog表删除数据失败');
            }

            //写入workflow_requestoperatelog_oi表
            $where = [
                ['requestid', '=', $requestId],
                ['workflowid', '=', $workflowId],
            ];
            $requestLogCount = $this->table('workflow_requestLog')->where($where)->count();
            $currentOperatorCount = $this->table('workflow_currentoperator')->where($where)->count();
            $data = [
                ['requestid' => $requestId, 'optlogid' => $requestOperatorLogId, 'entitytype' => 1, 'entityid' => $currentOperatorId, 'count' => $currentOperatorCount],
                ['requestid' => $requestId, 'optlogid' => $requestOperatorLogId, 'entitytype' => 2, 'entityid' => $requestLogId, 'count' => $requestLogCount],
            ];
            $this->table('workflow_requestoperatelog_oi')->insertAll($data);//写入失败没什么影响，故不抛出异常

            $this->commit();
        } catch (\Exception $e) {
            $this->rollback();
            return packRet(__LINE__, 'OA工作流数据更新失败：'.$e->getMessage().'，SQL：'.$this->getLastSql());
        }
        return packRet(0, 'success');
    }

    /**
     * 业务拒绝的处理
     * @param $config
     * @param $origin
     * @return array
     * @throws \think\exception\PDOException
     */
    private function refuseHandler($config, $origin)
    {
        $workflowType = $config['workflowtype'];
        $workflowId = $config['workflowid'];
        $currentNodeId = $config['currentnodeid'];
        $nextNodeId = $config['refuse_nodeid'];
        $startNodeId = $config['reject_nodeid'];
        $requestId = $origin['request_id'];
        list($operateDate, $operateTime) = explode(' ', $origin['update_time']);

        try {
            $remark = $origin['audit_log']['deal_remark'] ?? '';
            //当前节点操作人
            $operator = $this->getWorkflowNodeOperator($requestId, $workflowId, $currentNodeId, 'id, departmentid');
            $operatorId = $operator['id'] ?? 0;
            $operatorDept = $operator['departmentid'] ?? 0;
            //下一节点操作人，即归档节点操作人，和发起节点的一致
            $nextNodeOperator = $this->getWorkflowNodeOperator($requestId, $workflowId, $startNodeId, 'id, lastname');
            $nextNodeOperatorId = $nextNodeOperator['id'] ?? 0;
            $nextNodeOperatorName = $nextNodeOperator['lastname'] ?? null;
            $receivedPersonIds = !empty($nextNodeOperatorId) ? implode([$nextNodeOperatorId, ''], ',') : '';
            $receivedPersons = !empty($nextNodeOperatorName) ? implode([$nextNodeOperatorName, ''], ',') : '';

            $where = [
                ['workflowid', '=', $workflowId],
                ['nodeid', '=', $currentNodeId],
                ['destnodeid', '=', $nextNodeId],
            ];
            $nodeLink = $this->getOne($where, 'linkname', null, 'workflow_nodelink');
            $linkName = $nodeLink['linkname'] ?? '出口';

            //开始事务
            $this->startTrans();
            //写入workflow_requestbase表
            $where = [
                ['requestid', '=', $requestId],
                ['workflowid', '=', $workflowId],
                ['currentnodeid', '=', $currentNodeId],
                ['currentnodetype', '<>', 3],
            ];
            $data = [
                'lastnodeid' => $currentNodeId,
                'currentnodeid' => $nextNodeId,
                'currentnodetype' => 3,
                'status' => $linkName,
                'totalgroups' => 2,//TODO 操作组数
                'lastoperator' => $operatorId,//当前节点操作人ID
                'lastoperatedate' => $operateDate,
                'lastoperatetime' => $operateTime,
                'hrmids' => '0',
            ];
            if (!$this->table('workflow_requestbase')->where($where)->update($data)) {
                throw new \Exception('workflow_requestbase表更新数据失败');
            }

            //写入workflow_nownode表
            $where = [
                ['requestid', '=', $requestId],
            ];
            $data = [
                'nownodeid' => $nextNodeId,
                'nownodetype' => 3,
            ];
            if ($this->table('workflow_nownode')->where($where)->update($data) === false) {
                throw new \Exception('workflow_nownode表更新数据失败');
            }

            //写入workflow_requestLog表
            $data = [
                'requestid' => $requestId,
                'workflowid' => $workflowId,
                'nodeid' => $currentNodeId,
                'logtype' => 3,
                'operatedate' => $operateDate,
                'operatetime' => $operateTime,
                'operator' => $operatorId,//当前节点操作人ID
                'remark' => $remark,
                'clientip' => \SystemUtil::getLocalHost(),
                'operatortype' => 0,
                'destnodeid' => $nextNodeId,
                'receivedPersons' => $receivedPersons,//下一节点操作人，逗号分隔，逗号结尾
                'showorder' => 1,
                'agentorbyagentid' => -1,
                'agenttype' => 0,
                'requestLogId' => -1,
                'operatorDept' => $operatorDept,//当前节点操作人部门ID
                'HandWrittenSign' => 0,
                'SpeechAttachment' => 0,
                'receivedpersonids' => $receivedPersonIds,//下一节点操作人ID，逗号分隔，逗号结尾
            ];
            if ($this->table('workflow_requestLog')->insert($data) === false) {
                throw new \Exception('workflow_requestLog表插入数据失败');
            }
            $requestLogId = $this->getLastInsID();

            //写入workflow_requestoperatelog表
            $data = [
                'requestid' => $requestId,
                'nodeid' => $currentNodeId,
                'isremark' => 0,
                'operatorid' => $operatorId,//当前节点操作人ID
                'operatortype' => 0,
                'operatedate' => $operateDate,
                'operatetime' => $operateTime,
                'operatetype' => 'reject',
                'operatename' => '退回',
                'operatecode' => '2',
            ];
            if ($this->table('workflow_requestoperatelog')->insert($data) === false) {
                throw new \Exception('workflow_requestoperatelog表插入数据失败');
            }
            $requestOperatorLogId = $this->getLastInsID();

            //写入workflow_currentoperator表
            $where = [
                ['requestid', '=', $requestId],
                ['workflowid', '=', $workflowId],
            ];
            $data = [
                'iscomplete' => 1,
            ];
            //更新所有节点的操作记录
            if ($this->table('workflow_currentoperator')->where($where)->update($data) === false) {
                throw new \Exception('workflow_currentoperator表更新数据失败');
            }
            $where = [
                ['requestid', '=', $requestId],
                ['workflowid', '=', $workflowId],
                ['nodeid', '=', $currentNodeId],
                ['operatedate', 'null', ''],
                ['operatetime', 'null', ''],
            ];
            $data = [
                'isremark' => 2,
                'viewtype' => -2,
                'operatedate' => $operateDate,
                'operatetime' => $operateTime,
                'needwfback' => 0,
            ];
            //更新当前节点的操作记录
            if ($this->table('workflow_currentoperator')->where($where)->update($data) === false) {
                throw new \Exception('workflow_currentoperator表更新数据失败');
            }
            //写入下一节点的操作记录
            $data = [
                'requestid' => $requestId,
                'userid' => $nextNodeOperatorId,//下一节点操作人ID
                'groupid' => 3,//TODO 下一节点操作组ID
                'workflowid' => $workflowId,
                'workflowtype' => $workflowType,
                'isremark' => 4,
                'usertype' => 0,
                'nodeid' => $nextNodeId,
                'agentorbyagentid' => -1,
                'agenttype' => 0,
                'showorder' => 1,
                'receivedate' => $operateDate,
                'receivetime' => $operateTime,
                'viewtype' => 0,
                'iscomplete' => 1,
                'islasttimes' => 1,
                'groupdetailid' => 0,//TODO 下一节点操作组明细ID
                'preisremark' => 0,
                'needwfback' => 1,
            ];
            if ($this->table('workflow_currentoperator')->insert($data) === false) {
                throw new \Exception('workflow_currentoperator表插入数据失败');
            }
            $currentOperatorId = $this->getLastInsID();

            //写入workflow_approvelog表
            $data = [
                'requestid' => $requestId,
                'nodeid' => $currentNodeId,
                'operator' => $operatorId,//当前节点操作人ID
                'logdate' => $operateDate,
                'logtime' => $operateTime,
            ];
            if ($this->table('workflow_approvelog')->insert($data) === false) {
                throw new \Exception('workflow_approvelog表插入数据失败');
            }

            //写入workflow_requestoperatelog_oi表
            $where = [
                ['requestid', '=', $requestId],
                ['workflowid', '=', $workflowId],
            ];
            $requestLogCount = $this->table('workflow_requestLog')->where($where)->count();
            $currentOperatorCount = $this->table('workflow_currentoperator')->where($where)->count();
            $data = [
                ['requestid' => $requestId, 'optlogid' => $requestOperatorLogId, 'entitytype' => 1, 'entityid' => $currentOperatorId, 'count' => $currentOperatorCount],
                ['requestid' => $requestId, 'optlogid' => $requestOperatorLogId, 'entitytype' => 2, 'entityid' => $requestLogId, 'count' => $requestLogCount],
            ];
            $this->table('workflow_requestoperatelog_oi')->insertAll($data);//写入失败没什么影响，故不抛出异常

            $this->commit();
        } catch (\Exception $e) {
            $this->rollback();
            return packRet(__LINE__, 'OA工作流数据更新失败：'.$e->getMessage().'，SQL：'.$this->getLastSql());
        }
        return packRet(0, 'success');
    }
}
