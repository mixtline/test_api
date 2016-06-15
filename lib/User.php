<?php

class User
{
    /**
     * @var $client Client
     */
    public $client;
    public $user_id = -1;

    private $user = [];

    public function __construct($client, $user_id)
    {
        $this->client = $client;
        $this->user_id = $user_id;
        $this->user = isset($client->settings[1][$user_id])? $client->settings[1][$user_id] : [];
    }

    public function __get($name)
    {
        if ($name == 'name') {
            return isset($this->user[1])? $this->user[1] : '';
        } else
        if ($name == 'gender') {
            return (isset($this->user[4]) && $this->user[4] == 'f')? 'female' : 'male';
        } else
        if ($name == 'birth') {
            return isset($this->user[3])? $this->user[3] : 0;
        } else
        if ($name == 'birth_data') {
            return [
                'year' => (int)date('Y', $this->birth),
                'month' => (int)date('n', $this->birth),
                'day' => (int)date('d', $this->birth),
            ];
        } else
        if ($name == 'age') {
            return Date::smart_age_format($this->birth);
        } else
        if ($name == 'devices') {
            return isset($this->user[0])? $this->user[0] : [];
        } else
        if ($name == 'avatar') {
            return isset($this->user[2])? $this->user[2] : 'm';
        } else
        if ($name == 'avatar_data') {
            if (in_array($this->avatar, Util::allowed_avatars())) {
                return [
                    'type' => 'predefined',
                    'data' => ['predefinedId' => $this->avatar],
                ];
            } else {
                $config = Util::config();
                $path = Util::get_avatar_path($this->avatar);
                $format = 'png';
                $md5 = '';
                $avatar = $path . DIRECTORY_SEPARATOR . $this->avatar;
                if (file_exists($avatar)) {
                    $p = getimagesize($avatar);
                    $format = str_replace('image/', '', strtolower($p['mime']));
                    $md5 = hash('md5', file_get_contents($avatar, FILE_BINARY));
                }
                $first = strtolower(substr($this->avatar, 0, 1));
                return [
                    'type' => 'custom',
                    'data' => [
                        'format' => $format,
                        'md5' => $md5,
                        'url' => $config['params']['avatar_url'] . '/' . $first . '/' . $this->avatar,
                    ],
                ];
            }
        } else
        if ($name == 'monitor') {
            return isset($this->user[19])? $this->user[19] : User::get_monitor($this->age);
        }
    }

    public function has_device($device_id)
    {
        return isset($this->user[0][$device_id]) || isset($this->user[0]['-'.$device_id]);
    }


    public function rating_on($os, $type = 'app')
    {
        $settings = isset($this->user[13][0][1][0])?
            $this->user[13][0][1][0] : [];
        if ($os == 'iphone') {
            switch ($type) {
                case 'app': return !isset($settings[$os][0]) || (isset($settings[$os][0]) && $settings[$os][0] == '1')? 1 : 0;
                case 'movie': return !isset($settings[$os][1]) || (isset($settings[$os][1]) && $settings[$os][1] == '1')? 1 : 0;
                case 'tv': return !isset($settings[$os][2]) || (isset($settings[$os][2]) && $settings[$os][2] == '1')? 1 : 0;
                default: return 0;
            }
        } else {
            return isset($settings[$os])? $settings[$os] : 1;
        }
    }

    public function rating($os, $type = 'app')
    {
        $default_settings = User::default_ratings($this->age);
        $settings = isset($this->user[13][0][1][1])?
            $this->user[13][0][1][1] :
            $default_settings;
        if ($os == 'iphone') {
            if (strlen($settings[$os]) < 6) $settings = $default_settings;
            switch ($type) {
                case 'app': return isset($settings[$os])? (int)substr($settings[$os], 0, 2) : 0;
                case 'movie': return isset($settings[$os])? (int)substr($settings[$os], 2, 2) : 0;
                case 'tv': return isset($settings[$os])? (int)substr($settings[$os], 4, 2) : 0;
                default: return 0;
            }
        } else {
            return isset($settings[$os])? ($settings[$os]) : 0;
        }
    }

