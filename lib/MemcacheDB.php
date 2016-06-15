<?php

class MemcacheDB
{
    private static $instance;
    
    public $memc;
    public $host = 'localhost';
    public $port = 11211;
    public $hour = 3600;
    public $day  = 86400;
    public $week = 604800;
    public $is_connect = 0;

    /**
     * @return MemcacheDB
     */
    public static function instance()
    {
        if (!MemcacheDB::$instance) {
            MemcacheDB::$instance = new MemcacheDB();
        }
        return MemcacheDB::$instance;
    }
  
    private function __construct()
    {}

    private function connect() 
    {
        if (!$this->is_connect) {
            $this->memc = new Memcache();
            try {
                if ($this->memc->connect($this->host, $this->port, 2)) {
                    $this->is_connect = 1;
                } else {
                    if ($this->memc->pconnect($this->host, $this->port, 2)) {
                        $this->is_connect = 1;
                    } else {
                        $this->is_connect = 0;
                    }
                }
            } catch (Exception $e) {
                die($e->getMessage());
            }
        }
    }
  
    function add($key, $val, $compress = false, $time = false)
    {
        $this->connect();
        return $this->memc->add($key, $val, $compress? MEMCACHE_COMPRESSED : false, $time == false? $this->day : $time);
    }

    function get($key, $node = false)
    {
        $this->connect();
        return $this->memc->get($key);
    }
  
    function set($key, $val, $compress = false, $time = false) 
    {
        $this->connect();
        $res = $this->memc->add($key, $val, $compress? MEMCACHE_COMPRESSED : false, $time == false? $this->day : $time);
        
        if (!$res) {
            $res = $this->memc->replace($key, $val, $compress? MEMCACHE_COMPRESSED : false, $time == false? $this->day : $time);
        }
        
        return $res;
    }

    public function delete($key)
    {
        $this->connect();
        $this->memc->delete($key);
    }

    public function getExtendedStats() 
    {
        $this->connect();
        return $this->memc->getExtendedStats();
    }

    public function flush()
    {
        $this->connect();
        return $this->memc->flush();
    }

    public function check($key)
    {
        $this->connect();
        return $this->get($key) !== false;
    }
}
