<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/5/29
 * Time: 11:17
 * @link https://github.com/top-think/think-orm
 */
namespace money\model;

class Model extends \think\Model
{
    /**
     * Model constructor.
     * @param array $data
     * @param mixed $db
     * @param mixed $table
     */
    public function __construct($data = [], $db = null, $table = '')
    {
        if (!empty($db) && (is_string($db) || is_array($db))) {
            $this->connection = $db;
        }
        if (!empty($this->connection) && is_string($this->connection)) {
            $this->connection = getDbConfig($this->connection);
        }
        if (!empty($table)) {
            $this->table = $table;
        }
        parent::__construct($data);
    }

    /**
     * @param array $where
     * @param string $table
     * @return int|string
     */
    public function getCount($where = [], $table = '')
    {
        if (empty($table) && empty($this->table)) {
            return 0;
        } else {
            empty($table) && $table = $this->table;
        }
        return $this->table($table)->where($where)->count();
    }

    /**
     * @param array $where
     * @param string $fields
     * @param mixed $order
     * @param string $table
     * @return array|null
     */
    public function getOne($where = [], $fields = '*', $order = [], $table = '')
    {
        if (empty($table) && empty($this->table)) {
            return null;
        } else {
            empty($table) && $table = $this->table;
        }
        $result = $this->table($table)->field($fields)->where($where)->order($order)->find();
        return !empty($result) ? $result->toArray() : null;
    }

    /**
     * @param array $where
     * @param string $fields
     * @param int $page
     * @param int $pageSize
     * @param mixed $order
     * @param string $table
     * @return array|null
     */
    public function getList($where = [], $fields = '*', $page = 1, $pageSize = 20, $order = [], $table = '')
    {
        if (empty($table) && empty($this->table)) {
            return null;
        } else {
            empty($table) && $table = $this->table;
        }
        if (!isset($pageSize) || $pageSize < 0) {
            return $this->table($table)->field($fields)->where($where)->order($order)->select()->toArray();
        } else {
            return $this->table($table)->field($fields)->where($where)->order($order)->page($page, $pageSize)->select()->toArray();
        }
    }

    /**
     * @param array $where
     * @param string $fields
     * @param array $order
     * @param string $table
     * @return array|null
     */
    public function getAll($where = [], $fields = '*', $order = [], $table = '')
    {
        if (empty($table) && empty($this->table)) {
            return null;
        } else {
            empty($table) && $table = $this->table;
        }
        return $this->table($table)->field($fields)->where($where)->order($order)->select()->toArray();
    }

    /**
     * 加锁
     * @param $id
     * @param int $ttl
     * @return bool
     */
    public function acquireLock($id, $ttl = 5)
    {
        $ttl = intval($ttl) > 0 ? intval($ttl) : 5;
        $util = new \RedisUtil(ENV_REDIS_BASE_PATH);
        $redis = $util->get_redis();
        return $redis->set($id, 1, ['nx', 'ex' => $ttl]);
    }

    public function beginTransaction()
    {
        $this->startTrans();
    }
}