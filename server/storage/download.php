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

if (!empty($_GET['uuid'])) {
    try {
        $uuid = $_GET['uuid'];
        $mSysFile = new SysFile();
        $data = $mSysFile->getOne(['uuid' => $uuid, 'is_delete' => SysFile::DEL_STATUS_NORMAL]);
        if (empty($data)) {
            output(__LINE__, '文件不存在');
        }

        $originName = $data['origin_name'];
        $bucket = $data['group_name'];
        $remoteFilePath = $data['path_name'];
        $localFilePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . pathinfo($remoteFilePath, PATHINFO_BASENAME);

        //本地临时文件不存在，则从腾讯云下载
        if (!is_file($localFilePath)) {
            $config = config('disks.cosv5');
            $client = new Client($config);
            $adapter = new Adapter($client, $config);
            $filesystem = new Filesystem($adapter);
            $stream = $filesystem->readStream($remoteFilePath);
            file_put_contents($localFilePath, $stream);
            is_resource($stream) && fclose($stream);
            if (!is_file($localFilePath)) {
                output(__LINE__, '文件传输失败');
            }
        }

        $imgTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (in_array($data['file_type'], $imgTypes)) {
            header("Content-Type: {$data['file_type']}");
            readfile($localFilePath);
        } else {
            $obj = new FileDownload();
            $obj->download($localFilePath, $originName, true);
        }
    } catch (Exception $e) {
        \CommonLog::instance()->getDefaultLogger()->warn($e->getMessage());
        output(__LINE__, $e->getMessage());
    }
} else {
    output(__LINE__, '非法请求');
}
