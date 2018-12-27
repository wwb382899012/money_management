<?php
/**
*	文件存储类
*	@author sun
*	@since 2018-03-10
*/
namespace money\model;

class SysFile extends BaseModel
{
	protected $table = 'm_sys_file';

	public static function loadUuids($file_paths)
	{
	    $obj = new static();
		$annexes_array = explode(',',$file_paths);
        $annexes_uuid_array = array();
        foreach($annexes_array as $file_path)
        {
            $annexes_uuid_array[] = $obj->saveFile('', $file_path, $file_path,'');
        }
        return implode(',' , $annexes_uuid_array);
	}

	public static function changeUuidsToPath($obj , $from_key , $to_key)
	{
		if(!isset($obj[$from_key])){
			return $obj;
		}
		$id_array = explode(',' , $obj[$from_key]);
		if(count($id_array)==0){
			return $obj;
		}
		$key_array = array();
		$val_array = array();
		foreach($id_array as $key=>$id){
			$key_array[] = ':uuid_'.$key;
			$val_array['uuid_'.$key] = $id;
		}
        $static = new static();
		$ret = $static->getList(['uuid' => $id_array], 'uuid , origin_name, path_name , group_name');
		$datas = array();
		foreach($ret as $array)
		{
			$datas[$array['uuid']] = array(
				'uuid'=>$array['uuid'],
				'origin_name'=>$array['origin_name'],
				'path_name'=>$array['path_name'],
				'group_name'=>$array['group_name'],
			);
		}

		//排序
		$list = [];
		foreach ($id_array as $id) {
            $list[] = $datas[$id];
        }

		$obj[$to_key] = $list;
		return $obj;
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
        if(!$this->insert($data)){
        	throw new \Exception(\ErrMsg::RET_CODE_SERVICE_FAIL,'文件上传失败');
        }
        return $data['uuid'];
    }

    public function getFile($uuid){
        return $this->getOne(['uuid' => $uuid, 'is_delete' => 1]);
    }
}