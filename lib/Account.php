<?php

class Account
{
    const ACCOUNT_FRIENDS = 0;
    const ACCOUNT_PROFILES = 1;
    const ACCOUNT_CONTACTS = 2;
    const ACCOUNT_APPS = 3;
    const ACCOUNT_PHOTOS = 4;

    const SOCIAL_NETWORK_FACEBOOK = 'fb';
    const SOCIAL_NETWORK_BADOO = 'b';
    const SOCIAL_NETWORK_GOOGLE = 'google';
    const SOCIAL_NETWORK_TWITTER = 'twitter';
    const SOCIAL_NETWORK_FLICKR = 'fl';
    const SOCIAL_NETWORK_YOUTUBE = 'youtube';
    const SOCIAL_NETWORK_HULU = 'hulu';
    const SOCIAL_NETWORK_INSTAGRAM = 'ig';
    const SOCIAL_NETWORK_MYSPACE = 'ms';
    const SOCIAL_NETWORK_LINKEDIN = 'in';
    const SOCIAL_NETWORK_TUMBLR = 't';
    const SOCIAL_NETWORK_PINTEREST = 'pin';
    const SOCIAL_NETWORK_VIMEO = 'v';

    public $client_id;
    public $info;
    public $device_id;
    public $account_id;

    public function __construct($client_id, $device_id, $account_id)
    {
        $this->client_id = $client_id;
        $this->device_id = $device_id;
        $this->account_id = $account_id;
        $row = DB::instance()->fetch('SELECT * FROM accounts WHERE alfa=? AND client_id=? AND device_id=? AND account_id=?', [ord($client_id), $client_id, $device_id, $account_id]);
        if (isset($row['data']) && $row['data']) {
            $this->info = JSON::decode($row['data']);
        }
        if (!$this->info) {
            $this->info = [];
        }
    }

    public function __get($name)
    {
        if (isset($this->info[$name])) {
            return $this->info[$name];
        }
    }

    public function set_account($profile, $error = [])
    {
        $profile_id = isset($profile['id'])? $profile['id'] : false;
        $network = isset($profile['type'])? $profile['type'] : false;
        if (!$profile_id || !$network) return false;

        if (in_array(strtolower($network), ['fb', 'facebook'])) {
            $network = Account::SOCIAL_NETWORK_FACEBOOK;
        } else
        if (in_array(strtolower($network), ['badoo'])) {
            $network = Account::SOCIAL_NETWORK_BADOO;
        } else
        if (in_array(strtolower($network), ['flickr'])) {
            $network = Account::SOCIAL_NETWORK_FLICKR;
        } else
        if (in_array(strtolower($network), ['googleplus'])) {
            $network = Account::SOCIAL_NETWORK_GOOGLE;
        } else
        if (in_array(strtolower($network), ['twitter'])) {
            $network = Account::SOCIAL_NETWORK_TWITTER;
        } else
        if (in_array(strtolower($network), ['myspace'])) {
            $network = Account::SOCIAL_NETWORK_MYSPACE;
        } else
        if (in_array(strtolower($network), ['linkedin'])) {
            $network = Account::SOCIAL_NETWORK_LINKEDIN;
        } else
        if (in_array(strtolower($network), ['tumblr'])) {
            $network = Account::SOCIAL_NETWORK_TUMBLR;
        } else
        if (in_array(strtolower($network), ['pinterest'])) {
            $network = Account::SOCIAL_NETWORK_PINTEREST;
        } else
        if (in_array(strtolower($network), ['youtube'])) {
            $network = Account::SOCIAL_NETWORK_YOUTUBE;
        } else
        if (in_array(strtolower($network), ['instagram'])) {
            $network = Account::SOCIAL_NETWORK_INSTAGRAM;
        } else
        if (in_array(strtolower($network), ['vimeo'])) {
            $network = Account::SOCIAL_NETWORK_VIMEO;
        } else {
            throw new ServerException(Util::_t('Invalid request'), Util::_t('Not a valid profile type.'));
        }
        $profiles = isset($this->info[Account::ACCOUNT_PROFILES])? $this->info[Account::ACCOUNT_PROFILES] : [];

        if (!isset($profiles[$network])) {
            $profiles[$network] = [];
        }

        $data = isset($profiles[$network][$profile_id])? $profiles[$network][$profile_id] : [0 => $profile_id];
        $data[10] = $network;
        $now = time();
        if ($error && isset($error['message']) && $error['message']) {
            $data[16] = [0, $now, $error['message']];
        } else {
            if (isset($data[16][2]) && $data[16][2] == 'data') {
                $data[16][0] = 1;
            } else {
                $data[16] = [1, $now];
            }
        }

        $profiles[$network][$profile_id] = $data;
        $this->info[Account::ACCOUNT_PROFILES] = $profiles;

        return true;
    }

    public function save()
    {
        $data = JSON::encode($this->info);
        DB::instance()->execute('INSERT IGNORE INTO accounts (alfa, client_id, device_id, account_id, data) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE data=?', [ord($this->client_id), $this->client_id, $this->device_id, $this->account_id, $data, $data]);

        MemcacheDB::instance()->delete('account'.$this->client_id);
        MemcacheDB::instance()->delete('client'.$this->client_id);
        MemcacheDB::instance()->delete('serials'.$this->client_id);
        RedisDB::instance()->delete('find_contacts.'.$this->client_id);
        RedisDB::instance()->delete('find_profiles.'.$this->client_id);
    }
}