<?php
class TaskManager extends Page {
    
    public function run($Args = ''){
        language::set_data();
        lib_auth::check_access();
        $User = lib_auth::get_user();
        
        if(Engine::$Action == 'list'){
            return $this->run_list($Args);
        }
        else if(Engine::$Action == 'one'){
            return $this->run_one($Args);
        }
        else if(Engine::$Action == 'ulist'){
            return $this->run_addlist($Args);
        }
        else if(Engine::$Action == 'delete'){
            return $this->run_delete($Args);
        }
        else if(Engine::$Action == 'update'){
            return $this->run_update($Args);
        }
        else if(Engine::$Action == 'addtask'){
            return $this->run_add($Args);
        }
        else{
            return $this->run_index($Args);
        }
    }
    
    private function run_index($Args = array()){
        $Out = "";
        $error = "";
        $info = "";
        $User = lib_auth::get_user();
        $logged_in = isset($User['user']) && isset($User['type']) ? TRUE : FALSE;
        
        // Create module
        Template::create("taskmanager.tpl");
        
        $Tasks = $this->run_list(array("send_type" => "list"));
        $TypeList = mod_taskmanager_cfg::$Types;
        $StatusList = mod_taskmanager_cfg::$Status;
        $SortList = mod_taskmanager_cfg::$Sort;

        Template::set("TaskList", $Tasks);
        Template::set("TypeList", $TypeList);
        Template::set("StatusList", $StatusList);
        Template::set("SortList", $SortList);
        Template::set("User", $User);
        
        Template::set("Languages", language::get_menu());
        Template::set("Locale", language::get_list());
        Template::set("logged_in", $logged_in);
        Template::set("type", $User['type']);
        Template::set("error", $error);
        Template::set("info", $info);
        $Out = Template::parse();
        
        Template::create("default.tpl");
        Template::set("global_error", $error);
        Template::set("type", $User['type']);
        Template::set("title", $User['user'] . " - " . Cfg::APPNAME);

        $GlobalIncluded = "";
        $GlobalIncluded .= '<link href="/www/themes/new/style/jquery-ui-datapiker-1.10.3.css" media="screen" rel="stylesheet" type="text/css" />';
        $GlobalIncluded .= '<link href="/www/themes/new/style/grid.css" media="screen" rel="stylesheet" type="text/css" />';
        $GlobalIncluded .= '<script type="text/javascript" src="/www/themes/new/scripts/jquery.timers.js"></script>';
        $GlobalIncluded .= '<script type="text/javascript" src="/www/themes/new/scripts/jquery-ui-datapiker-1.10.3.custom.js"></script>';
        $GlobalIncluded .= "<link rel=\"stylesheet\" href=\"/" . Cfg::DIR_THEMES . Cfg::THEME . "/style/" . Engine::$Section . "/style.css?".rand()."\" type=\"text/css\" media=\"all\" />";
        $GlobalIncluded .= "<script language='javascript' src='/" . Cfg::DIR_THEMES . Cfg::THEME . "/" . Cfg::DIR_JS . Engine::$Section . "/taskmanager.js?".rand()."'></script>";

        Template::set("global_included", $GlobalIncluded);
        Template::set("content", $Out);
        return Template::parse();
    }
    
