<?php
/**
 * fastdfs 文件上传类
 */
class FastdfsCore {
    private static $instance;
    public static function factory(){
        if(!self::$instance){
            self::$instance = new self();
        }
        return self::$instance;
    }
    private $host;
    private $port;
    private $fdfs;
    private $tracker;
    private $server;
    private $error = [];
    private function __construct(){
    }

    /**
     * 上传文件
     */
    public function uploadFile($tmpFilePath, $ext){
        if(!$this->connection()){
            return false;
        }
        $file_info = $this->fdfs->storage_upload_by_filename($tmpFilePath, $ext);
        if(!$file_info){
            $this->last_error();
            return false;
        }
        return ['filename'=>$file_info['filename'], 'groupname'=>$file_info['group_name']];
    }

    /**
     * 文件下载
     */
    public function downFile($group, $filepath, $returnbuff=true, $localFile=''){
        if(!$this->connection()){
            return false;
        }
        if($returnbuff){
            $buffer = $this->fdfs->storage_download_file_to_buff($group, $filepath);
            if(!$buffer){
                $this->last_error();
            }else{
                return $buffer;
            }
        }else if(!$returnbuff && $localFile){
            $result = $this->fdfs->storage_download_file_to_file($group, $filepath, $localFile);
            if(!$result){
                $this->last_error();
            }else{
                return $localFile;                
            }
        }
        return false;
    }

    public function getLastError(){
        return $this->error;
    }

    /**
     * 连接
     */
    protected function connection(){
        if(!$this->fdfs){
            $this->fdfs = new FastDFS();
            $this->tracker = $this->fdfs->tracker_get_connection();
            if(!$this->tracker){
                $this->last_error();
                return false;
            }
            $this->server = $this->fdfs->connect_server($this->tracker['ip_addr'], $this->tracker['port']);
            if(!$this->server){
                $this->last_error();
                return false;
            }
        }
        return true;
    }
    
    /**
     * 断开连接
     */
    protected function dis_connection(){
    	
    }

    /**
     * 记录错误
     */
    protected function last_error(){
        $this->error = ['code' => $this->fdfs->get_last_error_no(), 'msg' => $this->fdfs->get_last_error_info()];
        CommonLog::instance()->getDefaultLogger()->info(sprintf("%s|%s",$this->fdfs->get_last_error_info(), $this->fdfs->get_last_error_no()));
    }
}