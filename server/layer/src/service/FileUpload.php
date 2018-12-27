<?php
/**
 * 会话数据获取
 */
use money\service\BaseService;
use money\model\SysFile;

class FileUpload extends BaseService{
    private $request;

    public function exec(){
        $file = $this->request->files;
        $file = array_values($file);
        $ext = substr($file[0]['name'], strrpos($file[0]['name'], ".")+1);
        $fileinfo = FastdfsCore::factory()->uploadFile($file[0]['tmp_name'], $ext);
        if(!$fileinfo){
            $msg = FastdfsCore::factory()->getLastError()['msg'] ?? '未知错误';
            throw new \Exception('文件上传失败：'.$msg, ErrMsg::RET_CODE_SERVICE_FAIL);
        }
        $fileDb = new SysFile();
        $uuid = $fileDb->saveFile($fileinfo['groupname'], $fileinfo['filename'], $file[0]['name'], $file[0]['type']);
        $this->packRet(ErrMsg::RET_CODE_SUCCESS,  ['uuid'=>$uuid]);
    }

    /**
     * 回设request对象
     */
    public function setRequest($request){
        $this->request = $request;
    }
}