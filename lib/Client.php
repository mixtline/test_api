<?php
define('EMULATE_ACTIVATION', $_SERVER['REMOTE_ADDR'] == '127.0.0.1' || $_SERVER['SERVER_ADDR'] == '198.199.67.123');

class Client
{
    const CLIENT_SETTINGS_DEVICES = 0;
    const CLIENT_SETTINGS_DEVICE_ID = 0;
    const CLIENT_SETTINGS_DEVICE_ACCOUNTS = 1;
    const CLIENT_SETTINGS_DEVICE_ACCOUNTS_ADDED = 2;
    const CLIENT_SETTINGS_DEVICE_ACCOUNTS_DELETED = 3;
    const CLIENT_SETTINGS_DEVICE_STAT = 4;
    const CLIENT_SETTINGS_DEVICE_APPS = 5; // = Account::ACCOUNT_APPS
    const CLIENT_SETTINGS_DEVICE_LOCK = 6; // device is locked ([locked, expire, duration, real_status (null=-1, 0, 1), real_callback_date, reason])
    const CLIENT_SETTINGS_DEVICE_ENROLL = 7; // for iphones only ([enrolled, [[enroll_date2, unenroll_date2], [enroll_date1, unenroll_date1]..]])
    const CLIENT_SETTINGS_DEVICE_ASSIGNED = 8; // array(when the devices was assigned to an user, stop_warning)
    const CLIENT_SETTINGS_DEVICE_OFFLINE = 9; // array(when the device was offline [start, finish], finish = 0 means now))
    const CLIENT_SETTINGS_DEVICE_VPN = 10; // array(when the pvn was offline [start, finish], finish = 0 means now))

    // start user settings
    const CLIENT_SETTINGS_USERS = 1;
    const CLIENT_SETTINGS_DEVICES_IDS = 0; // array(device_id => array(account_ids))
    const CLIENT_SETTINGS_USER_NAME = 1;
    const CLIENT_SETTINGS_USER_AVATAR = 2;
    const CLIENT_SETTINGS_USER_BIRTH = 3;
    const CLIENT_SETTINGS_USER_GENDER = 4;
    const CLIENT_SETTINGS_USER_SEVERITY = 5;
    const CLIENT_SETTINGS_USER_SAFE_SEARCH = 6;
    const CLIENT_SETTINGS_USER_BLOCK_SITES = 7;
    const CLIENT_SETTINGS_USER_BLACK_SITES = 8;
    const CLIENT_SETTINGS_USER_WHITE_SITES = 9;
    const CLIENT_SETTINGS_USER_CUSTOM_SITES = 10; //
    const CLIENT_SETTINGS_TIME_LIMIT = 11; // array of (device_id => CLIENT_SETTINGS_TIME_LIMITS_*), key 0 means common settings
    const CLIENT_SETTINGS_USER_FINISHED = 12; // user settings has been finished (or not) array(0 - settings, 1 - client settings, 2 - settings step2 (tab #))

    const CLIENT_SETTINGS_INFO = 2;
    const CLIENT_SETTINGS_INFO_EMAIL = 0;
    const CLIENT_SETTINGS_INFO_FNAME = 1;
    const CLIENT_SETTINGS_INFO_LNAME = 2;
    const CLIENT_SETTINGS_INFO_PHONE = 3;

    const CLIENT_SETTINGS_NOTIFICATION = 3;
    const CLIENT_SETTINGS_NOTIFICATION_EMAIL = 0;
    const CLIENT_SETTINGS_NOTIFICATION_WHO = 1;
    const CLIENT_SETTINGS_NOTIFICATION_PERIOD = 2;
    const CLIENT_SETTINGS_NOTIFICATION_HIGH = 3; // user_id|0 => [-1|0|1, hours, period_start, period_end]
    const CLIENT_SETTINGS_NOTIFICATION_MEDIUM = 4;
    const CLIENT_SETTINGS_NOTIFICATION_ON = 5;

    const CLIENT_SETTINGS_LANGUAGE = 4;
    const CLIENT_SETTINGS_TIMEZONE = 5; // timezone diff | timezone name
    const CLIENT_SETTINGS_24HOURS = 6;
    const CLIENT_SETTINGS_DAYLIGHT = 7;

