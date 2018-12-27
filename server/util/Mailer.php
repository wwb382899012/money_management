<?php 
require_once 'class.phpmailer.php';
class Mailer
{

    public $smtp_host = "smtp.exmail.qq.com" ;
    public $smtp_authorized =  true ;
    public $smtp_username =  "fin@zzhicheng.com" ;
    public $smtp_password = "Joa!QAZ2wsx";
    public $smtp_port = 25 ;

    public $charset = 'utf-8';
    public $from = "fin@zzhicheng.com";
    public $from_name = "资金系统";

    /**
     * 发送邮件
     * @param array $toArray [['address'=>'邮件地址', 'name'=>'名称']]
     * @param string $title 邮件标题
     * @param $msg 邮件内容
     */
    function send($toArray, $title, $msg,  $fromArray=array())
    {
            $mail = new PHPMailer(); 
            $mail->IsSMTP(); 
            $mail->Host = $this->smtp_host ;  
            $mail->SMTPAuth =$this->smtp_authorized ; 
            $mail->Username = $this->smtp_username ; 
            $mail->Password = $this->smtp_password ; 
            $mail->Port=$this->smtp_port ; 
            $mail->CharSet=$this->charset ; 
            if(!empty($fromArray))
            {
                $mail->From = $fromArray['from']; 
                $mail->FromName = $fromArray['from_name'] ; 
            }else{
                $mail->From = $this->from ; 
                $mail->FromName = $this->from_name ; 
            }
            

            $mail->IsHTML(true); // set email format to HTML

            foreach($toArray as $to){
                $mail->AddAddress($to["address"], $to["name"]);                
            }
            $mail->Subject = $title;
            $mail->Body = $msg;
             
            if(!$mail->Send())
            {
                return false;
            }
            return true ;
    }
}