    public static function default_ratings($age)
    {
        $result = array(
            'android' => 0,
            'mac' => 0,
            'win' => 0,
            'iphone' => 0,
        );
        if ($age < 9) {
            $result['android'] = 4;
        } else
        if ($age < 13) {
            $result['android'] = 9;
        } else
        if ($age < 17) {
            $result['android'] = 12;
        } else $result['android'] = 17;

        if ($age < 9) {
            $result['mac'] = 4;
        } else
        if ($age < 13) {
            $result['mac'] = 9;
        } else
        if ($age < 17) {
            $result['mac'] = 12;
        } else $result['mac'] = 17;

        $result['iphone'] = '040404'; // app, movie, tv
        if ($age < 9) {
            $result['iphone'][0] = '0';
            $result['iphone'][1] = '4';
        } else
        if ($age < 12) {
            $result['iphone'][0] = '0';
            $result['iphone'][1] = '9';
        } else
        if ($age < 17) {
            $result['iphone'][0] = '1';
            $result['iphone'][1] = '2';
        } else {
            $result['iphone'][0] = '1';
            $result['iphone'][1] = '7';
        }
        if ($age < 10) {
            $result['iphone'][2] = '0';
            $result['iphone'][3] = '4';
        } else
        if ($age < 13) {
            $result['iphone'][2] = '1';
            $result['iphone'][3] = '0';
        } else
        if ($age < 16) {
            $result['iphone'][2] = '1';
            $result['iphone'][3] = '3';
        } else
        if ($age < 18) {
            $result['iphone'][2] = '1';
            $result['iphone'][3] = '6';
        } else {
            $result['iphone'][2] = '1';
            $result['iphone'][3] = '8';
        }
        if ($age < 7) {
            $result['iphone'][4] = '0';
            $result['iphone'][5] = '4';
        } else
        if ($age < 10) {
            $result['iphone'][4] = '0';
            $result['iphone'][5] = '7';
        } else
        if ($age < 12) {
            $result['iphone'][4] = '1';
            $result['iphone'][5] = '0';
        } else
        if ($age < 14) {
            $result['iphone'][4] = '1';
            $result['iphone'][5] = '2';
        } else
        if ($age < 17) {
            $result['iphone'][4] = '1';
            $result['iphone'][5] = '4';
        } else {
            $result['iphone'][4] = '1';
            $result['iphone'][5] = '7';
        }

        if ($age < 6) {
            $result['win'] = 3;
        } else
        if ($age < 10) {
            $result['win'] = 6;
        } else
        if ($age < 13) {
            $result['win'] = 10;
        } else
        if ($age < 17) {
            $result['win'] = 13;
        } else
        if ($age < 18) {
            $result['win'] = 17;
        } else $result['win'] = 18;

        return $result;
    }
    
    public static function rating_names()
    {
        return array(
            'win' => [
                3 => 'EC (3+)',
                6 => 'E (6+)',
                10 => 'E (10+)',
                13 => 'T (13+)',
                17 => 'M (17+)',
                18 => 'A (18+)',
            ],
            'mac' => [
                4 => '4+',
                9 => '9+',
                12 => '12+',
                17 => '17+',
            ],
            'iphone' => [
                'app' => [
                    4 => '4+',
                    9 => '9+',
                    12 => '12+',
                    17 => '17+',
                ],
                'movie' => [
                    4 => 'G',
                    10 => 'PG',
                    13 => 'PG-13',
                    16 => 'R',
                    18 => 'NC-17',
                ],
                'tv' => [
                    4 => 'TV-Y',
                    7 => 'TV-Y7',
                    10 => 'TV-G',
                    12 => 'TV-PG',
                    14 => 'TV-14',
                    17 => 'TV-MA',
                ]
            ],
            'android' => [
                4 => 'Everyone',
                9 => 'Low maturity',
                12 => 'Medium maturity',
                17 => 'High maturity',
            ],
        );
    }

    public static function get_monitor($age)
    {
        $result = [
            'post' => 1,
            'social' => '1111111111111',
            'chat' => '11111',
            'mail' => '1111',
            'web' => 1,
            'search' => 1,
            'call' => 1,
            'contacts' => 1,
            'sms' => 1,
            'camera' => 1,
            'apps' => 1,
            'gps' => 1,
            'android_web' => 1,
            'android_search' => 1,
            'android_call' => 1,
            'android_contacts' => 1,
            'android_sms' => 1,
            'android_camera' => 1,
            'android_apps' => 1,
            'android_gps' => 1,
            'iphone_web' => 1,
            'iphone_search' => 1,
            'iphone_contacts' => 1,
            'iphone_camera' => 1,
            'iphone_apps' => 1,
            'iphone_gps' => 1,
            'iphone_itunes' => 1,
            'iphone_ibooks' => 1,
        ];

        if ($age > 12) {
            $result['iphone_itunes'] = 0;
            $result['iphone_ibooks'] = 0;
        }

        return $result;
    }

}