    public $client_id;
    public $settings = [];
    /**
     * @var $users array
     */
    public $users = [];

    private $_is_confirmed = 0;
    private $_exists = false;

    public function __construct($client_id)
    {
        $this->client_id = $client_id;
        $data = DB::instance()->fetch('SELECT * FROM settings WHERE alfa=? AND client_id=?', [ord($client_id), $client_id]);
        if ($data) {
            $this->_exists = true;
            $this->settings = JSON::decode($data['data']);
            if (isset($this->settings[Client::CLIENT_SETTINGS_USERS]) && is_array($this->settings[Client::CLIENT_SETTINGS_USERS])) {
                foreach ($this->settings[Client::CLIENT_SETTINGS_USERS] as $user_id => $u) {
                    $this->users[$user_id] = new User($this, $user_id);
                }
            }
        }
    }

    public static function langs()
    {
        return array('en', 'es', 'de', 'ru');
    }

    public function __get($name)
    {
        if ($name == 'users_as_array') {
            $result = isset($this->settings[Client::CLIENT_SETTINGS_USERS])? $this->settings[Client::CLIENT_SETTINGS_USERS] : [];
            return is_array($result)? $result : [];
        } else
        if ($name == 'devices_as_array') {
            $result = isset($this->settings[Client::CLIENT_SETTINGS_DEVICES])? $this->settings[Client::CLIENT_SETTINGS_DEVICES] : [];
            return is_array($result)? $result : [];
        } else
        if ($name == 'exists') {
            return $this->_exists;
        } else
        if ($name == 'email') {
            return isset($this->settings[Client::CLIENT_SETTINGS_INFO][Client::CLIENT_SETTINGS_INFO_EMAIL])? $this->settings[Client::CLIENT_SETTINGS_INFO][Client::CLIENT_SETTINGS_INFO_EMAIL] : '';
        } else
        if ($name == 'is_confirmed') {
            return $this->_is_confirmed;
        } else
        if ($name == 'fname') {
            $fname = isset($this->settings[Client::CLIENT_SETTINGS_INFO][Client::CLIENT_SETTINGS_INFO_FNAME])? $this->settings[Client::CLIENT_SETTINGS_INFO][Client::CLIENT_SETTINGS_INFO_FNAME] : '';
            return $fname;
        } else
        if ($name == 'lname') {
            $lname = isset($this->settings[Client::CLIENT_SETTINGS_INFO][Client::CLIENT_SETTINGS_INFO_LNAME])? $this->settings[Client::CLIENT_SETTINGS_INFO][Client::CLIENT_SETTINGS_INFO_LNAME] : '';
            return $lname;
        } else
        if ($name == 'lang') {
            $lang = isset($this->settings[Client::CLIENT_SETTINGS_LANGUAGE])? $this->settings[Client::CLIENT_SETTINGS_LANGUAGE] : 'en';
            if (!in_array($lang, Client::langs())) $lang = 'en';
            return $lang;
        }
    }

    public function __set($name, $value)
    {
        if ($name == 'email') {
            if (!isset($this->settings[Client::CLIENT_SETTINGS_INFO])) {
                $this->settings[Client::CLIENT_SETTINGS_INFO] = [];
            }
            $this->settings[Client::CLIENT_SETTINGS_INFO][Client::CLIENT_SETTINGS_INFO_EMAIL] = $value;
            ksort($this->settings[Client::CLIENT_SETTINGS_INFO]);
        } else
        if ($name == 'first_name') {
            if (!isset($this->settings[Client::CLIENT_SETTINGS_INFO])) {
                $this->settings[Client::CLIENT_SETTINGS_INFO] = [];
            }
            $this->settings[Client::CLIENT_SETTINGS_INFO][Client::CLIENT_SETTINGS_INFO_FNAME] = $value;
            ksort($this->settings[Client::CLIENT_SETTINGS_INFO]);
        } else
        if ($name == 'last_name') {
            if (!isset($this->settings[Client::CLIENT_SETTINGS_INFO])) {
                $this->settings[Client::CLIENT_SETTINGS_INFO] = [];
            }
            $this->settings[Client::CLIENT_SETTINGS_INFO][Client::CLIENT_SETTINGS_INFO_LNAME] = $value;
            ksort($this->settings[Client::CLIENT_SETTINGS_INFO]);
        } else
        if ($name == 'lang') {
            if (!in_array($value, Client::langs())) {
                $value = 'en';
            }
            $this->settings[Client::CLIENT_SETTINGS_LANGUAGE] = $value;
        } else
        if ($name == 'is_confirmed') {
            $this->_is_confirmed = $value? 1 : 0;
        }
    }

