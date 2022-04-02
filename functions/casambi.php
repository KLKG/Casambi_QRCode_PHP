<?php
    function buildcasambiString($code_type, $lithernet_id, $target_type, $target_id, $level, $red, $green, $blue, $white, $tc){
        switch ($code_type) {
            case 0: //Empty
                $result = "";
                break;
            case 1: //Level
                $result = $lithernet_id."#114#5#32#".$level."#3#".$target_type."#".$target_id."\r\n";
                break;
            case 2: //Tc
                $result = $lithernet_id."#114#5#72#$tc#3#".$target_type."#".$target_id."\r\n";
                break;
            case 3: // RGBWAF
                $result = $lithernet_id."#114#8#47#".$red."#".$green."#".$blue."#".$white."#".$target_type."#".$target_id."#".$level."\r\n";
                break;
        }
        return $result;
    }
?>