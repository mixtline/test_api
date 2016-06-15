<?php
/*
 * https://github.com/nicolasff/phpredis
 */

class RedisDB
{
    private static $instance;

    /**
     * @var $memc Redis
     */
    public $memc;
    public $host = 'localhost';
    public $port = 6379;
    public $hour = 3600;
    public $day  = 86400;
    public $week = 604800;
    public $database = 1;
    public $is_connect = 0;

    /**
     * @return RedisDB
     */
    public static function instance()
    {
        if (!RedisDB::$instance) {
            RedisDB::$instance = new RedisDB();
        }
        return RedisDB::$instance;
    }
  
    private function __construct()
    {}

    private function connect() 
    {
        if (!$this->is_connect) {
            $this->memc = new Redis();
            try {
                if ($this->memc->connect($this->host, $this->port, 5)) {
                    $this->is_connect = 1;
                } else {
                    if ($this->memc->pconnect($this->host, $this->port, 5)) {
                        $this->is_connect = 1;
                    } else {
                        $this->is_connect = 0;
                    }
                }
                $this->memc->select($this->database);
            } catch (Exception $e) {
                die($e->getMessage());
            }
        }
    }
  
    public function get($key)
    {
	
        $this->connect();
        return $this->memc->get($key);
    }

    public function mGet($keys)
    {
        return $this->getMultiple($keys);
    }

    public function getMultiple($keys)
    {
        $this->connect();
        return $this->memc->getMultiple($keys);
    }

    public function keys($search)
    {
        $this->connect();
        return $this->memc->keys($search);
    }

    public function set($key, $val, $timeout = 0.0)
    {
        $this->connect();
        if ($timeout > 0) {
            $res = $this->memc->set($key, $val, $timeout);
        } else {
            $res = $this->memc->set($key, $val);
        }

        return $res;
    }

    public function delete($key)
    {
        $this->connect();
        $this->memc->del($key);
    }

    public function inc($key)
    {
        $this->connect();
        return $this->memc->incr($key);
    }

    public function dec($key)
    {
        $this->connect();
        return $this->memc->decr($key);
    }

    public function incrBy($key, $value)
    {
        $this->connect();
        return $this->memc->incrBy($key, $value);
    }

    public function decrBy($key, $value)
    {
        $this->connect();
        return $this->memc->decrBy($key, $value);
    }

    public function flush()
    {
        $this->connect();
        return $this->memc->flushDB();
    }

    public function check($key)
    {
        $this->connect();
        return $this->exists($key);
    }

    public function setTimeout($key, $duration)
    {
        $this->connect();
        $this->memc->setTimeout($key, $duration);
    }

    public function rename($key, $new_key)
    {
        $this->connect();
        return $this->memc->rename($key, $new_key);
    }

    /* sets */

    public function sMembers($key)
    {
        $this->connect();
        return $this->memc->sMembers($key);
    }

    public function sAdd($key, $value)
    {
        $this->connect();
        return $this->memc->sAdd($key, $value);
    }

    public function sRemove($key, $value)
    {
        $this->connect();
        $res = $this->memc->sRemove($key, $value);

        return $res;
    }

    public function sMove($key1, $key2, $value)
    {
        $this->connect();
        return $this->memc->sMove($key1, $key2, $value);
    }

    public function sContains($key, $value)
    {
        $this->connect();
        return $this->memc->sIsMember($key, $value);
    }

    public function sIsMember($key, $value)
    {
        $this->connect();
        return $this->memc->sIsMember($key, $value);
    }

    public function sRandom($key)
    {
        $this->connect();
        $res = $this->memc->sRandMember($key);

        return $res;
    }

    public function sSize($key)
    {
        $this->connect();
        return $this->memc->sSize($key);
    }

    /* lists */

    public function lPush($key, $value)
    {
        $this->connect();
        return $this->memc->lPush($key, $value);
    }
    
    public function lPop($key)
    {
        $this->connect();
        return $this->memc->lPop($key);
    }

    public function rPop($key)
    {
        $this->connect();
        return $this->memc->rPop($key);
    }

    public function rPush($key, $value)
    {
        $this->connect();
        return $this->memc->rPush($key, $value);
    }

    public function lSize($key)
    {
        $this->connect();
        return $this->memc->lSize($key);
    }

    public function lRange($key, $start, $end)
    {
        $this->connect();
        return $this->memc->lRange($key, $start, $end);
    }

    public function lTrim($key, $start, $end)
    {
        $this->connect();
        return $this->memc->lTrim($key, $start, $end);
    }

    public function lRem($key, $count, $value)
    {
        $this->connect();
        return $this->memc->lRem($key, $count, $value);
    }

    public function type($key)
    {
        $this->connect();
        return $this->memc->type($key);
    }


    /* sorted sets */
    // data = array(id, login, level);

    public function zAdd($key, $score, $data)
    {
        $this->connect();
        return $this->memc->zAdd($key, $score, $data);
    }

    public function zDelete($key, $data)
    {
        $this->connect();
        $res = $this->memc->zRemove($key, $data);

        return $res;
    }

    public function zRange($key, $start, $stop)
    {
        $this->connect();
        $res = $this->memc->zRange($key, $start, $stop, true);

        return $res;
    }

    public function zRevRange($key, $start, $stop)
    {
        $this->connect();
        $res = $this->memc->zRevRange($key, $start, $stop, true);

        return $res;
    }

    public function zRank($key, $data)
    {
        $this->connect();
        $res = $this->memc->zRank($key, $data);

        return $res;
    }

    public function zRevRank($key, $data)
    {
        $this->connect();
        $res = $this->memc->zRevRank($key, $data);

        return $res;
    }

    public function zScore($key, $data)
    {
        $this->connect();
        $res = $this->memc->zScore($key, $data);

        return $res;
    }

    public function zSize($key)
    {
        $this->connect();
        $res = $this->memc->zSize($key);

        return $res;
    }

    public function zIncrBy($key, $value, $data)
    {
        $this->connect();
        $res = $this->memc->zIncrBy($key, $value, $data);

        return $res;
    }

    public function zDeleteRangeByScore($key, $start, $stop)
    {
        $this->connect();
        $res = $this->memc->zRemRangeByScore($key, $start, $stop);

        return $res;
    }


    /* hashes */

    public function hSet($key, $field, $value)
    {
        $this->connect();
        return $this->memc->hSet($key, $field, $value);
    }
    public function hMSet($key, $fields = array())
    {
        $this->connect();
        return $this->memc->hmSet($key, $fields);
    }
    public function hIncrBy($key, $field, $value = 1)
    {
        $this->connect();
        return $this->memc->hIncrBy($key, $field, $value);
    }
    public function hGet($key, $field)
    {
        $this->connect();
        return $this->memc->hGet($key, $field);
    }
    public function hMGet($key, $fields = array())
    {
        $this->connect();
        return $this->memc->hmGet($key, $fields);
    }

    public function hGetAll($key)
    {
        $this->connect();
        return $this->memc->hGetAll($key);
    }

    public function hDel($key, $field)
    {
        $this->connect();
        return $this->memc->hDel($key, $field);
    }

    public function hLen($key)
    {
        $this->connect();
        return $this->memc->hLen($key);
    }
}
