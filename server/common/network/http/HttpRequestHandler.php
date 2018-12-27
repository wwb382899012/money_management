<?php
/**
 * Created by PhpStorm.
 * User: lewis.qing
 * Date: 2017/7/27
 * Time: 10:59
 */
class HttpRequestHandler
{
    protected $client = null;

    public function init()
    {
    }

    public function __construct()
    {
        $this->client = new \HttpRequest();
    }

    public function __destruct()
    {
        // TODO: Implement __destruct() method.
        if ($this->client) {
            $this->client->close();
        }
    }

    public function setTimeout($timeout = 3, $connection_timeout = 1)
    {
        $this->client->setTimeout($timeout,$connection_timeout);
    }

    public function post($url, $data = null)
    {
        $ret = $this->client->post($url, $data);
        return $this->client->checkResult($ret);
    }

    public function get($url, $data = null)
    {
        $ret = $this->client->get($url, $data);
        return $this->client->checkResult($ret);
    }

    public function timeout($timeout = 3, $connection_timeout = 1)
    {
        $timeout *= 1000;
        $connection_timeout *= 1000;
        if (defined("CURLOPT_TIMEOUT_MS")) {
            $options[CURLOPT_NOSIGNAL] = 1;
            $options[CURLOPT_TIMEOUT_MS] = $timeout;
        } else {
            $options[CURLOPT_TIMEOUT] = ceil($timeout / 1000);
        }

        if (defined("CURLOPT_CONNECTTIMEOUT_MS")) {
            $options[CURLOPT_CONNECTTIMEOUT_MS] = $connection_timeout;
        } else {
            $options[CURLOPT_CONNECTTIMEOUT] = ceil($connection_timeout / 1000);
        }

        $this->httpOptions($options);

        return $this;
    }

    public function setCookies($cookies)
    {
        $options[CURLOPT_COOKIEFILE] = $cookies;
        $options[CURLOPT_COOKIEJAR] = $cookies;
    }

    public function httpOptions($options = array())
    {
        $this->client->setOptions($options);

        return $this;
    }

    public function setHeader($key, $value)
    {
        $this->client->setHeader($key, $value);
    }

    public function error()
    {
        return array('code'=>$this->client->getErrorCode(), 'msg'=>$this->client->getErrorMessage());
    }

    public function status()
    {
        return $this->client->httpStatus();
    }
}