    private function run_list($Args = array()){
        $Type = Engine::$Req->post("none", "type", (isset($Args['type']) ? $Args['type'] : ""));
        $Status = Engine::$Req->post("none", "status", (isset($Args['status']) ? $Args['status'] : ""));
        $Sort = Engine::$Req->post("none", "sort", (isset($Args['sort']) ? $Args['sort'] : "byid"));
        $sType = Engine::$Req->post("none", "send_type", (isset($Args['send_type']) ? $Args['send_type'] : "ajax"));
        $rType = Engine::$Req->post("none", "result_type", (isset($Args['result_type']) ? $Args['result_type'] : "all"));
        $IDs = Engine::$Req->post("none", "ids", (isset($Args['ids']) ? $Args['ids'] : ""));
        
        $TypeList = mod_taskmanager_cfg::$Types;
        $StatusList = mod_taskmanager_cfg::$Status;
        
        $Result = array();
        
        $TypeQuery = strlen($Type) > 0 && isset($TypeList[$Type]) ? " AND type='".$Type."' " : "";
        $StatusQuery = strlen($Status) > 0 && isset($StatusList[$Status]) ? " AND status='".$Status."' " : "";
        
        $IDsQuery = "";
        if(is_array($IDs)){
            $IDsQuery = count($IDs) > 0 ? " AND id IN(".implode(",", $IDs).") " : "";
        }
        else{
            $IDsList = strlen($IDs) > 0 ? explode(":", $IDs) : array();
            $IDsQuery = count($IDsList) > 0 ? " AND id IN(".implode(",", $IDsList).") " : "";
        }
        
        $SortQuery = " ORDER BY id";
        if($Sort == 'bydate'){
            $SortQuery = " ORDER BY date";
        }
        
        if($rType == 'id'){
            $Fields = "id";
        }
        else if($rType == 'select'){
            $Fields = "id, CONCAT('Завдання #', id) AS name";
        }
        else{
            $Fields = "*, CONCAT('Завдання #', id) AS name, DATE_FORMAT(date_start, '%d.%m.%Y %H:%m:%s') AS sdate, DATE_FORMAT(date_end, '%d.%m.%Y %H:%m:%s') AS edate, FROM_UNIXTIME(date, '%d.%m.%Y') AS fdate ";
        }
        $Query = "SELECT " . $Fields;
        $Query .= " FROM cronjobs ";
        $Query .= " WHERE 1=1 " . $TypeQuery . $StatusQuery . $IDsQuery;
        $Query .= $SortQuery;
        
        if($rType == 'id'){
            $LST = Engine::$DB->fetch_list($Query, "id", "name");
            $Result = array_keys($LST);
        }
        else if($rType == 'select'){
            $Result = Engine::$DB->fetch_list($Query, "id", "name");
        }
        else{
            $Result = Engine::$DB->fetch_list($Query);
            foreach($Result AS $Key => $Item){
                $Item['values'] = mod_taskmanager_cfg::valuesDecode($Item['values']);
                $Result[$Key] = $Item;
            }
        }

        if($sType == 'list'){return $Result;}
        
        header ('Content-type: application/json; charset: utf-8');
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
        header("Last-Modified: " . gmdate( "D, d M Y H:i:s") . " GMT");
        header("Cache-Control: no-cache, must-revalidate");
        header("Pragma: no-cache");
        
        echo json_encode($Result);
        exit();
    }
    
    private function run_one($Args = array()){
        $ID = Engine::$Req->post("none", "id", (isset($Args['id']) ? $Args['id'] : 0));
        $sType = Engine::$Req->post("none", "send_type", (isset($Args['send_type']) ? $Args['send_type'] : "ajax"));
        
        $Result = array();
        
        if($ID > 0){
            $Fields = "*, CONCAT('Завдання #', id) AS name, DATE_FORMAT(date_start, '%d.%m.%Y %H:%m:%s') AS sdate, DATE_FORMAT(date_end, '%d.%m.%Y %H:%m:%s') AS edate, FROM_UNIXTIME(date, '%d.%m.%Y') AS fdate ";
            $Query = "SELECT " . $Fields;
            $Query .= " FROM cronjobs ";
            $Query .= " WHERE id='".$ID."' ";

            $Result = Engine::$DB->fetch_item($Query);
            $Result['values'] = mod_taskmanager_cfg::valuesDecode($Result['values']);
        }
 
        if($sType == 'list'){return $Result;}
        
        header ('Content-type: application/json; charset: utf-8');
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
        header("Last-Modified: " . gmdate( "D, d M Y H:i:s") . " GMT");
        header("Cache-Control: no-cache, must-revalidate");
        header("Pragma: no-cache");
        
        echo json_encode($Result);
        exit();
    }
    
    private function run_update($Args = array()){
        $Result = array(
            "status" => "ERROR", 
            "err" => "", 
            "val" => ""
        );
        
        $ID = Engine::$Req->post("none", "id", (isset($Args['id']) ? $Args['id'] : 0));
        $Type = Engine::$Req->post("none", "type", (isset($Args['type']) ? $Args['type'] : "one"));
        $Value = Engine::$Req->post("none", "value", (isset($Args['value']) ? $Args['value'] : ""));
        
        if($ID > 0){
            if($Type == 'date'){
                $Date = lib_date::toTimestamp($Value);
                if($Date > 0){
                    Engine::$DB->query("UPDATE cronjobs SET date='".$Date."' WHERE id='".$ID."' ");
                    $Result['status'] = "OK";
                    $Result['val'] = $Value;
                }
                else{
                    $Result['err'] = "Дату не визначено";
                }
            }
            else if($Type == 'type'){
                Engine::$DB->query("UPDATE cronjobs SET type='".$Value."' WHERE id='".$ID."' ");
                $Result['status'] = "OK";
                $Result['val'] = $Value;
            }
            else if($Type == 'status'){
                if($Value == 'EXECUTE'){
                    $Item = Engine::$DB->fetch_item("SELECT status FROM cronjobs WHERE id='".$ID."' ");
                    $Result['status'] = "OK";
                    $Result['val'] = $Item['status'];
                    $Result['err'] = "Не можна призначити завданню такий статус";
                }
                else if($Value == 'END'){
                    Engine::$DB->query("UPDATE cronjobs SET status='".$Value."', date_start=NOW(), date_end=NOW(), errors='Зупинено адміністратором' WHERE id='".$ID."' ");
                    $Result['status'] = "OK";
                    $Result['val'] = $Value;
                }
                else if($Value == 'NEW'){
                    Engine::$DB->query("UPDATE cronjobs SET status='".$Value."', date_start='0000-00-00 00:00:00', date_end='0000-00-00 00:00:00', errors='' WHERE id='".$ID."' ");
                    $Result['status'] = "OK";
                    $Result['val'] = $Value;
                }
                else{
                    Engine::$DB->query("UPDATE cronjobs SET status='".$Value."' WHERE id='".$ID."' ");
                    $Result['status'] = "OK";
                    $Result['val'] = $Value;
                }
            }
            else{
                $Result['err'] = "Параметр не знайдено";
            }           
        }
        else{
            $Result['err'] = "Завдання не знайдено";
        }
        
        header ('Content-type: application/json; charset: utf-8');
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
        header("Last-Modified: " . gmdate( "D, d M Y H:i:s") . " GMT");
        header("Cache-Control: no-cache, must-revalidate");
        header("Pragma: no-cache");
        
        echo json_encode($Result);
        exit();
    }
    
