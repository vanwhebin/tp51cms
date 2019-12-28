<?php
/**
 * Created by PhpStorm.
 * User: a2
 * Date: 2019/8/28
 * Time: 15:03
 */

namespace app\lib\redis;
use think\facade\Env;

class RedisClient extends \Redis
{
    public $host;
    public $port;
    public $password;
    public $db;

    public function __construct()
    {
        $this->host = Env::get('redis.redis_host','127.0.0.1');
        $this->port = Env::get('redis.redis_port',6379);
        $this->password = Env::get('redis.redis_password','aukeys@2019');
        $this->db = Env::get('redis.redis_database', (config('app_debug') ? 1 : 0));
    }


}