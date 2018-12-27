<?php

class HttpRequest
{
    public $options;

    private $_ch;
    private $_config = array(
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_AUTOREFERER => true,
        CURLOPT_CONNECTTIMEOUT => 1,
        CURLOPT_TIMEOUT => 5,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_BINARYTRANSFER => true,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:5.0) Gecko/20110619 Firefox/5.0',
    );

    private $_header = array();

    public function __construct()
    {
        $this->init();
    }

    public function preInit(array &$attributes)
    {

    }

    public function init() 
    {
        try {
            $this->_ch = curl_init();
        } catch (\Exception $e) {
            throw new \Exception('Curl Not Installed');
        }
    }

    public function setTimeout($timeout = 3, $connectTimeout = 1)
    {
        $timeout *= 1000;
        $connectTimeout *= 1000;
        if (defined("CURLOPT_TIMEOUT_MS")) {
            $this->_config[CURLOPT_NOSIGNAL] = 1;
            $this->_config[CURLOPT_TIMEOUT_MS] = $timeout;
        } else {
            $this->_config[CURLOPT_TIMEOUT] = ceil($timeout / 1000);
        }

        if (defined("CURLOPT_CONNECTTIMEOUT_MS")) {
            $this->_config[CURLOPT_CONNECTTIMEOUT_MS] = $connectTimeout;
        } else {
            $this->_config[CURLOPT_CONNECTTIMEOUT] = ceil($connectTimeout / 1000);
        }
    }

    public function setHeader($key, $value)
    {
        $this->_header[$key] = $value;
    }

    public function setConfigOptions()
    {
        $header = array();
        if (!empty($this->_header))
        {
            foreach ($this->_header as $key => $value)
            {
                $header[] = $key.':'.$value;
            }
        }

        $this->_config[CURLOPT_HTTPHEADER] = $header;
        $this->setOptions($this->_config);
    }

    public function httpStatus()
    {
       return curl_getinfo($this->_ch, CURLINFO_HTTP_CODE);
    }

    public function _exec($url)
    {
        $this->setOption(CURLOPT_URL, $url);
        $c = curl_exec($this->_ch);
        if (!curl_errno($this->_ch)) {
            return $c;
        }  else{
            return curl_error($this->_ch);
        }
    }
    public function _execEx()
    {
        $c = curl_exec($this->_ch);
        if (!curl_errno($this->_ch)) {
            return $c;
        }  else{
            return curl_error($this->_ch);
        }
    }

    public function get($url, $data = array())
    {
        /**
         * get采用application/x-www-form-urlencoded格式发送
         */
        $this->setOption(CURLOPT_URL, $this->buildUrl($url, $data));
        $this->setConfigOptions();
        return $this->_execEx();
    }

    public function post($url, $data = array())
    {
        /**
         * php_curl对set的顺序有严重的要求
         */
        $this->setOption(CURLOPT_URL, $url);

        $this->setConfigOptions();

        $this->setOption(CURLOPT_POST, true);
        if (is_array($data) || is_object($data)) {       
            $data = http_build_query($data);  
        }       

        $this->setOption(CURLOPT_POSTFIELDS, $data);

        return $this->_execEx();
    }

    public function put($url, $data, $params = array())
    {
        $f = fopen('php://temp', 'rw+');
        fwrite($f, $data);
        rewind($f);

        $this->setOption(CURLOPT_PUT, true);
        $this->setOption(CURLOPT_INFILE, $f);
        $this->setOption(CURLOPT_INFILESIZE, strlen($data));

        return $this->_exec($this->buildUrl($url, $params));
    }

    public function checkResult($result)
    {
        $errCode = curl_errno($this->_ch);
        if (0 == $errCode)
        {
            return $result;
        }
        else
        {
            switch ($errCode)
            {
                case CURLE_OPERATION_TIMEOUTED:
                    {
                        if (stripos($result, 'connection') !== false)
                        {
                            $errCode = 102;
                            $msg = '连接服务器失败';
                        }
                        else
                        {
                            $errCode = 505;
                            $msg = '接收数据超时';
                        }
                    }
                    break;
                case CURLE_COULDNT_CONNECT:
                    $errCode = 102;
                    $msg = '连接服务器失败';
                    break;
                default:
                    $msg = '接收异常:'.$errCode;
                    $errCode = 504;
                    break;
            }

            throw new \Exception($msg,$errCode);
        }
    }

    public function buildUrl($url, $data = array())
    {
        $parsed = parse_url($url);
        isset($parsed['query']) ? parse_str($parsed['query'], $parsed['query']) : $parsed['query'] = array();
        $params = isset($parsed['query']) ? array_merge($parsed['query'], $data) : $data;
        $parsed['query'] = ($params) ? '?' . http_build_query($params) : '';
        if (!isset($parsed['path'])) {
            $parsed['path'] = '/';
        }
        
        $port = '';
        if (isset($parsed['port'])) {
            $port = ':' . $parsed['port'];
        }
        
        return $parsed['scheme'] . '://' . $parsed['host'] .$port. $parsed['path'] . $parsed['query'];
    }

    public function setOptions($options = array())
    {
        curl_setopt_array($this->_ch, $options);
        return $this;
    }

    public function setOption($option, $value) 
    {
        curl_setopt($this->_ch, $option, $value);
        return $this;
    }
    
    public function close()
    {
        curl_close($this->_ch);
    }
    
    public function getHttpCode()
    {
        return curl_getinfo($this->_ch, CURLINFO_HTTP_CODE);
    }
    
    public function getHttpInfo()
    {
        return curl_getinfo($this->_ch);
    }

    public function getErrorCode()
    {
        return curl_errno($this->_ch);
    }

    public function getErrorMessage()
    {
        return curl_error($this->_ch);
    }
}
