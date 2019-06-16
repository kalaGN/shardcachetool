<?php
require_once './vendor/autoload.php';

use Infobird\Tool\Phpaes;
$aes = new Phpaes('123456');
var_dump($aes->encrypt('123456'));
var_dump($aes->decrypt('61tTXg2T3zc9AX96+6A33g=='));
$conf = [
    'aeskey' => '123456',//

    //common库配置
    'commondb' => array('host' => '127.0.0.1',
        'port' => '3306',
        'username' => 'root',
        'password' => 'GdKIL/Z9IAENoxJncwAdPQ==', //加密
        'dbname' => 'common',
    ),

    //分库redis
    'redis' => array(
        'host' => '127.0.0.1',
        'port' => '6379',
        'password' => 'ha/1OAx+rKmoWplpz1qZYw==',
        'db' => '2',
        'timeout' => '2',)

];
$tool = new \Infobird\Tool\shardcache($conf);
$tool->run();
