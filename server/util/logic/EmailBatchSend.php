<?php
/**
 * 异步邮件批量发送
 */

namespace money\logic;

use money\model\SysMailNews;

class EmailBatchSend{
    public function start(){
        $db = new SysMailNews();
        $mail = new \Mailer();
        $data = $db->mailList(1, 100, ['news_status'=>1]);
        foreach($data['data'] as $row){
            if(!$row['send_datetime'] || strtotime($row['send_datetime']) > time() || empty($row['email_address'])){
                continue;
            }
            $toArray = ['address'=>$row['email_address'],'name'=>$row['deal_user_name']];
            $rows = $db->updateStatus($row['uuid'], 2);
            if($rows == 0){
                continue;
            }

            if(!$mail->send([$toArray], $row['title'], $row['content'])){
                $db->updateStatus($row['uuid'], 1);
            }
        }
    }
}