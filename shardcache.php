<?php
/**
 *
 * 此文件用于分库分表配置redis 缓存迁移
 *
 *
 * Created by Afei.
 * User: infobird
 * Date: 2019/02/25/
 * Time: 16:04:29
 */
error_reporting(E_ALL);
date_default_timezone_set("PRC");

require_once "CacheInfo.php";
require_once "Phpaes.php";
require_once "ConnectMysqli.php";
$aes = new Phpaes();

//连接 AA029 redis信息 密码密文
$FROM_IP = '118.178.122.81';
$FROM_PORT = 9001;
$FROM_DB = 3;
$FROM_PWD = 'pOyuElQLeLNVQM+xeCJlsfK39A47/FbNqW1UrlgypAI=';//

//common库配置
$host='119.3.63.186';
$port="36595";
$username='infobird';
$password="A75s1oCgFA5bCpVANkfKCQ==";
$db='common';

$mysqllink = new ConnectMysqli(array(
    'host'=> $host,
    'port'=>$port,
    'user'=>$username,
    'pass'=>$aes->decrypt($password),
    'db'=>$db,
    'charset'=>'utf8'
));
//==============================================================================================================

//var_dump($_SERVER['argv']);
if (count($_SERVER['argv']) != 2) {
    echo 'argv error.';
    exit;
}
$ar = $_SERVER['argv'];
$nowtime = date('Y-m-d H:i:s');
echo "start at :" . $nowtime . "     ";

$toredis = new redis();
$toredis->connect($FROM_IP, $FROM_PORT);
if ($FROM_PWD) {
   $re= $toredis->auth($aes->decrypt($FROM_PWD));
}
$toredis->select($FROM_DB);
if (empty($toredis)) {
    echo "from redis connnect error!";
    exit;
}


// 1 取出资源信息
$query = "select * from com_storage_instance_define left JOIN com_servers_pool on com_storage_instance_define.server_identity=com_servers_pool.server_identity";
$result = $mysqllink->getAll($query);
file_put_contents('resource_sync.txt', $truekey . "\r\n" . "data:" . json_encode($result) . "\r\n", FILE_APPEND);
$filter =array('server_ip','server_password','server_port','server_user','instance_db','instance_character','instance_connect_timeout','is_stop');
foreach ($result as $rk => $rv){
    $data = array();
    foreach ($filter as $v){
        if (isset($rv[$v])){
            $data[$v]=$rv[$v];
        }
    }
    if ($rv['sys_category']=='nosql'){
        $rv['biz_type']='redis';
    }elseif($rv['sys_category']=='rdb'){
        $rv['biz_type']='mysql';
    }elseif($rv['sys_category']=='api'){
        if ($rv['biz_type']=='1'||$rv['biz_type']=='0'){
            $rv['biz_type']='http';
        }
        if ($rv['biz_type']=='3'){
            $rv['biz_type']='soap';
        }
    }
    $data['sys']=$rv['sys_category'];
    $data['biz']=$rv['biz_type'];
    $cache = new CacheInfo($toredis,$data);
    $cache->setInstance($rv['instance_identity'],$data);
}


//2 取出预设 自定义 分库配置分库
$query = "select * from com_storage_shard_config";
$shardconfigresult = $mysqllink->getAll($query);
//var_dump($shardconfigresult);
file_put_contents('sharddb_sync.txt', $truekey . "\r\n" . "data:" . json_encode($shardconfigresult) . "\r\n", FILE_APPEND);
$filter =array('shard_identity','shard_entity','storage_identity_cluster','shard_sys_strategy','shard_fields','enterprise_id','shard_name');
if (!empty($shardconfigresult)){
    foreach ($shardconfigresult as $rk => $rv){
        $data = array();
        foreach ($filter as $v){
            if (isset($rv[$v])){
                $data[$v]=$rv[$v];
            }
        }
        $data['enterprise_id']=$rv['enterprise_id'];
        $data['db']=$rv['shard_entity'];
        $cache = new CacheInfo($toredis,$data);
        //预设 自定义分库 写入
        $cache->setShardDbConfig($rv['shard_entity'],$data);
        if ($data['enterprise_id']>0){
            //设置了分库的企业 打开分库开关
            $cache->setShardDbSwitch($data['enterprise_id'],1);
        }
    }
}


//3 取出预设 自定义 分表
$query = "select com_storage_table_shard_config.*,com_storage_shard_config.shard_entity as db from com_storage_table_shard_config left JOIN com_storage_shard_config on com_storage_table_shard_config.db_shard_identity=com_storage_shard_config.shard_identity";
$shardtableconfigresult = $mysqllink->getAll($query);
//var_dump($shardtableconfigresult);
file_put_contents('shardtable_sync.txt', $truekey . "\r\n" . "data:" . json_encode($shardtableconfigresult) . "\r\n", FILE_APPEND);
$filter =array('default_ddl_file','shard_biz_strategy','shard_sys_strategy','shard_entity','db','shard_fields');
if (!empty($shardtableconfigresult)){
    foreach ($shardtableconfigresult as $rk => $rv){
        $data = array();
        foreach ($filter as $v){
            if (isset($rv[$v])){
                $data[$v]=$rv[$v];
            }
        }
        $data['enterprise_id']=$rv['enterprise_id'];
        $data['db']=$rv['db'];
        $data['table_entity'] = $rv['shard_entity'];
        $cache = new CacheInfo($toredis,$data);

        //预设 自定义分库 写入
        $cache->setShardTableConfig($rv['shard_entity'],$data);
        if ($data['enterprise_id']>0){
            //设置了分表的企业 打开分库开关
            $cache->setShardTableSwitch($data['enterprise_id'],1);
        }
    }

}

$nowtime = date('Y-m-d H:i:s');
echo "end at :" . $nowtime . "     ";


