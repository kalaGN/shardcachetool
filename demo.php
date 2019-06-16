<?php
require_once './vendor/autoload.php';

$conf = [
    'aeskey' => '123456',//should config common key

    //common库配置
    'commondb' => array('host' => '127.0.0.1',
        'port' => '3306',
        'username' => 'root',
        'password' => 'pwd', //encrypted pwd
        'dbname' => 'common',
    ),

    //分库redis
    'redis' => array(
        'host' => '127.0.0.1',
        'port' => '6379',
        'password' => 'enc',//encrypted pwd
        'db' => '2',
        'timeout' => '2',)

];
$tool = new \Infobird\Tool\shardcache($conf);
$tool->run();