    public function init_settings()
    {
        $this->settings[Client::CLIENT_SETTINGS_NOTIFICATION] = [
            Client::CLIENT_SETTINGS_NOTIFICATION_EMAIL => $this->email,
            Client::CLIENT_SETTINGS_NOTIFICATION_WHO => 0, // all users
            Client::CLIENT_SETTINGS_NOTIFICATION_PERIOD => [0 => [7, 0, 1080]],
            Client::CLIENT_SETTINGS_NOTIFICATION_HIGH => [0 => [-1]],
            Client::CLIENT_SETTINGS_NOTIFICATION_MEDIUM => [0 => [-1]],
            Client::CLIENT_SETTINGS_NOTIFICATION_ON => 1,
        ];

        if ($this->exists) {
            DB::instance()->execute('UPDATE serials SET is_confirmed=? WHERE client_id=?', [
                $this->_is_confirmed,
                $this->client_id
            ]);
        } else {
            $this->add_fake_license();
        }
    }

    public function clear_cache()
    {
        MemcacheDB::instance()->delete('account'.$this->client_id);
        MemcacheDB::instance()->delete('client'.$this->client_id);
        MemcacheDB::instance()->delete('serials'.$this->client_id);
    }

    public function save()
    {
        ksort($this->settings);
        if ($this->exists) {
            DB::instance()->execute('UPDATE settings SET data=? WHERE alfa=? AND client_id=?', [JSON::encode($this->settings), ord($this->client_id), $this->client_id]);
        } else {
            DB::instance()->execute('INSERT INTO settings (alfa, client_id, data) VALUES (?, ?, ?)', [ord($this->client_id), $this->client_id, JSON::encode($this->settings)]);
        }

        $this->clear_cache();
    }

    public static function login($email, $password)
    {
        if (!$email) {
            throw new ServerException(Util::_t('Invalid request'), str_replace('{email}', $email, Util::_t('The {email} value is not a valid email address.')));
        }
        if (!$password) {
            throw new ServerException(Util::_t('Invalid request'), Util::_t('Malformed request.'));
        }

        $config = Util::config();
        $url = $config['params']['activate_client_url'];

        $headers = get_headers($url . $email);
        $content = false;
        if (strpos($headers[0], '200')) {
            $content = @file_get_contents($url . $email);
        } elseif (strpos($headers[0], '404')) {
            $content = '';
        }

        $is_admin = EMULATE_ACTIVATION && $email == 'amustware@amustware.com';

        if (!$is_admin && $content === false) {
            throw new ServerException(Util::_t('Autorization server error'), Util::_t('No answer from autorization server'));
        }
        else if (!$is_admin && !$content) {
            throw new ServerException(Util::_t('Incorrect email or password'), Util::_t('Make sure your email and password are both correct'));
        }
        else {
            if (version_compare(phpversion(), '5.5.0', '<')) {
                require_once(__DIR__ . DIRECTORY_SEPARATOR . 'password.php');
            }
            $data = json_decode($content, true);
            $hash = trim($data['password_hash']);
            $password_verify = password_verify(trim($password), $hash);
            if (!$is_admin && !$password_verify) {
                throw new ServerException(Util::_t('Incorrect email or password'), Util::_t('Make sure your email and password are both correct'));
            } else {
                if ($is_admin) {
                    return new Client('864fcddf-7f6a-45cc-aba2-069f8b95b385');
                } else {
                    return new Client($data['id']);
                }
            }
        }
    }

