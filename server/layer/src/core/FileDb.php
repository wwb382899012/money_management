<?php
/**
 * 文件映射关系存储
 */
class FileDb {
    private $db_connect;
    public function __construct(){
        $this->db_connect = DbUtil::getInstance()->getSqlHandler('base');
    }

    /**
     * 保存文件映射值
     */
    public function saveFile($group, $filename, $originname, $type){
        $data['path_name'] = $filename;
        $data['group_name'] = $group;
        $data['origin_name'] = $originname;
        $data['file_type'] = $type;
        $data['uuid'] = md5(uuid_create());
        $data['create_time'] = date('Y-m-d H:i:s');
        $values_key = '(`'.implode('`,`', array_keys($data)).'`)';
        $value_val = '("'.implode('","', array_values($data)).'")';
        $insertSql = "insert into m_sys_file $values_key values $value_val";
        $this->db_connect->exec($insertSql);
        return $data['uuid'];
    }

    public function getFile($uuid){
        $sql = "select * from m_sys_file where uuid='".$uuid."' and is_delete";
        $data = $this->db_connect->query($sql);
        if(empty($data)){
            return null;
        }
        return $data[0];
    }
}