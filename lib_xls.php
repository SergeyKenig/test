<?php
class lib_xls{
    private static $Path = Cfg::XLS_PATH;
    private static $fName = "streem_xls_";
    private static $Ext = ".xls";
    private static $FilePath = "";
    public static $Sleep = 5;
    
    public static function filename($Name = ""){
        $Name = str_replace(self::$Ext, "", $Name);
        $Name = trim($Name);
        if(strlen($Name) > 0){
            self::$fName = lib_format::translit($Name) . self::$Ext;
        }
        for($i = 0; true; $i++){
            $FileName = self::$Path . self::$fName . $i . self::$Ext;
            if(!file_exists($FileName)){break;}
        }
        self::$FilePath = $FileName;
        return $FileName;
    }
    
    public static function create($Name = "", $Path = ""){
        sleep(self::$Sleep);
        if(strlen($Name) == 0){$Name = self::$fName . self::$Ext;}
        if(strlen($Path) == 0){$Path = self::$FilePath;}
        $Name = self::$fName;
        header('X-Sendfile: ' . $Path);
        header('Content-Type: application/octet-stream');
        header("Content-Type: application/vnd.ms-excel;");
        header('Content-Disposition: attachment; filename=' . $Name);
    }
    
    // Получаем индексы для xls файла по ключам массива
    public static function get_cells($List, $Index, $cIndex=1, $rIndex=1){
        $Cells = "";
        if($cIndex > 1){
            $Key = array_search($Index[0], $List);
            $cIndex--;
            $fIndex = implode("", $Index);
            $lIndex = isset($List[$Key + $cIndex]) ? $List[$Key + $cIndex] : $fIndex;
            $lIndex .= $Index[1];
            if($fIndex != $lIndex){
                $Cells = $fIndex . ":" . $lIndex;
            }
            
        }
        else if($rIndex > 1){
            $rIndex--;
            $fIndex = implode("", $Index);
            $lIndex = $Index[1] + $rIndex;
            $Cells = $fIndex . ":" . $Index[0] . $lIndex;
        }
        return $Cells;
    }
    
    // Получаем буквенные маркеры для большого файла xls
    public static function create_row_index_list(&$List){
        $xList = $List;
        $yList = $List;
        for($i = 0; $i < count($xList); $i++){
            for($j = 0; $j < count($yList); $j++){
                $List[] = $xList[$i] . $yList[$j];
            }
        }
        return true;
    }
    
    // Получаем координаты первой и последней ячейки будущего файла
    public static function period($List){
        next($List);
        $s1 = key($List);
        $s2 = key($List[$s1]);
        end($List);
        $k1 = key($List);
        end($List[$k1]);
        $k2 = key($List[$k1]);
        return $s2 . $s1 . ":" .$k2 . $k1; 
    }
}
?>