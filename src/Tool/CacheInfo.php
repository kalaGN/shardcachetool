<?php
/**
 * Created by infobird
 * User: infobird
 * Date: 2019/01/25/
 * Time: 10:26:58
 * Version: v1.0.0.5
 *
 *
 * 分库分表缓存相关
 */
namespace Infobird/Tool;
class CacheInfo
{
    private $_instance_define_key;
    private $_db_shard_key;
    private $_db_shard_switch_key;
    private $_table_shard_key;
    private $_table_shard_switch_key;

    //组合方式存储redis
    private $_redis_obj;

    public function __construct(Redis $redisobj, $info)
    {

        $this->_redis_obj = $redisobj;
        $this->_instance_define_key = 'com_ins_' . $info['sys'] . '_' . $info['biz'] . '_info';
        $this->_db_shard_key = 'shard_' . $info['enterprise_id'] . '_database';

        $this->_table_shard_key = 'shard_' . $info['enterprise_id'] . '_' . $info['db'] . '_table';

        $this->_db_default_shard_key = 'shard_0_database';
        $this->_table_default_shard_key = 'shard_0_' . $info['db'] . '_table';

        $this->_db_shard_switch_key = 'shard_onoff_' . $info['db'] . '_database';
        if (isset($info['table_entity'])){
            $this->_table_shard_switch_key = 'shard_onoff_' . $info['db'] . '_' . $info['table_entity'] . '_table';
        }
    }

    /**
     * @param $info
     */
    public function setTableKey($info){
        $this->_table_shard_switch_key = 'shard_onoff_' . $info['db'] . '_' . $info['table'] . '_database';
    }

    /**设置企业分库开关
     * @param $eid
     * @param $val
     * @return int
     */
    public function setShardDbSwitch($eid, $val)
    {
        $result = $this->_redis_obj->hSet($this->_db_shard_switch_key, $eid, $val);
        return $result;
    }


    /**获取企业分库开关
     * @param $eid
     * @return string
     */
    public function getShardDbSwitch($eid)
    {
        $result = $this->_redis_obj->hGet($this->_db_shard_switch_key, $eid);
        return $result;
    }


    /**设置分库配置
     * @param $shard_entity
     * @param $info
     * @return int
     */
    public function setShardDbConfig($shard_entity, $info)
    {
        if(empty($info['shard_sys_strategy'])){
            $result = $this->_redis_obj->hSet($this->_db_default_shard_key, $shard_entity, json_encode($info)); 
        }else{
           $result = $this->_redis_obj->hSet($this->_db_shard_key, $shard_entity, json_encode($info)); 
        }
        
        return $result;
    }

    /**设置未分库公共库配置
     * @param $shard_entity
     * @param $info
     * @return int
     */
    public function setDefaultShardDbConfig($shard_entity, $info)
    {
        $result = $this->_redis_obj->hSet($this->_db_default_shard_key, $shard_entity, json_encode($info));
        return $result;
    }
    /**获取企业分库配置
     * @param $shard_entity
     * @return string+
     */
    public function getShardDbConfig($shard_entity)
    {
        $result = $this->_redis_obj->hGet($this->_db_shard_key, $shard_entity);
        return json_decode($result, 1);
    }

    public function delShardDbCofig($shard_entity){
        return $this->_redis_obj->hDel($this->_db_shard_key, $shard_entity);
    }
    /**
     * @param $shard_entity
     * @return mixed
     */
    public function getDefaultShardDbConfig($shard_entity)
    {
        $result = $this->_redis_obj->hGet($this->_db_default_shard_key, $shard_entity);
        return json_decode($result, 1);
    }

    public function delDefaultShardDbCofig($shard_entity){
        return $this->_redis_obj->hDel($this->_db_default_shard_key, $shard_entity);
    }
    /**设置分表开关
     * @param $eid
     * @param $val
     * @return int
     */
    public function setShardTableSwitch($eid, $val)
    {
        $result = $this->_redis_obj->hSet($this->_table_shard_switch_key, $eid, $val);
        return $result;
    }

    /**获取分表开关
     * @param $eid
     * @return string
     */
    public function getShardTableSwitch($eid)
    {
        $result = $this->_redis_obj->hGet($this->_table_shard_switch_key, $eid);
        return $result;
    }

    /**设置分库企业的分表配置
     * @param $shardentity
     * @param $info
     * @return int
     */
    public function setShardTableConfig($shardentity, $info)
    {
        $result = $this->_redis_obj->hSet($this->_table_shard_key, $shardentity, json_encode($info));
        return $result;
    }

    public function delShardTableConfig($shardentity){
        return $this->_redis_obj->hDel($this->_table_shard_key, $shardentity);
    }

    /**设置未分库的分表配置
     * @param $shardentity
     * @param $info
     * @return int
     */
    public function setDefaultShardTableConfig($shardentity, $info)
    {
        $result = $this->_redis_obj->hSet($this->_table_default_shard_key, $shardentity, json_encode($info));
        return $result;
    }

    public function delDefaultShardTableConfig($shardentity){
        return $this->_redis_obj->hDel($this->_table_default_shard_key, $shardentity);
    }

    /**获取分表配置
     * @param $shardentity
     * @return string
     */
    public function getShardTableConfig($shardentity)
    {
        $result = $this->_redis_obj->hGet($this->_table_shard_key, $shardentity);
        return json_decode($result, 1);
    }
    
    /**获取未分库的分表配置
     * @param $shardentity
     * @return mixed
     */
    public function getDefaultShardTableConfig($shardentity)
    {
        $result = $this->_redis_obj->hGet($this->_table_default_shard_key, $shardentity);
        return json_decode($result, 1);
    }

    /**存储实例信息
     * @param $instance_id
     * @param $info
     * @return int
     */
    public function setInstance($instance_id, $info)
    {
        $result = $this->_redis_obj->hSet($this->_instance_define_key, $instance_id, json_encode($info));
        return $result;
    }


    /**获取实例信息
     * @param $instance_id
     * @return string
     */
    public function getInstance($instance_id)
    {
        $result = $this->_redis_obj->hGet($this->_instance_define_key, $instance_id);
        return json_decode($result, 1);
    }
}
