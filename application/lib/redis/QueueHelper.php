<?php
/**
 * Created by PhpStorm.
 * User: a2
 * Date: 2019/10/8
 * Time: 17:52
 */

namespace app\lib\redis;


class QueueHelper
{
    public $queueName;

    public $redisQueue;

    public function __construct($queueName)
    {
        $this->queueName = $queueName;
        $this->redisQueue = new RedisClient();
        $this->redisQueue->connect($this->redisQueue->host, $this->redisQueue->port);
        $this->redisQueue->auth($this->redisQueue->password);
        $this->redisQueue->select($this->redisQueue->db);
    }

    /**
     * 检查队列中的任务信息
     * @param $data array 队列生产信息
     * @param $searchPattern  string 队列查找模式
     * @return array|bool
     */
    public function checkRedisQueue($data, $searchPattern)
    {
        // 检查是否存在重复的确认信息
        $this->redisQueue->setOption(\Redis::OPT_SCAN, \Redis::SCAN_NORETRY);
        $it = null;
        while ($arr = $this->redisQueue->zScan($this->queueName, $it, $searchPattern, 1000)) {
            if (count($arr)) {
                $this->redisQueue->close();
                return boolval($arr);
            }
        }
        $this->redisQueue->close();
        return false;
    }

}