    public static function create($email, $name, $password)
    {
        if (!$email) {
            throw new ServerException(Util::_t('Invalid request'), str_replace('{email}', $email, _t('The {email} value is not a valid email address.')));
        }
        if (!$name) {
            throw new ServerException(Util::_t('Invalid request'), _t('Name must be defined.'));
        }
        if (!$password) {
            throw new ServerException(Util::_t('Invalid request'), _t('Password must be defined.'));
        }
        $parts = explode(' ', $name);
        $fname = $lname = '';
        if (isset($parts[0])) {
            $fname = array_shift($parts);
        }
        if (isset($parts[0])) {
            $lname = implode(' ', $parts);
        }

        $config = Util::config();
        if (Server::$lang == 'en') {
            $url = $config['params']['activate_register_url'];
        } else {
            $url = sprintf($config['params']['activate_register_url_lang'], Server::$lang);
        }
        $data = array(
            'registration[email]' => $email,
            'registration[password]' => $password,
            'registration[firstName]' => $fname? $fname : $name,
            'registration[lastName]' => $lname? $lname : $fname,
            'registration[ip]' => isset($_SERVER['REMOTE_ADDR'])? $_SERVER['REMOTE_ADDR'] : '',
            //'registration[hash]' => $hash,
        );

        $options = array(
            'http' => array(
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                'method'  => 'POST',
                'content' => http_build_query($data),
            ),
        );
        $context  = stream_context_create($options);
        $result = file_get_contents($url, false, $context);

        if (strtolower($result) != 'ok') {
            $error_title = Util::_t('Registration error');
            $error = str_replace('{email}', $email, Util::_t($result));
            throw new ServerException($error_title, $error);
        }
    }

    public static function reset_password($email)
    {
        if (!$email) {
            throw new ServerException(Util::_t('Invalid request'), str_replace('{email}', $email, Util::_t('The {email} value is not a valid email address.')));
        }

        $config = Util::config();
        $url = $config['params']['reset_password_url'];
        $url = str_replace(['{email}', '{locale}'], [$email, Server::$lang], $url);
        $content = file_get_contents($url);


        $xml = simplexml_load_string($content);
        $json = json_encode($xml);
        $data = json_decode($json, true);

        //if (isset($data['status']) && $data['status'] == 'OK') {
        //    return true;
        //}
        if (!isset($data['status'])) {
            throw new ServerException(Util::_t('Invalid request'), Util::_t('Malformed remote server response.'));
        }
        if (isset($data['status']) && $data['status'] == 'ERROR') {
            throw new ServerException(Util::_t('Reset password error'), $data['reason']);
        }
    }

    public function regenerate_xml($what = 'all')
    {
        DB::instance()->execute('INSERT IGNORE INTO request_xml (client_id, xml) VALUES (?, ?) ON DUPLICATE KEY UPDATE xml="all"', [$this->client_id, $what]);
    }

    public function set_device_status($user_id, $device_id, $status = true)
    {
        if ($status) {
            if (isset($this->settings[Client::CLIENT_SETTINGS_USERS][$user_id][Client::CLIENT_SETTINGS_DEVICES_IDS]['-'.$device_id])) {
                $this->settings[Client::CLIENT_SETTINGS_USERS][$user_id][Client::CLIENT_SETTINGS_DEVICES_IDS][$device_id] = $this->settings[Client::CLIENT_SETTINGS_USERS][$user_id][Client::CLIENT_SETTINGS_DEVICES_IDS]['-'.$device_id];
                unset($this->settings[Client::CLIENT_SETTINGS_USERS][$user_id][Client::CLIENT_SETTINGS_DEVICES_IDS]['-'.$device_id]);
            }
        } else {
            if (isset($this->settings[Client::CLIENT_SETTINGS_USERS][$user_id][Client::CLIENT_SETTINGS_DEVICES_IDS][$device_id])) {
                $this->settings[Client::CLIENT_SETTINGS_USERS][$user_id][Client::CLIENT_SETTINGS_DEVICES_IDS]['-'.$device_id] = $this->settings[Client::CLIENT_SETTINGS_USERS][$user_id][Client::CLIENT_SETTINGS_DEVICES_IDS][$device_id];
                unset($this->settings[Client::CLIENT_SETTINGS_USERS][$user_id][Client::CLIENT_SETTINGS_DEVICES_IDS][$device_id]);
            }
        }
    }

