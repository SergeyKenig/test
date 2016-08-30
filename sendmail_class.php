<?php
class Sendmail extends Page {
    public $MailType = "smtp";
    public $To;
    public $From;
    
    public function run($Args=''){
        if($this->MailType == 'mail'){
            return $this->run_mail($Args);
        }
        else{
            return $this->run_smtp($Args);
        }
    }
    
    private function run_smtp($Args=array()){
        Engine::log("Запускаю SMTP клиент");
        $Users = isset($Args['users']) ? $Args['users'] : array();
        $File = isset($Args['file']) ? $Args['file'] : "";
        $this->To = $this->getUsers($Users);
        
        $Mail = new smtp();
        $Mail->SmtpServer = isset($Args['smtp_server']) ? $Args['smtp_server'] : Cfg::SMTP_SERVER;
        $Mail->SmtpPort = isset($Args['smtp_port']) ? $Args['smtp_port'] : Cfg::SMTP_PORT;
        $Mail->SmtpAuth = isset($Args['smtp_auth']) ? $Args['smtp_auth'] : Cfg::SMTP_AUTH;
        $Mail->SmtpUser = isset($Args['smtp_user']) ? $Args['smtp_user'] : Cfg::SMTP_USER;
        $Mail->SmtpPassword = isset($Args['smtp_pass']) ? $Args['smtp_pass'] : Cfg::SMTP_PASS;
        $Mail->From = isset($Args['smtp_from']) ? $Args['smtp_from'] : Cfg::SMTP_FROM;
        $Mail->FromName = isset($Args['smtp_fromname']) ? $Args['smtp_fromname'] : Cfg::SMTP_NAME;

        $Names = array();
        $Mail->To = array();
        foreach($this->To AS $i => $Item){
            if(strlen($Item['email']) == 0){
                Engine::log("Sendmail: пустой e-mail для " . $Item['name']);
                continue;
            }
            if(!preg_match("/^[0-9A-Za-z-_.]+@[0-9a-z-_.]+[.]{1}[a-z]{2,4}$/", $Item['email'])){
                Engine::log("Sendmail: не корректный e-mail " . $Item['email'] . " для " . $Item['name']);
                continue;
            }
            $Mail->To[] = $Item['email'];
            $Names[] = $Item['name'];
        }

        $Mail->Subject = isset($Args['subject']) ? $Args['subject'] : "неміє теми";
        $Message = isset($Args['message']) ? $Args['message'] : "";
        
        if(count($Mail->To) == 1 && isset($Names[0]) && strlen($Names[0]) > 0){
            $Message = str_replace("USERNAME", "шановний(а) " . $Names[0], $Message);
        }
        else{
            $Message = str_replace("USERNAME", "шановний користувач", $Message);
        }
        
        $Message .= "<br /><br /><hr /><br />";
        $Message .= "Це повідомлення відправлено автоматично і не потребує відповіді. <br />";
        $Message .= "Дякуємо, що користуєтесь нашими послугами. <br /> <br />";
        $Message .= "З повагою,  <br />";
        $Message .= 'служба технічної підтримки';

        $Files = array();
        if(is_array($File)){
            foreach($File AS $Value){
                if(strlen($Value) > 0 && file_exists($Value)){
                    $Files[] = array($Value, $this->getAttachName($Value));
                }
            }
        }
        else{
            if(strlen($File) > 0 && file_exists($File)){
                $Files[] = array($File, $this->getAttachName($File));
            }
        }
        
        if(count($Files) > 0){
            $Mail->Attachment = TRUE;
            $Mail->Message = "";
            $Mail->ContentType = "multipart/mixed";
            $Mail->Boundary = "=-KiZOoO7h8RvXYQyj/+lu";
            
            $Mail->Message .= "--" . $Mail->Boundary . "\n";
            $Mail->Message .= 'Content-type: text/html; charset="'.$Mail->Charset.'"'  . "\n";
            $Mail->Message .= 'Content-Transfer-Encoding: binary' . "\n\n";
            $Mail->Message .= $Message . "\n\n";
            
            $Mail->Message .= "--" . $Mail->Boundary  . "\n";
            foreach($Files AS $i => $Item){
                $Filename = $Item[1];
                $f = fopen($Item[0], "r");
                $contentFile = fread($f, filesize($File));
                fclose($f);
                
                if($i > 0){
                    $Mail->Message .= "--" . $Mail->Boundary  . "\n";
                }
                $Mail->Message .= 'Content-Type: application/octet-stream; name="'.basename($Filename).'" ' . "\n";
                $Mail->Message .= 'Content-Transfer-Encoding: base64'  . "\n";
                $Mail->Message .= 'Content-Disposition: attachment' . "\n\n";
                $Mail->Message .= chunk_split(base64_encode($contentFile))  . "\n";
                
            }
            $Mail->Message .= "--" . $Mail->Boundary . "--";
        }
        else{
            $Mail->Attachment = FALSE;
            $Mail->Message = $Message;
        }
        
        $SendResult = count($Mail->To) > 0 ? $Mail->Send() : "Список получателей пуст";
        Engine::log("SMTP: " . $SendResult);
        return $SendResult;
    }
    
    private function run_mail($Args=array()){
        $SendResult = "Почта не отправлена. Используйте тип отправки smtp";
        Engine::log("MAIL: " . $SendResult);
        return $SendResult;
    }
    
    private function getUsers($Users){
        $Result = array();
        if(count($Users) == 0){return $Result;}
        $uList = implode(",", $Users);
        
        $Result = Engine::$DB->fetch_list("
        SELECT ud.*, u.lgn FROM user_data AS ud 
        LEFT JOIN users AS u ON(u.id=ud.id) 
        WHERE ud.id IN(".$uList.") 
        ");
        
        return $Result ? $Result : array();
    }
    
    private function getAttachName($Path, $Sepparator="/"){
        $tmp = explode($Sepparator, $Path);
        return $tmp[count($tmp) - 1];
    }

}
?>