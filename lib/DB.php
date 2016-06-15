<?php

class DB
{
    private static $instance;
    private $db = null;

    /**
     * @return DB
     */
    public static function instance()
    {
        if (!DB::$instance) {
            DB::$instance = new DB();
        }
        return DB::$instance;
    }

    private function __construct()
    {
        $config = Util::config();
        $this->db = new PDO($config['components']['db']['connectionString'], $config['components']['db']['username'], $config['components']['db']['password']);
        $this->db->query("SET NAMES UTF8");
    }

    public function fetch($sql, $params = [])
    {
        $sth = $this->db->prepare($sql);
        $sth->execute($params);
        $data = $sth->fetch(PDO::FETCH_ASSOC);
        return $data;
    }
    public function fetchAll($sql, $params = [])
    {
        $sth = $this->db->prepare($sql);
        $sth->execute($params);
        $data = $sth->fetchAll(PDO::FETCH_ASSOC);
        return $data;
    }
    public function execute($sql, $params = [])
    {
        $sth = $this->db->prepare($sql);
        //return $sth->execute($params);
        if (!$sth->execute($params)) {
            throw new ServerException(Util::_t('DB error'),  $sth->errorInfo()[2]);
        }
        return true;
    }
}