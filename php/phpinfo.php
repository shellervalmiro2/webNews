<?php
// test error log
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../log/error_log.log');

function giveError(){
    throw new Exception("custom error");
}

function handleError(){
    try{
        giveError();
    } catch(Exception $e){
        error_log($e->getMessage());
    }
}

handleError();
?>