    public function assign_device($user_id, $device_id, $status = true, $reassign = true)
    {
        if ($reassign) {
            foreach ($this->settings[Client::CLIENT_SETTINGS_USERS] as $uid => $user) {
                if (isset($user[Client::CLIENT_SETTINGS_DEVICES_IDS][$device_id])) {
                    unset($this->settings[Client::CLIENT_SETTINGS_USERS][$uid][Client::CLIENT_SETTINGS_DEVICES_IDS][$device_id]);
                } else
                if (isset($user[Client::CLIENT_SETTINGS_DEVICES_IDS]['-'.$device_id])) {
                    unset($this->settings[Client::CLIENT_SETTINGS_USERS][$uid][Client::CLIENT_SETTINGS_DEVICES_IDS]['-'.$device_id]);
                }
            }
        }
        if ($status) {
            if (isset($this->settings[Client::CLIENT_SETTINGS_USERS][$user_id][Client::CLIENT_SETTINGS_DEVICES_IDS]['-'.$device_id])) {
                unset($this->settings[Client::CLIENT_SETTINGS_USERS][$user_id][Client::CLIENT_SETTINGS_DEVICES_IDS]['-'.$device_id]);
            }
            $this->settings[Client::CLIENT_SETTINGS_USERS][$user_id][Client::CLIENT_SETTINGS_DEVICES_IDS][$device_id] = ['all'];
            $this->settings[Client::CLIENT_SETTINGS_USERS][$user_id][Client::CLIENT_SETTINGS_USER_FINISHED] = [1]; // settings finished
        } else {
            if (isset($this->settings[Client::CLIENT_SETTINGS_USERS][$user_id][Client::CLIENT_SETTINGS_DEVICES_IDS][$device_id])) {
                unset($this->settings[Client::CLIENT_SETTINGS_USERS][$user_id][Client::CLIENT_SETTINGS_DEVICES_IDS][$device_id]);
            }
            $this->settings[Client::CLIENT_SETTINGS_USERS][$user_id][Client::CLIENT_SETTINGS_DEVICES_IDS]['-'.$device_id] = ['all'];
        }
    }

    public function add_user($devices, $name, $avatar, $birth, $gender)
    {
        if (!isset($this->settings[Client::CLIENT_SETTINGS_USERS])) {
            $this->settings[Client::CLIENT_SETTINGS_USERS] = [];
        }
        $user_id = sizeof($this->settings[Client::CLIENT_SETTINGS_USERS]);
        $this->settings[Client::CLIENT_SETTINGS_USERS][$user_id] = [
            0 => $devices,
            1 => $name,
            2 => $avatar,
            3 => $birth,
            4 => $gender == 'male'? 'm' : 'f',
        ];
        if ($devices) {
            $this->settings[Client::CLIENT_SETTINGS_USERS][$user_id][Client::CLIENT_SETTINGS_USER_FINISHED] = [1]; // settings finished
        }
        return $user_id;
    }

