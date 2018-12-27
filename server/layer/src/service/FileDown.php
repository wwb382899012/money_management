<?php
/**
 * 会话数据获取
 */
use money\service\BaseService;
use money\model\SysFile;

class FileDown extends BaseService{

    protected $rule = [
        //'sessionToken' => 'require',
        'uuid' => 'require',
    ];

    private $response;

    public function access($params){
        $uuid = $params['uuid'];
        $fileDb = new SysFile();
        $data = $fileDb->getFile($uuid);
        if(!$data){
            $this->response->header("Content-Type", "text/html; charset=utf-8");           
            $this->response->end('文件不存在');
            return json_encode(['code'=>ErrMsg::RET_CODE_SERVICE_FAIL, 'msg'=>'文件不存在']);
        }
        $dir = "/tmp/moneyfile";
        if(!is_dir($dir)){
            mkdir($dir, 0777);
        }
        $fileUuidFlag = md5(uuid_create())."_";
        $local_filename = $dir.DIRECTORY_SEPARATOR.$fileUuidFlag.$data['origin_name'];

        FastdfsCore::factory()->downFile($data['group_name'], $data['path_name'], false, $local_filename);
        if(!$local_filename){
            $this->response->header("Content-Type", "text/html; charset=utf-8");           
            $this->response->end('文件不存在');
            return json_encode(['code'=>ErrMsg::RET_CODE_SERVICE_FAIL, 'msg'=>'文件不存在']);  
        }
        $imgTypes = ['image/jpeg','image/png','image/gif'];
        if(in_array($data['file_type'], $imgTypes)){
            $this->response->header("Content-Type", $data['file_type']);
        }else{
            $this->response->header('Content-Type', 'application/octet-stream');
            $this->response->header('Content-Disposition', 'attachment; filename*=UTF-8\'\''.rawurlencode($data['origin_name']));
        }
        $this->response->sendfile($local_filename);
    }

    /**
     * 回设request对象
     */
    public function setResponse($response){
        $this->response = $response;
        return true;
    }
}