<?php
/**
 * User: howie
 * Date: 2017/8/4
 * Time: 11:03
 */

include_once(__DIR__.DIRECTORY_SEPARATOR. 'db'.DIRECTORY_SEPARATOR.'DbClient.php');

class DbUtil
{
    static public $instance;
    protected $clients = array();

    private static $count = 0;

    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * @param string $db
     * @return DbClient
     */
    public function getSqlHandler($db_name = 'master')
    {
        if (empty($this->clients[$db_name]))
        {
            $pdo = $this->getPdo($db_name);
            $this->clients[$db_name] = $pdo;
        }
        else
        {
            $pdo = $this->clients[$db_name];
        }

        return $pdo;
    }

    public function getPdo($db_name = 'master')
    {
        $conf = JmfUtil::getEnvConfig('ENV_DB_' . strtoupper($db_name));
        if (empty($conf)) {
            throw new LogicException('数据库配置错误', ErrMsg::RET_CODE_EXCEPTION);
        }

        $url = new UrlUtil($conf);
        $dsn = $url->getProtocol().':host='.$url->getHost().';port='.$url->getPort().';dbname='.$url->getParameter('dbname');
        $username = $url->getUsername();
        $password = $url->getPassword();

        $options = array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES'utf8';",
            PDO::ATTR_PERSISTENT => true,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION);

        $pdo = new DbClient($dsn,$username,$password, $options);

        return $pdo;
    }
}