    private function run_delete($Args = array()){
        $Result = array(
            "status" => "ERROR", 
            "err" => "", 
            "val" => ""
        );
        
        $ID = Engine::$Req->post("none", "id", (isset($Args['id']) ? $Args['id'] : 0));
        
        if($ID > 0){
            Engine::$DB->query("DELETE FROM cronjobs WHERE id='".$ID."' ");
            $Result['status'] = "OK";
            $Result['err'] = "Завдання видалено";
        }
        else{
            $Result['err'] = "Завдання не знайдено";
        }
        
        header ('Content-type: application/json; charset: utf-8');
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
        header("Last-Modified: " . gmdate( "D, d M Y H:i:s") . " GMT");
        header("Cache-Control: no-cache, must-revalidate");
        header("Pragma: no-cache");
        
        echo json_encode($Result);
        exit();
    }
    
    private function run_addlist($Args=array()){
        $Result = array();
        
        $IDs = Engine::$Req->post("none", "ids", (isset($Args['ids']) ? $Args['ids'] : ""));
        
        $Args = array(
            "result_type" => "list",
            "action" => "users",
            "update" => 1, 
            "fld" => "city", 
            "ids" => $IDs
        );

        $Obj = new Listing();
        $Result = $Obj->run($Args);
        
        header ('Content-type: application/json; charset: utf-8');
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
        header("Last-Modified: " . gmdate( "D, d M Y H:i:s") . " GMT");
        header("Cache-Control: no-cache, must-revalidate");
        header("Pragma: no-cache");
        
        echo json_encode($Result);
        exit();
    }
    
    private function run_add($Args=array()){
        $Result = array(
            "status" => "ERROR", 
            "err" => ""
        );
        
        $User = lib_auth::get_user();
        
        $Users = Engine::$Req->post("none", "users", (isset($Args['users']) ? $Args['users'] : array()));
        $Type = Engine::$Req->post("none", "sendtype", (isset($Args['sendtype']) ? $Args['sendtype'] : 'MAIL'));
        $Date = Engine::$Req->post("none", "send_date", (isset($Args['send_date']) ? $Args['send_date'] : date("d.m.Y")));
        $Theme = Engine::$Req->post("none", "theme", (isset($Args['theme']) ? $Args['theme'] : ''));
        $Message = Engine::$Req->post("none", "mess", (isset($Args['mess']) ? $Args['mess'] : ''));
        
        $Date = lib_date::toTimestamp($Date);
        
        if($User['type'] == 'admin'){
        
            $FormData = array();

            $FormData['users'] = $Users;
            $FormData['subject'] = $Theme;
            $FormData['message'] = $Message;
            $FormData['send_date'] = $Date;

            $Data = serialize($FormData);
            $IDD = md5($Data);
            $Insert = array(
                "status" => "NEW", 
                "type" => "MAIL", 
                "date" => $Date, 
                "module" => "Sendmail", 
                "method" => "run", 
                "values" => $Data,
                "idd" => $IDD
            );
            
            Engine::$DB->insert("cronjobs", $Insert);
            $Result['status'] = "OK";
            $Result['err']  = "Нове завдання додано успішно";
        }
        else{
            $Result['err']  = "Операція заборонена";
        }
        
        header ('Content-type: application/json; charset: utf-8');
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
        header("Last-Modified: " . gmdate( "D, d M Y H:i:s") . " GMT");
        header("Cache-Control: no-cache, must-revalidate");
        header("Pragma: no-cache");
        
        echo json_encode($Result);
        exit();
    }
}
?>