    public function add_device($device_id, $name, $os, $timezone)
    {
        if (!isset($this->settings[Client::CLIENT_SETTINGS_DEVICES])) {
            $this->settings[Client::CLIENT_SETTINGS_DEVICES] = [];
        }
        $this->settings[Client::CLIENT_SETTINGS_DEVICES][] = [$device_id,[$device_id]];

        $licenses = DB::instance()->fetchAll('SELECT * FROM serials WHERE client_id=?', [$this->client_id]);
        $n = sizeof($licenses);
        $license = current($licenses);
        if ($n > 2 && !in_array($license['premium'], ['p10y', 'p10m'])) {
            $error_title = Util::_t('No available licenses');
            if (in_array($license['premium'], ['p3y', 'p3m'])) {
                $error_message = Util::_t('This device cannot be connected to your Parent Account because you have reached the maximum number of devices ({n}) for your type of license. Your license type: Familoop Safeguard Premium {n} ({n} devices).', ['{n}' => 3]);
            } else {
                $error_message = Util::_t('This device cannot be connected to your Parent Account because you have reached the maximum number of devices ({n}) for your type of license. Your license type: Familoop Safeguard Free ({n} devices).', ['{n}' => 3]);
            }
            $error_message .= "\n".Util::_t('You can either get a new license or close this window and delete one of the existing devices in your Parent Account on Familoop.com.');
            throw new ServerException($error_title, $error_message);
        }
        if ($n > 9) {
            $error_title = Util::_t('No available licenses');
            if (in_array($license['premium'], ['p10y', 'p10m'])) {
                $error_message = Util::_t('This device cannot be connected to your Parent Account because you have reached the maximum number of devices ({n}) for your type of license. Your license type: Familoop Safeguard Premium {n} ({n} devices).', ['{n}' => 10]);
            } else {
                $error_message = Util::_t('This device cannot be connected to your Parent Account because you have reached the maximum number of devices ({n}) for your type of license. Your license type: Familoop Safeguard Free ({n} devices).', ['{n}' => 3]);
            }
            $error_message .= "\n".Util::_t('You can either get a new license or close this window and delete one of the existing devices in your Parent Account on Familoop.com.');
            throw new ServerException($error_title, $error_message);
        }

        $items = Timezone::offsets();
        $offset = isset($items[$timezone])? $items[$timezone] * 3600 : $license['timezone'];

        if ($os == 'macos') {
            $os = 'mac';
        } else
        if ($os == 'ios') {
            $os = 'iphone';
        }

        RedisDB::instance()->sAdd('just-installed', $device_id);

        DB::instance()->execute('DELETE FROM serials WHERE device_id=? AND client_id=device_id', [$this->client_id]);

        return DB::instance()->execute('
        INSERT INTO serials
            (device_id, email, client_id, name, serial, created, expired, timezone, timezone_id, install, os, premium, is_confirmed)
        VALUES
            (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?, ?)
        ', [$device_id, $license['email'], $this->client_id, $name, $device_id, $license['created'], $license['expired'], $offset, $timezone, $os, $license['premium'], $license['is_confirmed']]);
    }

    public function get_assigned($device_id)
    {
        if (isset($this->settings[Client::CLIENT_SETTINGS_DEVICES]) && $this->settings[Client::CLIENT_SETTINGS_DEVICES]) {
            foreach ($this->settings[Client::CLIENT_SETTINGS_DEVICES] as $p) {
                if ((string)$device_id == (string)$p[0]) {
                    return isset($p[8][0]) && $p[8][0]? $p[8][0] : 0;
                }
            }
        }
        return 0;
    }

    public function set_assigned($device_id, $assigned = 1, $offset = 0)
    {
        if (isset($this->settings[Client::CLIENT_SETTINGS_DEVICES]) && $this->settings[Client::CLIENT_SETTINGS_DEVICES]) {
            foreach ($this->settings[Client::CLIENT_SETTINGS_DEVICES] as $index => $p) {
                if ((string)$device_id == (string)$p[0]) {
                    if (!isset($this->settings[Client::CLIENT_SETTINGS_DEVICES][$index][8]) || !is_array($this->settings[Client::CLIENT_SETTINGS_DEVICES][$index][8])) {
                        $this->settings[Client::CLIENT_SETTINGS_DEVICES][$index][8] = [];
                    }
                    $this->settings[Client::CLIENT_SETTINGS_DEVICES][$index][8][0] = $assigned? time() + $offset : 0;
                }
            }
        }
        return false;
    }

    public function lock_device($device_id, $account_id, $lock = true, $reason = '')
    {
        $now = time();
        foreach ($this->settings[Client::CLIENT_SETTINGS_DEVICES] as $index => $device) {
            if ((string)$device_id == (string)$device[0]) {
                $this->settings[Client::CLIENT_SETTINGS_DEVICES][$index][6][3] = $lock? 1 : 0;
                $this->settings[Client::CLIENT_SETTINGS_DEVICES][$index][6][4] = $now;
                $this->settings[Client::CLIENT_SETTINGS_DEVICES][$index][6][5] = $reason;
                //if (!$lock && strtolower($reason) == 'set_pin_locally') {

                $accounts = isset($this->settings[Client::CLIENT_SETTINGS_DEVICES][$index][1])? $this->settings[Client::CLIENT_SETTINGS_DEVICES][$index][1] : [];
                $keys = array_map(function($a) use ($device_id) {$parts = explode('|', $a); return 'events.'.$device_id.'.'.$parts[0];}, $accounts);
                //$keys = RedisDB::instance()->keys('events.'.$device_id.'.*');
                $redis_data = [];

                if (!$lock) {
                    $this->settings[Client::CLIENT_SETTINGS_DEVICES][$index][6][0] = 0;
                    $this->settings[Client::CLIENT_SETTINGS_DEVICES][$index][6][1] = $now;
                    RedisDB::instance()->hDel('timeout', $device_id);
                    if (strtolower($reason) == 'set_pin_locally') {
                        foreach ($keys as $key) {
                            $parts = explode('.', $key);
                            if ((string)$device_id != (string)$parts[1]) continue;

                            if ($p = RedisDB::instance()->get($key)) {
                                $data = JSON::decode($p);
                            } else {
                                $data = [];
                            }
                            $redis_data[$key] = $data;

                            $data['timeout-pin'] = [
                                'n' => 1,
                                'days' => [Date::to_days()],
                                'ids' => [$parts[1]],
                            ];
                            RedisDB::instance()->set($key, JSON::encode($data));
                            $redis_data[$key] = $data;
                        }

                        RedisDB::instance()->hSet('timeout', $device_id, JSON::encode([$this->client_id, 'pin', $now]));
                    }
                } else {
                    if ($p = RedisDB::instance()->hGet('timeout', $device_id)) {
                        $data = JSON::decode($p);
                        $data[4] = $now;
                        RedisDB::instance()->hSet('timeout', $device_id, JSON::encode($data));
                    }
                }
                foreach ($keys as $key) {
                    $parts = explode('.', $key);
                    if ((string)$device_id != (string)$parts[1]) continue;

                    if (!isset($redis_data[$key])) {
                        $p = RedisDB::instance()->get($key);
                        $data = JSON::decode($p);
                    } else {
                        $data = $redis_data[$key];
                    }
                    $found = false;
                    if (isset($data['timeout-on'])) {
                        unset($data['timeout-on']);
                        $found = true;
                    }
                    if (isset($data['timeout-off'])) {
                        unset($data['timeout-off']);
                        $found = true;
                    }
                    if ($found) {
                        RedisDB::instance()->set($key, JSON::encode($data));
                    }
                }
                return true;
            }
        }
        return false;
    }

    public function update_serials($params)
    {
        $rebill = $params['rebill'];
        $sn = $params['sn'];
        $expired = $params['expired'];
        $product = $params['product'];

        DB::instance()->execute('UPDATE serials SET notify=0, rebill=?, serial=?, expired=?, premium=?, is_confirmed=? WHERE client_id=?',
                                [$rebill, $sn, $expired, $product, $this->is_confirmed, $this->client_id]);

        $devices = DB::instance()->fetch('SELECT device_id FROM serials WHERE client_id=? AND client_id != device_id', [$this->client_id]);
        if ($devices) {
            DB::instance()->execute('DELETE FROM serials WHERE client_id=? AND client_id=device_id', [$this->client_id]);
        }
    }

    private function add_fake_license()
    {
        $now = time();
        DB::instance()->execute('INSERT INTO serials (
            device_id ,
            email ,
            name ,
            client_id ,
            serial ,
            did ,
            created ,
            expired ,
            last_visit ,
            last_login ,
            ver ,
            timezone ,
            os ,
            os_version ,
            rebill ,
            premium ,
            notify ,
            install ,
            test ,
            is_confirmed ,
            data
            ) VALUES (?,  ?,  "",  ?,  ?,  "",  ?,  ?,  0,  0,  "",  "",  "",  "",  ?,  ?,  0,  1,  0,  ?, "")',
        [$this->client_id, $this->email, $this->client_id, $this->client_id, $now, $now + 10*86400, '', '10', $this->_is_confirmed]);
    }
}