<?php
/**
 * User: howie
 * Date: 2017/8/7
 * Time: 18:23
 */

/**
 * Class SqlClient
 * @method beginTransaction() 开始事务
 * @method commit() 提交事务
 * @method rollBack() 回滚事务
 * @method bool inTransaction() 是否在事务内
 * @method bool lastInsertId() 返回最后插入行的ID或序列值
 */
class DbClient
{
    /**
     * @var PDO
     */
    protected $PDO = null;
    protected $loggs = null;
    private $_dns = null;
    private $_user_name = null;
    private $_pass_word = null;
    private $_options = array();

    public function __construct($dns, $username = null, $password = null, $driver_options = array())
    {
        $this->_dns = $dns;
        $this->_user_name = $username;
        $this->_pass_word = $password;
        $this->_options = $driver_options;

        $this->logger = CommonLog::instance()->getDbLogger();
        //$this->PDO->setAttribute(PDO::ATTR_PERSISTENT , false);
        //$this->PDO->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        $this->connect();
        return $this;
    }

    private function connect()
    {
        $this->logger->info($this->_dns.';user='.$this->_user_name.';pwd='.$this->_pass_word);
        $this->PDO = new PDO($this->_dns, $this->_user_name, $this->_pass_word, $this->_options);
    }

    public function __call($name, $args)
    {
        if (!method_exists($this->PDO, $name)) {
            throw new LogicException('PDO类未定义此方法', ErrMsg::RET_CODE_EXCEPTION);
        }
        return call_user_func_array(array($this->PDO, $name), $args);
    }

    public function getPDO()
    {
        return $this->PDO;
    }

    public function query($sql, $input_parameters = array())
    {
        try
        {
            $this->log($sql, $input_parameters);
            $sth = $this->prepare($sql, $input_parameters);
            return $sth->fetchAll(PDO::FETCH_ASSOC);
        }
        catch (\Exception $e)
        {
            $this->logger->error($e->getMessage()." 数据库进行重连，执行[$sql]", $e);
            $this->connect();

            $sth = $this->prepare($sql, $input_parameters);
            return $sth->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    public function exec($sql, $input_parameters = array())
    {
        try
        {
            $this->log($sql, $input_parameters);
            $sth = $this->prepare($sql, $input_parameters);
            return $sth->rowCount();
        }
        catch (\Exception $e)
        {
            $this->logger->error($e->getMessage()." 数据库进行重连，执行[$sql]", $e);
            $this->connect();

            $sth = $this->prepare($sql, $input_parameters);
            return $sth->rowCount();
        }
    }

    /**
     * 事务
     * @param $callback
     * @return bool
     * @throws Exception
     */
    public function transaction(Closure $callback)
    {
        try{
            $ret = $this->_execTransaction($callback);
        }catch(\Exception $e)
        {
            $this->connect();
            $ret = $this->_execTransaction($callback);
        }
        return $ret;
    }

    /**
     * 记录mysql日志
     */
    private function log($sql, $input_parameters){
        if (defined('MYSQL_LOG') && MYSQL_LOG){
            if(!empty($input_parameters)){
                $keys = array_keys($input_parameters);
                if(strstr($sql, '?') !== false){
                    $keys = array_fill(0, count($keys), '?');
                }
                $sql = str_replace($keys, $input_parameters, $sql);
            }
            $this->logger->info("SQL:".$sql);
        }
    }

    private function _execTransaction($callback)
    {
        $db = $this->PDO;
        $db->beginTransaction();
        try {
            $ret = call_user_func_array($callback, array(&$db));
        } catch (Exception $exception) {
            $db->rollBack();
            throw $exception;
        }
        $db->commit();
        return $ret;
    }


    private function prepare($sql, $input_parameters = array())
    {
        $sth = $this->PDO->prepare($sql);

        foreach ($input_parameters as $key => $parameter) {
            $sth->bindValue($key, $parameter);
        }
        $sth->execute();
        return $sth;
    }
}