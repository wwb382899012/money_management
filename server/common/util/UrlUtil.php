<?php
/**
 * Created by PhpStorm.
 * User: lewis.qing
 * Date: 2017/6/22
 * Time: 14:33
 * Desc: URL解析类
 */

class UrlUtil
{
    const URL_SCHEME = 'scheme';
    const URL_USER = 'user';
    const URL_PASS = 'pass';
    const URL_HOST = 'host';
    const URL_PORT = 'port';
    const URL_QUERY = 'query';
    const URL_PATH = 'path';

    protected $protocol = null;
    protected $username = null;
    protected $password = null;
    protected $host = null;
    protected $port = 0;
    protected $path = null;
    protected $params= null;
    protected $parameters = array();

    protected $registryZkUrl = null;
    protected $registryUrl = null;
    protected $codedUrl = null;

    public function __construct($url)
    {
        if (is_string($url))
        {
            if (!$this->ParseString($url))
            {
                throw new \Exception('对象初始化失败');
            }
        }
        elseif (is_array($url))
        {
            if (!$this->ParseArray($url))
            {
                throw new \Exception('对象初始化失败');
            }
        }

    }

    public function ParseString($url)
    {
        $ret = true;
        try
        {
            $urlList = parse_url($url);
            if (!empty($urlList))
            {
                $this->ParseArray($urlList);
            }
        }
        catch (\Exception $e)
        {
            $ret = false;
        }
        return $ret;
    }

    public function ParseArray($urlList)
    {
        if( !isset($urlList[self::URL_SCHEME]) ||
            !isset($urlList[self::URL_HOST]) ||
            !isset($urlList[self::URL_PORT]))
        {
            return false;
        }

        if (isset($urlList[self::URL_SCHEME]))
        {
            $this->protocol = $urlList[self::URL_SCHEME];
        }
        if (isset($urlList[self::URL_USER]))
        {
            $this->username = $urlList[self::URL_USER];
        }
        if (isset($urlList[self::URL_PASS]))
        {
            $this->password = $urlList[self::URL_PASS];
        }
        if (isset($urlList[self::URL_HOST]))
        {
            $this->host = $urlList[self::URL_HOST];
        }
        if (isset($urlList[self::URL_PORT]))
        {
            $this->port = $urlList[self::URL_PORT];
        }
        if (isset($urlList[self::URL_PATH]))
        {
            $this->path = $urlList[self::URL_PATH];
        }

        if (isset($urlList[self::URL_QUERY]))
        {
            $getArgs = array();
            $this->params = $urlList[self::URL_QUERY];
            parse_str($this->params,$getArgs);
            $this->parameters = $getArgs;
        }
        $this->packUrlString();
        return true;
    }

    private function packUrlString()
    {
        if(empty($this->originUrl))
        {
            $this->codedUrl = $this->protocol.'://'.$this->host.':'.$this->port.'/'.$this->path.'?'.$this->params;
            $this->registryUrl = urlencode($this->codedUrl);
        }
        else
        {
            $this->registryUrl = urlencode($this->originUrl);
        }

        return true;
    }

    public function __destruct()
    {

    }

    public function getProtocol()
    {
        return $this->protocol;
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function getAuthority()
    {
        if (($this->username == null || $this->username.length() == 0)
            && ($this->password == null || $this->password.length() == 0))
        {
            return null;
        }
        return ($this->username == null ? "" : $this->username).":" .($this->password == null ? "" : $this->password);
    }

    public function getHost()
    {
        return $this->host;
    }

    public function getUrl()
    {
        return $this->host.DIRECTORY_SEPARATOR.$this->path;
    }

    public function getPort()
    {
        return $this->port;
    }

    public function getAddress()
    {
        return $this->port <= 0? $this->host : $this->host.':'.$this->port;
    }

    public function getBackupAddress()
    {
        return $this->getParameter(JmfConstants::JMF_REGISTRY_BACKUP_KEY);
    }

    public function getPath()
    {
        return $this->path;
    }

    public function getParameters()
    {
        return $this->parameters;
    }

    public function getParameter($key)
    {
        if (isset($this->parameters[$key]))
        {
            return $this->parameters[$key];
        }
        return null;
    }

    public function getUrls()
    {
        return $this->registryUrl;
    }

    public function getGroup($defaultGroup = null)
    {
        $group = $this->getParameter(JmfConstants::JMF_SERVICE_GROUP);
        if (empty($group))
        {
            $group = $defaultGroup;
        }
        return $group;
    }

    public function getSet($defaultSet = null)
    {
        $set = $this->getParameter(JmfConstants::JMF_SERVICE_SET);
        if (empty($set))
        {
            $set = $defaultSet;
        }
        return $set;
    }

    public function getInterface()
    {
        $interface = $this->getParameter('interface');

        return $interface;
    }

    public function getVersion()
    {
        return $this->getParameter(JmfConstants::JMF_SERVICE_VERSION);
    }

    public function getService()
    {
        return $this->getParameter(JmfConstants::JMF_SERVICE_INTERFACE);
    }

    public function getDefaultWeight($defaultWeight = 100)
    {
        $weight = $this->getParameter(JmfConstants::JMF_REGISTRY_WEIGHT);
        if (empty($weight))
        {
            $weight = $defaultWeight;
        }
        return $weight;
    }

    public function getZookeeperPath()
    {
        $root = '/'.JmfConstants::JMF_REGISTRY_NODE;
        $providers = 'providers';

        $this->registryZkUrl = $root.DIRECTORY_SEPARATOR.$this->getInterface().DIRECTORY_SEPARATOR.$providers.DIRECTORY_SEPARATOR.$this->getUrls();

        return $this->registryZkUrl;
    }
}