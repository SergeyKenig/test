<?php
class lib_tables {
    // Возвращает массив с именами таблиц, которые содержат все поля из входного массива
    public static function get($FieldList = array()){
        $Tables = array();
        if(!is_array($FieldList)){return $Tables;}
        if(count($FieldList) == 0){return $Tables;}
        $s = mysql_query("SHOW TABLES");
        if(mysql_num_rows($s) == 0){return $Tables;}
        $tList = array();
        while($row = mysql_fetch_row($s)){
            $tList[] = $row[0];
        }
 
        $s = mysql_query("SHOW COLUMNS FROM " . $tText);
        while($row = mysql_fetch_row($s)){
            $cList[] = $row[0];
        }
        $cList = array();
        for($i = 0; $i < count($tList); $i++){
            $TBL = $tList[$i];
            $s = mysql_query("SHOW COLUMNS FROM " . $TBL);
            while($row = mysql_fetch_row($s)){
                $cList[$TBL][] = $row[0];
            }
            
        }

        $Count = count($FieldList);
        foreach($cList AS $TBL => $Fields){
            $Isset = 0;
            foreach($Fields AS $i => $Field){
                if(in_array($Field, $FieldList)){
                    $Isset++;
                }
                if($Isset >= $Count){break;}
            }
            if($Isset == $Count){
                $Tables[] = $TBL;
            }
        }
        
        return $Tables;
    }
    
    // Обновляет значения из ValList для таблиц, которые содержат все поля
    // из FieldList при соблюдении условия из WhereList
    public static function update($FieldList = array(), $ValList = array(), $WhereList = array()){
        if(!is_array($FieldList) || !is_array($ValList) || !is_array($WhereList)){return false;}
        if(count($FieldList) == 0 || count($ValList) == 0 || count($WhereList) == 0){return false;}
        $List = self::getTables($FieldList);
        if(count($List) == 0){return false;}
        
        foreach($List AS $i => $Table){
            $Query = "UPDATE " . $Table . " SET ";
            $i = 0;    
            foreach($ValList AS $Key => $Value){
                $Sepp = $i == 0 ? "" : " ,";
                $Query .= $Sepp . $Key . "='" . $Value . "'";
                $i++;
            }
            $Query .= " WHERE ";
            $i = 0;
            foreach($WhereList AS $Key => $Value){
                $Sepp = $i == 0 ? "" : " AND ";
                $Query .= $Sepp . $Key . "='" . $Value . "'";
                $i++;
            }
            $u = mysql_query($Query);
        }
        return true;
    }
}
?>