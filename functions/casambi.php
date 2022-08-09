<?php
    function buildcasambiString($code_type, $lithernet_id, $target_type, $target_id, $level, $red, $green, $blue, $white, $tc, $scene){
        switch ($code_type) {
            case 0: //Empty
                $result = "";
                break;
            case 1: //Level
                $result = $lithernet_id."#114#5#32#".$level."#3#".$target_type."#".$target_id."\r\n";
                break;
            case 2: //Tc
                $result = $lithernet_id."#114#5#72#$tc#23#".$target_type."#".$target_id."\r\n";
                break;
            case 3: // RGBWAF
                $result = $lithernet_id."#114#8#47#".$red."#".$green."#".$blue."#".$white."#".$target_type."#".$target_id."#255\r\n";
                break;
            case 4: // Scene
                $result = $lithernet_id."#114#4#30#".$scene."#".$level."#3\r\n";
                break;
        }
        return $result;
    }
?>