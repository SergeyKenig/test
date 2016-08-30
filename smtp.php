<?php
class smtp{
    public $HttpHost;
    public $SmtpServer;
    public $SmtpPort;
    public $SmtpAuth;
    public $SmtpUser;
    public $SmtpPassword;
    public $StartTLS;
    public $Timeout;

    public $From;
    public $FromName;
    public $To;
    public $Subject;
    public $Message;
    public $Attachment;
    
    public $Protocol;

    public $Mime;
    public $ContentType;
    public $Charset;
    public $Boundary;
    
    private $Headers;
    

    public function __construct(){
        $this->SmtpServer = "";
        $this->SmtpPort = 465;
        $this->SmtpAuth = FALSE;
        $this->SmtpUser = "";
        $this->SmtpPassword = "";
        $this->StartTLS = FALSE;
        $this->Timeout = 30;

        $this->From = "";
        $this->FromName = "";
        $this->To = "";
        $this->Subject = "";
        $this->Message = "";
        $this->Attachment = FALSE;

        $this->Protocol = "tls://";

        $this->Mime = "1.0";
        $this->ContentType = "text/html";
        $this->Charset = "utf-8";
        $this->HttpHost = $_SERVER["HTTP_HOST"];
        $this->Boundary = "=-KiZOoO7h8RvXYQyj/+lu";  
    }

    public function CreateHeader(){
        if(strlen($this->Boundary) == 0){$this->Boundary = "=-KiZOoO7h8RvXYQyj/+lu";}
        $headers = "";
        $headers .= 'MIME-Version: ' . $this->Mime . "\n"; 
        if($this->Attachment){
            $headers .= 'Content-type: ' . $this->ContentType . '; boundary="'.$this->Boundary.'"' . "\n";
        }
        else{
            $headers .= 'Content-type: ' . $this->ContentType . '; charset=' . $this->Charset . '' . "\n";
        }
        $headers .= 'Content-Transfer-Encoding: binary' . "\n";
        $headers .= 'X-Mailer: ICE-mail-client ver. 1.0' . "\n";

        $this->Headers = $headers;
    }


    public function Send(){
        $this->CreateHeader();
        
        // Open an SMTP connection
        $cp = fsockopen ($this->Protocol . $this->SmtpServer, $this->SmtpPort, $errno, $errstr, $this->Timeout);
        if (!$cp) return "Не могу открыть соединение";

        $res = fread($cp, 256);

        if(substr($res, 0, 3) != "220") return "Сервер не отвечает";

        // Say hello...
        fputs($cp, "EHLO " . $this->HttpHost . "\r\n");
        $res = fread($cp, 256);
        if(substr($res, 0, 3) != "250") return "Ошибка Хело";  

        if($this->SmtpAuth){
            if($this->StartTLS){
                // TLS 
                fputs($cp, "STARTTLS\r\n");
                $res = fread($cp, 256);
                //if(substr($res,0,3) != "220") return "Failed to Initiate Authentication"; 
            }

            // perform authentication
            fputs($cp, "AUTH LOGIN\r\n");
            $res = fread($cp, 256);
            if(substr($res, 0, 3) != "334") return "Авторизация не доступна";

            fputs($cp, base64_encode($this->SmtpUser)."\r\n");
            $res = fread($cp, 256);
            if(substr($res, 0, 3) != "334") return "Не верное имя пользователя";
 
            fputs($cp, base64_encode($this->SmtpPassword)."\r\n");
            $res = fread($cp, 256);
            if(substr($res, 0, 3) != "235") return "Не верный пароль";
        }

        // Mail from...
        fputs($cp, "MAIL FROM: <".$this->From.">\r\n");
        $res = fread($cp, 256);
        if(substr($res, 0, 3) != "250") return "Ошибка MAIL FROM";

        // Rcpt to...
        if(is_array($this->To)){
            foreach($this->To AS $User){
                fputs($cp, "RCPT TO: <".$User.">\r\n");
                $res = fread($cp,256);
                if(substr($res,0,3) != "250") return "Ошибка RCPT TO";
            }
        }
        else{
            fputs($cp, "RCPT TO: <".$this->To.">\r\n");
            $res = fread($cp,256);
            if(substr($res,0,3) != "250") return "Ошибка RCPT TO";
        }
        // Data...
        fputs($cp, "DATA\r\n");
        $res = fread($cp, 256);
        if(substr($res, 0, 3) != "354") return "Ошибка DATA";

        if(strlen($this->FromName) > 0){
            $this->From = $this->FromName . "<" . $this->From . ">";
        }
        fputs($cp, "To: ".$this->From."\nFrom: ".$this->From."\nSubject: ".$this->Subject."\n".$this->Headers."\n\n".$this->Message."\n.\n");
        $res = fread($cp, 256);
        if(substr($res, 0, 3) != "250") return "Не верное тело сообщения";
        
        // ...And time to quit...
        fputs($cp, "QUIT\r\n");
        $res = fread($cp, 256);
        if(substr($res, 0, 3) != "221") return "Соединение не завершено или завершено с ошибками";

        return "SENDED";
    }
}
?>