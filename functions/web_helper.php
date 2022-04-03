<?php
    function cleanSite($input){
        $clean = strtolower($input);
        $clean = preg_replace('/[^a-z]+/', '', $clean); 
        $clean = substr($clean, 0, 10);
        return $clean;
    }

    function cleanCode($input){
        $clean = strtolower($input);
        $clean = preg_replace('/[^a-z0-9]+/', '', $clean); 
        $clean = substr($clean, 0, 10);
        return $clean;
    }

    function cleanNumber($input){
        $clean = strtolower($input);
        $clean = preg_replace('/[^0-9]+/', '', $clean); 
        $clean = substr($clean, 0, 3);
        return $clean;
    }

    function cleanUserPass($input){
        $clean = strtolower($input);
        $clean = preg_replace('/[^a-z0-9]+/', '', $clean); 
        $clean = substr($clean, 0, 10);
        return $clean;
    }
?>