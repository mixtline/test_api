<?php

class Device
{
    public $client;
    public $info;
    public $device_id;

    public function __construct($device_id)
    {
        $this->device_id = $device_id;
        $this->info = DB::instance()->fetch('SELECT * FROM serials WHERE device_id=?', [$device_id]);
        $this->client = new Client($this->info['client_id']);
    }

    public function __get($name)
    {
        if ($name == 'client_id') {
            return $this->client->client_id;
        } else
        if ($name == 'added') {
            return isset($this->info['device_id']);
        } else
        if ($name == 'enabled') {
            return array_filter($this->client->users_as_array, function($user){return isset($user[0][$this->device_id]);})? true : false;
        } else
        if ($name == 'os') {
            $os = 'windows';
            if ($this->info['os'] == 'mac') {
                $os = 'macos';
            } else
            if ($this->info['os'] == 'iphone') {
                $os = 'ios';
            } else
            if ($this->info['os'] == 'android') {
                $os = 'android';
            }
            return $os;
        } else
        if ($name == 'os_short') {
            $os = 'win';
            if ($this->info['os'] == 'mac') {
                $os = 'mac';
            } else
            if ($this->info['os'] == 'iphone') {
                $os = 'iphone';
            } else
            if ($this->info['os'] == 'android') {
                $os = 'android';
            }
            return $os;
        } else
        if ($name == 'is_iphone') {
            return $this->os_short == 'iphone';
        } else
        if ($name == 'owners') {
            $result = [];
            foreach ($this->client->users_as_array as $user_id => $user) {
                if (isset($user[0][$this->device_id]) || isset($user[0]['-'.$this->device_id])) {
                    $result[] = $user_id;
                }
            }
            return $result;
        } else
        if ($name == 'assigned') {
            return $this->client->get_assigned($this->device_id);
        } else
        if (isset($this->info[$name])) {
            return $this->info[$name];
        }
    }

    public function __set($name, $value)
    {
        if ($name == 'assigned') {
            if ((!$this->assigned && $value) || !$value) {
                $config = Util::config();
                $offset = $this->timezone;
                $offset -= $config['params']['server_timezone']*3600;

                $this->client->set_assigned($this->device_id, $value, $offset);
            }
        }
    }

    public function generate_token()
    {
        $this->client->clear_cache();

        $token = hash('md5', uniqid(rand(), true));
        DB::instance()->execute('UPDATE serials SET token=? WHERE device_id=?', [$token, $this->device_id]);
        return $token;
    }
    public function set_did($did)
    {
        $this->client->clear_cache();

        DB::instance()->execute('UPDATE serials SET did=? WHERE device_id=?', [$did, $this->device_id]);
        $this->info['did'] = $did;

        /*
         * make push after enroll!
        if (RedisDB::instance()->sIsMember('just-installed', $this->device_id)) {
            RedisDB::instance()->sRemove('just-installed', $this->device_id);
            if ($this->is_iphone) {
                $this->push('install');

                $now = time();
                $this->push_service($now + 86400, 'install1');
                $this->push_service($now + 7*86400, 'install7');
            }
        }
        */

        return true;
    }
    public function add_account($account_id)
    {
        $found = array_filter($this->client->settings[0], function($a) use ($account_id) {return $a[0] == $account_id;});
        if (!$found) {
            $this->client->settings[0][] = [$this->device_id, [$account_id]];
            return true;
        }
        return false;
    }

    /**
     * @param $token
     * @return Device
     */
    public static function get_device_from_token($token)
    {
        $data = DB::instance()->fetch('SELECT device_id FROM serials WHERE token=?', [$token]);
        if (!$data) {
            return null;
        }
        return new Device($data['device_id']);
    }

    public function push($what)
    {
        $did = isset($this->info['did'])? $this->info['did'] : '';
        $device_id = $this->device_id;
        $os = $this->info['os'];

        if ($did) {
            DB::instance()->execute('INSERT INTO push_notifications (did, device_id, date, os, what) VALUES (?, ?, UNIX_TIMESTAMP(), ?, ?) ON DUPLICATE KEY UPDATE date=UNIX_TIMESTAMP(), what=CONCAT(what, "|", ?)', [
                $did, $device_id, $os, $what, $what
            ]);
        }
    }

    public function push_service($when, $what)
    {
        $did = isset($this->info['did'])? $this->info['did'] : '';
        $device_id = $this->device_id;
        $os = $this->info['os'];

        if ($did) {
            DB::instance()->execute('INSERT INTO push_service (did, device_id, date, os, what) VALUES (?, ?, ?, ?, ?)', [
                $did, $device_id, $when, $os, $what
            ]);
        }
    }
}