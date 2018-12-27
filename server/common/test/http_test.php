<?php
/**
 * Created by PhpStorm.
 * User: lewis.qing
 * Date: 2017/7/27
 * Time: 11:00
 */

require_once (dirname(__DIR__).DIRECTORY_SEPARATOR.'index.php');

$http = new HttpRequestHandler();
$http->init();
$http->timeout(1, $this->ioTimeout);
$params = array('tel'=>'15815524090');
$ret = $http->post("127.0.0.1", $params);
unset($http);