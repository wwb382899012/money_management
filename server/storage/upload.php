<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/7/10
 * Time: 15:49
 */
require_once 'bootstrap.php';

use \money\model\SysFile;

use Qcloud\Cos\Client;
use Freyo\Flysystem\QcloudCOSv5\Adapter;
use League\Flysystem\Filesystem;

if (!empty($_FILES)) {
    try {
        $file = array_values($_FILES)[0];
        if ($file['error'] > 0) {
            output(__LINE__, '文件上传失败：'.$file['error']);
        }

        $originName = $file["name"];
        $fileType = $file["type"];
        $tmpFile = $file["tmp_name"];
        $remoteFilePath = date('Ymd'). DIRECTORY_SEPARATOR . uniqid() . '-' . $originName;
        $localFilePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . pathinfo($remoteFilePath, PATHINFO_BASENAME);
        $config = config('disks.cosv5');
        $bucket = $config['bucket'];

        //移动到临时目录
        if (!move_uploaded_file($tmpFile, $localFilePath)) {
            output(__LINE__, '文件移动失败');
        }

        $mSysFile = new SysFile();
        $uuid = $mSysFile->saveFile($bucket, $remoteFilePath, $originName, $fileType);
        if (!$uuid) {
            output(__LINE__, '文件上传失败');
        }
        $res = ['code' => ErrMsg::RET_CODE_SUCCESS, 'msg' =>  'success', 'data' => ['uuid' => $uuid]];
        echo json_encode($res, JSON_UNESCAPED_UNICODE);

        //结束客户端请求，上传文件到腾讯云
        fastcgi_finish_request();
        $client = new Client($config);
        $adapter = new Adapter($client, $config);
        $filesystem = new Filesystem($adapter);

        //判断bucket是否存在，若不存在则创建
        if (!$client->doesBucketExist($bucket)) {
            $client->createBucket(['Bucket' => $bucket]);
        }

        //上传文件到腾讯云
        $stream = fopen($localFilePath, 'r');
        if (!$filesystem->writeStream($remoteFilePath, $stream)) {
            throw new \Exception('文件上传到腾讯云失败');
        }
        is_resource($stream) && fclose($stream);
        is_file($localFilePath) && unlink($localFilePath);
    } catch (Exception $e) {
        \CommonLog::instance()->getDefaultLogger()->warn($e->getMessage());
        $mSysFile->del($uuid);
    }
} else {
    output(__LINE__, '非法请求');
}

