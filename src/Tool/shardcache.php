<?php
namespace Infobird\Tool;
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
error_reporting(0);
date_default_timezone_set("PRC");

class shardcache
{
    protected $secretkey = '';
    protected $conf = array();

    public function __construct($conf)
    {
        $this->conf = $conf;
        $this->secretkey = $conf['aeskey'];
    }

    public function run()
    {
        $aes = new Phpaes($this->secretkey);


        //common库配置
        $mysqllink = new ConnectMysqli(array(
            'host' => $this->conf['commondb']['host'],
            'port' => $this->conf['commondb']['port'],
            'user' => $this->conf['commondb']['username'],
            'pass' => $aes->decrypt($this->conf['commondb']['password']),
            'db' => $this->conf['commondb']['dbname'],
            'charset' => 'utf8'
        ));
//==============================================================================================================
        $nowtime = date('Y-m-d H:i:s');
        //连接 AA029 redis信息 密码密文
        $toredis = new \Redis();
        $toredis->connect($this->conf['redis']['host'], $this->conf['redis']['port'], $this->conf['redis']['timeout']);
        if ($this->conf['redis']['password']) {
            $re = $toredis->auth($aes->decrypt($this->conf['redis']['password']));
        }
        $toredis->select($this->conf['redis']['db']);
        if (!($toredis instanceof \Redis)) {
            file_put_contents('logs'.DIRECTORY_SEPARATOR.'error.txt',"from redis connnect error!". "\r\n", FILE_APPEND);
            exit;
        }


        // 1 取出资源信息
        $query = "select * from com_storage_instance_define left JOIN com_servers_pool on com_storage_instance_define.server_identity=com_servers_pool.server_identity";
        $result = $mysqllink->getAll($query);
        file_put_contents('logs'.DIRECTORY_SEPARATOR.'resource_sync.txt', $nowtime . "\r\n" . "data:" . json_encode($result) . "\r\n", FILE_APPEND);
        $filter = array('server_ip', 'server_password', 'server_port', 'server_user', 'instance_db', 'instance_character', 'instance_connect_timeout', 'is_stop');
        foreach ($result as $rk => $rv) {
            $data = array();
            foreach ($filter as $v) {
                if (isset($rv[$v])) {
                    $data[$v] = $rv[$v];
                }
            }
            if ($rv['sys_category'] == 'nosql') {
                $rv['biz_type'] = 'redis';
            } elseif ($rv['sys_category'] == 'rdb') {
                $rv['biz_type'] = 'mysql';
            } elseif ($rv['sys_category'] == 'api') {
                if ($rv['biz_type'] == '1' || $rv['biz_type'] == '0') {
                    $rv['biz_type'] = 'http';
                }
                if ($rv['biz_type'] == '3') {
                    $rv['biz_type'] = 'soap';
                }
            }
            $data['sys'] = $rv['sys_category'];
            $data['biz'] = $rv['biz_type'];
            $cache = new CacheInfo($toredis, $data);
            $cache->setInstance($rv['instance_identity'], $data);
        }


        //2 取出预设 自定义 分库配置分库
        $query = "select * from com_storage_shard_config";
        $shardconfigresult = $mysqllink->getAll($query);

        file_put_contents('logs'.DIRECTORY_SEPARATOR.'sharddb_sync.txt', $nowtime . "\r\n" . "data:" . json_encode($shardconfigresult) . "\r\n", FILE_APPEND);
        $filter = array('shard_identity', 'shard_entity', 'storage_identity_cluster', 'shard_sys_strategy', 'shard_fields', 'enterprise_id', 'shard_name');
        if (!empty($shardconfigresult)) {
            foreach ($shardconfigresult as $rk => $rv) {
                $data = array();
                foreach ($filter as $v) {
                    if (isset($rv[$v])) {
                        $data[$v] = $rv[$v];
                    }
                }
                $data['enterprise_id'] = $rv['enterprise_id'];
                $data['db'] = $rv['shard_entity'];
                $cache = new CacheInfo($toredis, $data);
                //预设 自定义分库 写入
                $cache->setShardDbConfig($rv['shard_entity'], $data);
                if ($data['enterprise_id'] > 0) {
                    //设置了分库的企业 打开分库开关
                    $cache->setShardDbSwitch($data['enterprise_id'], 1);
                }
            }
        }


        //3 取出预设 自定义 分表
        $query = "select com_storage_table_shard_config.*,com_storage_shard_config.shard_entity as db from com_storage_table_shard_config left JOIN com_storage_shard_config on com_storage_table_shard_config.db_shard_identity=com_storage_shard_config.shard_identity";
        $shardtableconfigresult = $mysqllink->getAll($query);
        $nowtime = date('Y-m-d H:i:s');

        file_put_contents('logs'.DIRECTORY_SEPARATOR.'shardtable_sync.txt', $nowtime . "\r\n" . "data:" . json_encode($shardtableconfigresult) . "\r\n", FILE_APPEND);
        $filter = array('default_ddl_file', 'shard_biz_strategy', 'shard_sys_strategy', 'shard_entity', 'db', 'shard_fields');
        if (!empty($shardtableconfigresult)) {
            foreach ($shardtableconfigresult as $rk => $rv) {
                $data = array();
                foreach ($filter as $v) {
                    if (isset($rv[$v])) {
                        $data[$v] = $rv[$v];
                    }
                }
                $data['enterprise_id'] = $rv['enterprise_id'];
                $data['db'] = $rv['db'];
                $data['table_entity'] = $rv['shard_entity'];
                $cache = new CacheInfo($toredis, $data);

                //预设 自定义分库 写入
                $cache->setShardTableConfig($rv['shard_entity'], $data);
                if ($data['enterprise_id'] > 0) {
                    //设置了分表的企业 打开分库开关
                    $cache->setShardTableSwitch($data['enterprise_id'], 1);
                }
            }

        }

        $nowtime = date('Y-m-d H:i:s');
        file_put_contents('logs'.DIRECTORY_SEPARATOR.'shardtable_sync.txt', $nowtime . "\r\n" . "data:" . "\r\n", FILE_APPEND);
    }
}

