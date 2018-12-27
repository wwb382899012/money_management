<?php
class XssFilter{
    private $data;
    public function __contructs($data=null){
        $this->data = $data;
    }

    public function result($data=null){
        if($data){
            $this->data = $data;
        }
        if(!$this->data){
            return '';
        }

        if (is_string($this->data)){
            if(!($array = json_decode($this->data, true))){
                return $this->xss($this->data);
            }
        }else if(is_array($this->data)){
            $array = $this->data;
        }else{
            return $this->data;
        }
        $array = $this->recuFilter($array);
        if(is_string($this->data)){
            return json_encode($array);                
        }else{
            return $array;
        }
      
    }

    /**
     * 递归过滤数据
     */
    protected function recuFilter(array $array){
        foreach ($array as $key => $value) {
            if(is_array($value)){
                $array[$key] = $this->recuFilter($value);
            }else{
                $array[$key] = $this->xss($value);
            }
        }
        return $array;
    }

    /**
     * xss字符处理
     */
    protected function xss($string){
        return htmlspecialchars(trim($string));
    }
}