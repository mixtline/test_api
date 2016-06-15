<?php
/**
 * @param $server Server
 * @return array
 * @throws ServerException
 */
function getDeviceHubStatus($server)
{
    $device_id = $server->agentId;
    if (!$device_id) {
        return [];
    }

    $device = new Device($device_id);
    if (!$device->added) {
        return ['isAddedToHub' => false];
    }

    $on = $device->enabled;

    $status = [];
    if ($device->info['expired'] < time()) {
        $status['isValid'] = false;
        $status['invalidReason'] = Util::_t('Reached expiration date');
    } else {
        $status['isValid'] = true;
    }
    $status['expirationDate'] = (int)$device->info['expired'];

    $info = [
        'agentId' => $device_id,
        'os' => $device->os,
        'userDefinedName' => $device->info['name'],
        'userDefinedTimeZone' => ['regionId' => $device->info['timezone_id']? $device->info['timezone_id'] : 'GMT']
    ];

    return [
        'isAddedToHub' => true,
        'isEnabledOnHub' => $on,
        'rootUrl' => Util::get_root_url($device->client_id),
        'licenseStatus' => $status,
        'deviceInfo' => $info,
    ];
}

/**
 * @param $server Server
 * @return array
 * @throws ServerException
 */
function isDeviceEnabledOnHub($server)
{
    $device_id = $server->agentId;
    if (!$device_id) {
        throw new ServerException(Util::_t('Invalid request'), Util::_t('Not a valid device Id.'));
    }

    $server->check_authorization();

    $device = new Device($device_id);
    if (!$device->info) {
        throw new ServerException(Util::_t('Invalid request'), Util::_t('No device found.'));
    }

    $client = new Client($server->client_id);

    $found = array_filter($client->users_as_array, function($a) use ($device_id) {return isset($a[0][$device_id]) || isset($a[0]['-'.$device_id]);});
    if (sizeof($found) > 1) {
        throw new ServerException(Util::_t('Error'), Util::_t('Multiple users are assigned to the device.'));
    }

    return ['isEnabled' => $device->enabled];
}

/**
 * @param $server Server
 * @return array
 * @throws ServerException
 */
function setDeviceEnabledOnHub($server)
{
    $device_id = $server->agentId;
    if (!$device_id) {
        throw new ServerException(Util::_t('Invalid request'), Util::_t('Not a valid device Id.'));
    }
    $on = $server->get_bool('isEnabled', true);

    $server->check_authorization();

    $device = new Device($device_id);
    if (!$device->info) {
        throw new ServerException(Util::_t('Invalid request'), Util::_t('No device found.'));
    }

    $client = new Client($server->client_id);

    $found = array_filter($client->devices_as_array, function($a) use ($device_id) {return (string)$a[0] == $device_id;});
    if (!$found) {
        throw new ServerException(Util::_t('Invalid request'), Util::_t('No device found.'));
    }

    if (!$client->users_as_array) {
        throw new ServerException(Util::_t('Invalid request'), Util::_t('No children found.'));
    }

    $found = false;
    foreach ($client->users_as_array as $user_id => $user) {
        if (isset($user[0][$device_id])) {
            if (!in_array('all', $user[0][$device_id]) && !in_array($device_id, $user[0][$device_id])) {
                throw new ServerException(Util::_t('Error'), Util::_t('This is not a personal device.'));
            }
            if (!$on) {
                $client->set_device_status($user_id, $device_id, false);
                $client->save();
                $client->regenerate_xml();
            }
            $found = true;
        }
        if (isset($user[0]['-'.$device_id])) {
            if (!in_array('all', $user[0]['-'.$device_id]) && !in_array($device_id, $user[0]['-'.$device_id])) {
                throw new ServerException(Util::_t('Error'), Util::_t('This is not a personal device.'));
            }
            if ($on) {
                $client->set_device_status($user_id, $device_id, true);
                $client->save();
                $client->regenerate_xml();
            }
            $found = true;
        }
    }

    if ($found) {
        return [];
    }
    throw new ServerException(Util::_t('Error'), Util::_t('No device found.'));
}

/**
 * @param $server Server
 * @return array
 * @throws ServerException
 */
function getDeviceInfo($server)
{
    $device_id = $server->agentId;
    if (!$device_id) {
        throw new ServerException(Util::_t('Invalid request'), Util::_t('Not a valid device Id.'));
    }

    $server->check_authorization();

    $device = new Device($device_id);
    if (!$device->info) {
        throw new ServerException(Util::_t('Invalid request'), Util::_t('No device found.'));
    }

    $info = [
        'agentId' => $device_id,
        'os' => $device->os,
        'userDefinedName' => $device->info['name'],
        'userDefinedTimeZone' => ['regionId' => $device->info['timezone_id']? $device->info['timezone_id'] : 'GMT']
    ];

    return $info;
}

/**
 * @param $server Server
 * @return array
 * @throws ServerException
 */
function setDeviceInfo($server)
{
    $device_id = $server->deviceInfo['agentId'];
    if (!$device_id) {
        throw new ServerException(Util::_t('Invalid request'), Util::_t('Not a valid device Id.'));
    }
    $device_name = isset($server->deviceInfo['userDefinedName'])? $server->deviceInfo['userDefinedName'] : false;
    $os = isset($server->deviceInfo['os'])? $server->deviceInfo['os'] : false;
    if ($os !== false) {
        if ($os == 'macos') {
            $os = 'mac';
        } else
        if ($os == 'ios') {
            $os = 'iphone';
        }
    }
    $timezone = isset($server->deviceInfo['userDefinedTimeZone']['regionId'])? $server->deviceInfo['userDefinedTimeZone']['regionId'] : false;

    if (!$device_name) {
        throw new ServerException(Util::_t('Invalid request'), Util::_t('Device name is not valid.'));
    }
    /*
    if (!$timezone) {
        throw new ServerException(Util::_t('Invalid request'), Util::_t('Not a valid device zone.'));
    }
    if (!$os || !in_array($os, Util::allowed_os())) {
        throw new ServerException(Util::_t('Invalid request'), Util::_t('Not a valid device os.'));
    }
    */

    $server->check_authorization();

    $device = new Device($device_id);
    if (!$device->info) {
        throw new ServerException(Util::_t('Invalid request'), Util::_t('No device found.'));
    }
    if (strtolower(trim($device_name)) != strtolower(trim($device->name))) {
        if (in_array(strtolower(trim($device_name)), get_devices_names($device->client_id))) {
            throw new ServerException(Util::_t('Invalid request'), Util::_t('There is another device with the same name.'));
        }
    }

    $items = Timezone::offsets();
    $offset = isset($items[$timezone])? $items[$timezone] * 3600 : $device->info['timezone'];

    DB::instance()->execute('UPDATE serials SET name=?, timezone=?, timezone_id=?, os=? WHERE device_id=?',
                            [
                                $device_name !== false? $device_name : $device->info['name'],
                                $offset,
                                $timezone !== false? $timezone : $device->info['timezone_id'],
                                $os !== false? $os : $device->info['os'],
                                $device_id
                            ]);
    return [];
}


/**
 * @param $server Server
 * @return array
 * @throws ServerException
 */
function assignDeviceToChild($server)
{
    $device_id = $server->agentId;
    $user_id = $server->childId;
    $reassign = $server->get_bool('reassign', true);

    if (!$device_id) {
        throw new ServerException(Util::_t('Invalid request'), Util::_t('Not a valid device Id.'));
    }
    if ($user_id === false) {
        throw new ServerException(Util::_t('Invalid request'), Util::_t('Not a valid child Id.'));
    }

    $server->check_authorization();

    $device = new Device($device_id);
    if (!$device->info) {
        throw new ServerException(Util::_t('Invalid request'), Util::_t('No device found.'));
    }

    $client = new Client($server->client_id);

    $found = array_filter($client->devices_as_array, function($a) use ($device_id) {return (string)$a[0] == $device_id;});
    if (!$found) {
        throw new ServerException(Util::_t('Invalid request'), Util::_t('No device found.'));
    }

    if (!$client->users_as_array) {
        throw new ServerException(Util::_t('Invalid request'), Util::_t('No children found.'));
    }

    if (!isset($client->users_as_array[$user_id])) {
        throw new ServerException(Util::_t('Invalid request'), Util::_t('No child found.'));
    }

    if (!$reassign) {
        $owners = $device->owners;
        if ($owners > 1 || !in_array($user_id, $owners)) {
            throw new ServerException(Util::_t('Error'), Util::_t('The device is assigned to another child.'));
        }
    }

    $client->assign_device($user_id, $device_id, true, $reassign);
    $client->save();

    return [];
}

/**
 * @param $server Server
 * @return array
 * @throws ServerException
 */
function getChildrenList($server)
{
    $server->check_authorization();

    $client = new Client($server->client_id);
    $users = [];
    foreach ($client->users as $user_id => $user) {
        $data = [
            'id' => (string)$user_id,
            'name' => $user->name,
            'gender' => $user->gender,
            'birthDate' => $user->birth_data,
            'avatar' => $user->avatar_data,
        ];
        $users[] = $data;
    }


    return ['children' => $users];
}


/**
 * @param $server Server
 * @return array
 * @throws ServerException
 */
function addChild($server)
{
    $name = isset($server->child['name'])? (string)$server->child['name'] : false;
    $gender = isset($server->child['gender'])? (string)$server->child['gender'] : false;
    $year = isset($server->child['birthDate']['year'])? (int)$server->child['birthDate']['year'] : false;
    $month = isset($server->child['birthDate']['month'])? (int)$server->child['birthDate']['month'] : false;
    $day = isset($server->child['birthDate']['day'])? (int)$server->child['birthDate']['day'] : false;

    if (!$name) {
        throw new ServerException(Util::_t('Invalid request'), Util::_t('Not a valid child name.'));
    }
    if (!in_array($gender, ['male', 'female'])) {
        throw new ServerException(Util::_t('Invalid request'), Util::_t('Not a valid child gender.'));
    }
    if (!$year) {
        throw new ServerException(Util::_t('Invalid request'), Util::_t('Not a valid child year birth.'));
    }
    if (!$month) {
        throw new ServerException(Util::_t('Invalid request'), Util::_t('Not a valid child month birth.'));
    }
    if (!$day) {
        throw new ServerException(Util::_t('Invalid request'), Util::_t('Not a valid child day birth.'));
    }

    $server->check_authorization();

    $birth = mktime(0, 0, 0, $month, $day, $year);
    if ($birth > time()) {
        throw new ServerException(Util::_t('Invalid request'), Util::_t('Invalid Date of Birth. It cannot be in the future.'));
    }

    $avatar = $server->avatar();


    $client = new Client($server->client_id);
    $user_id = $client->add_user([], $name, $avatar, $birth, $gender);
    $client->save();
    $client->regenerate_xml();

    return ['childId' => (string)$user_id];
}


/**
 * @param $server Server
 * @return array
 * @throws ServerException
 */
function getAssignedToDeviceChildId($server)
{
    $device_id = $server->agentId;

    $server->check_authorization();

    $device = new Device($server->device_id);
    if (!$device->info) {
        return [];
    }

    $client = new Client($server->client_id);

    $user_id = false;
    foreach ($client->users_as_array as $uid => $user) {
        if (isset($user[0][$device_id]) && in_array('all', $user[0][$device_id])) {
            if ($user_id === false) {
                $user_id = $uid;
            } else {
                $user_id = -1;
            }
        }
        if (isset($user[0]['-'.$device_id]) && in_array('all', $user[0]['-'.$device_id])) {
            if ($user_id === false) {
                $user_id = $uid;
            } else {
                $user_id = -1;
            }
        }
    }

    if ($user_id === false) {
        return [];
    }
    if ($user_id < 0) {
        throw new ServerException(Util::_t('Error'), Util::_t('Multiple users are assigned to the device.'));
    }
    return ['childId' => (string)$user_id];
}


/**
 * @param $server Server
 * @return array
 * @throws ServerException
 */
function addNewEnabledDeviceAndAssignNewChild($server)
{
    $device_id = isset($server->deviceInfo['agentId'])? $server->deviceInfo['agentId'] : false;
    if (!$device_id) {
        throw new ServerException(Util::_t('Invalid request'), Util::_t('Not a valid device Id.'));
    }
    $device_name = isset($server->deviceInfo['userDefinedName'])? $server->deviceInfo['userDefinedName'] : false;
    $os = isset($server->deviceInfo['os'])? $server->deviceInfo['os'] : false;
    $timezone = isset($server->deviceInfo['userDefinedTimeZone']['regionId'])? $server->deviceInfo['userDefinedTimeZone']['regionId'] : false;
    if (!$device_name) {
        throw new ServerException(Util::_t('Invalid request'), Util::_t('Device name is not valid.'));
    }
    if (!$timezone) {
        throw new ServerException(Util::_t('Invalid request'), Util::_t('Not a valid device zone.'));
    }
    if (!$os || !in_array($os, Util::allowed_os())) {
        throw new ServerException(Util::_t('Invalid request'), Util::_t('Not a valid device os.'));
    }

    if (!$device_name) {
        throw new ServerException(Util::_t('Invalid request'), Util::_t('Device name is not valid.'));
    }

    $name = isset($server->child['name'])? (string)$server->child['name'] : false;
    $gender = isset($server->child['gender'])? (string)$server->child['gender'] : false;
    $year = isset($server->child['birthDate']['year'])? (int)$server->child['birthDate']['year'] : false;
    $month = isset($server->child['birthDate']['month'])? (int)$server->child['birthDate']['month'] : false;
    $day = isset($server->child['birthDate']['day'])? (int)$server->child['birthDate']['day'] : false;

    if (!$name) {
        throw new ServerException(Util::_t('Invalid request'), Util::_t('Not a valid child name.'));
    }
    if (!in_array($gender, ['male', 'female'])) {
        throw new ServerException(Util::_t('Invalid request'), Util::_t('Not a valid child gender.'));
    }
    if (!$year) {
        throw new ServerException(Util::_t('Invalid request'), Util::_t('Not a valid child year birth.'));
    }
    if (!$month) {
        throw new ServerException(Util::_t('Invalid request'), Util::_t('Not a valid child month birth.'));
    }
    if (!$day) {
        throw new ServerException(Util::_t('Invalid request'), Util::_t('Not a valid child day birth.'));
    }

    $server->check_authorization();

    $device = new Device($device_id);
    if ($device->info) {
        throw new ServerException(Util::_t('Invalid request'), Util::_t('There is another device with the same agent Id.'));
    }
    if (in_array(strtolower(trim($device_name)), get_devices_names($device->client_id))) {
        throw new ServerException(Util::_t('Invalid request'), Util::_t('There is another device with the same name.'));
    }

    $birth = mktime(0, 0, 0, $month, $day, $year);
    if ($birth > time()) {
        throw new ServerException(Util::_t('Invalid request'), Util::_t('Invalid Date of Birth. It cannot be in the future.'));
    }
    $avatar = $server->avatar();

    $client = new Client($server->client_id);
    $user_id = $client->add_user([$device_id => ['all']], $name, $avatar, $birth, $gender);
    $client->add_device($device_id, $device_name, $os, $timezone);
    $client->save();
    $client->regenerate_xml();

    if ($did = $server->pushNotificationToken) {
        $device->set_did($did);
    }
    if (!($token = $device->token)) {
        $token = $device->generate_token();
    }

    $device->assigned = true;

    return [
        'childId' => (string)$user_id,
        'rootUrl' => Util::get_root_url($server->client_id),
        'deviceToken' => $token
    ];
}

/**
 * @param $server Server
 * @return array
 * @throws ServerException
 */
function addNewEnabledDeviceAndAssignExistingChild($server)
{
    $device_id = isset($server->deviceInfo['agentId'])? $server->deviceInfo['agentId'] : false;
    if (!$device_id) {
        throw new ServerException(Util::_t('Invalid request'), Util::_t('Not a valid device Id.'));
    }
    $device_name = isset($server->deviceInfo['userDefinedName'])? $server->deviceInfo['userDefinedName'] : false;
    $os = isset($server->deviceInfo['os'])? $server->deviceInfo['os'] : false;
    $timezone = isset($server->deviceInfo['userDefinedTimeZone']['regionId'])? $server->deviceInfo['userDefinedTimeZone']['regionId'] : false;
    if (!$device_name) {
        throw new ServerException(Util::_t('Invalid request'), Util::_t('Device name is not valid.'));
    }
    if (!$timezone) {
        throw new ServerException(Util::_t('Invalid request'), Util::_t('Not a valid device zone.'));
    }
    if (!$os || !in_array($os, Util::allowed_os())) {
        throw new ServerException(Util::_t('Invalid request'), Util::_t('Not a valid device os.'));
    }

    $server->check_authorization();

    $device = new Device($device_id);
    if ($device->info) {
        throw new ServerException(Util::_t('Invalid request'), Util::_t('There is another device with the same agent Id.'));
    }
    if (in_array(strtolower(trim($device_name)), get_devices_names($device->client_id))) {
        throw new ServerException(Util::_t('Invalid request'), Util::_t('There is another device with the same name.'));
    }

    $user_id = $server->childId;
    $client = new Client($server->client_id);
    if (!isset($client->users_as_array[$user_id])) {
        throw new ServerException(Util::_t('Invalid request'), Util::_t('No child found.'));
    }

    $client->add_device($device_id, $device_name, $os, $timezone);
    $client->assign_device($user_id, $device_id, true, true);
    $client->save();
    $client->regenerate_xml();

    if ($did = $server->pushNotificationToken) {
        $device->set_did($did);
    }
    if (!($token = $device->token)) {
        $token = $device->generate_token();
    }

    $device->assigned = true;

    return [
        'deviceToken' => $token,
        'rootUrl' => Util::get_root_url($server->client_id),
    ];
}

/**
 * @param $server Server
 * @return array
 */
function iOS_getDeviceMdmStatus($server)
{
    $device_id = $server->agentId;
    if (!$device_id) {
        throw new ServerException(Util::_t('Invalid request'), Util::_t('Not a valid device Id.'));
    }

    $mdm = new Mdm($device_id);

    return ['isProfileInstalled' => $mdm->installed];
}

/**
 * @param $server Server
 * @return array
 */
function getHubSessionLinks($server)
{
    $server->check_authorization();

    $config = Util::config();
    $token = uniqid(rand(), true);
    RedisDB::instance()->set('hub.'.$token, json_encode(['device_id' => $server->device_id, 'client_id' => $server->client_id]), 3600);

    return [
        'fineTune' => ['url' => $config['params']['family_url'].'/hub/?token='.$token.'&path=activities#settings1'],
        'visitHub' => ['url' => $config['params']['family_url'].'/hub/?token='.$token.'&path=insights']
    ];
}


/**
 * @param $server Server
 * @return array
 */
function getDeviceToken($server)
{
    $device_id = $server->agentId;
    if (!$device_id) {
        throw new ServerException(Util::_t('Invalid request'), Util::_t('Not a valid device Id.'));
    }

    $server->check_authorization();

    $device = new Device($device_id);
    if (!$device->info) {
        throw new ServerException(Util::_t('Invalid request'), Util::_t('No device found.'));
    }

    if (!($token = $device->token)) {
        $token = $device->generate_token();
    }

    return ['deviceToken' => $token];
}

/**
 * @param $server Server
 * @return array
 */
function setPushNotificationToken($server)
{
    $token = $server->deviceToken;
    if (!$token) {
        throw new ServerException(Util::_t('Invalid request'), Util::_t('Not a valid device token.'));
    }

    $device = Device::get_device_from_token($token);
    if (!$device || !$device->info) {
        throw new ServerException(Util::_t('Invalid request'), Util::_t('No device found.'));
    }

    if ($did = $server->pushNotificationToken) {
        $device->set_did($did);
    }

    return [];
}

/**
 * @param $server Server
 * @return array
 */
function getPushNotificationToken($server)
{
    $device_id = $server->agentId;
    if (!$device_id) {
        throw new ServerException(Util::_t('Invalid request'), Util::_t('Not a valid device Id.'));
    }

    $server->check_authorization();

    $device = new Device($device_id);
    if (!$device->info) {
        throw new ServerException(Util::_t('Invalid request'), Util::_t('No device found.'));
    }

    return ['pushNotificationToken' => $device->did];
}

/**
 * @param $server Server
 * @return array
 */
function getChildContract($server)
{
    $token = $server->deviceToken;
    if (!$token) {
        throw new ServerException(Util::_t('Invalid request'), Util::_t('Not a valid device token.'));
    }

    //$server->check_authorization();

    $device = Device::get_device_from_token($token);
    if (!$device || !$device->info) {
        throw new ServerException(Util::_t('Invalid request'), Util::_t('No device found.'));
    }

    $client = new Client($device->client_id);

    foreach ($client->users as $user_id => $user) {
        if (isset($user->devices[$device->device_id]) || isset($user->devices['-'.$device->device_id])) {
            $result = [
                'deviceName' => $device->name,
                'childName' => $user->name,
                'childGender' => $user->gender,
            ];
            $result['ratings'] = [];
            $os = $device->os_short;
            if ($os == 'iphone') {
                $monitor = $user->monitor;
                $block = [];
                if ($monitor['iphone_itunes']) {
                    $block[] = 'iTunes';
                }
                if ($monitor['iphone_ibooks']) {
                    $block[] = 'iBook Store';
                }
                $result['ratings'] = [
                    [
                        'name' => 'Apps',
                        'value' => $user->rating_on($os, 'app')? User::rating_names()[$os]['app'][$user->rating($os, 'app')] : Util::_t('Allow all')
                    ],
                    [
                        'name' => 'Movies',
                        'value' => $user->rating_on($os, 'movie')? User::rating_names()[$os]['movie'][$user->rating($os, 'movie')] : Util::_t('Allow all')
                    ],
                    [
                        'name' => 'TV Shows',
                        'value' => $user->rating_on($os, 'tv')? User::rating_names()[$os]['tv'][$user->rating($os, 'tv')] : Util::_t('Allow all')
                    ],
                    [
                        'name' => 'Block explicit content in',
                        'value' => implode(', ', $block)
                    ]
                ];
            } else {
                $result['ratings'] = [
                    [
                        'name' => 'Apps',
                        //'value' => User::rating_names()[$os][$user->rating($os)]
                        'value' => $user->rating_on($os)? $user->rating($os) : Util::_t('Allow all')
                    ]
                ];
            }
            return $result;
        }
    }

    throw new ServerException(Util::_t('Invalid request'), Util::_t('No child found.'));
}


/**
 * @param $server Server
 * @return array
 */
function getScreenTime($server)
{
    $token = $server->deviceToken;
    if (!$token) {
        throw new ServerException(Util::_t('Invalid request'), Util::_t('Not a valid device token.'));
    }

    $device_id = $server->agentId;
    if (!$device_id) {
        throw new ServerException(Util::_t('Invalid request'), Util::_t('Not a valid device Id.'));
    }

    $device = new Device($device_id);
    if (!$device->info) {
        throw new ServerException(Util::_t('Invalid request'), Util::_t('No device found.'));
    }

    $range = $server->range;
    $result = [
        'range' => $range,
        'screenTime' => [],
    ];
    $config = Util::config();
    $screentime = new Screentime($device_id, $device_id);
    $screentime->day_offset = $device->timezone - $config['params']['server_timezone']*3600;
    //$screentime = new Screentime($device_id, 'alex');
    $period = [$range['begin'], $range['end']];

    $result['screenTime']['webBrowsing'] = $screentime->duration($period, 'web');
    $result['screenTime']['calls'] = $screentime->duration($period, 'call');
    $result['screenTime']['messages'] = $screentime->duration($period, 'messages');
    $result['screenTime']['social'] = $screentime->duration($period, 'social');
    $result['screenTime']['video'] = $screentime->duration($period, 'video');
    $result['screenTime']['music'] = $screentime->duration($period, 'music');
    $result['screenTime']['games'] = $screentime->duration($period, 'game');
    $result['screenTime']['other'] = $screentime->duration($period, 'other');

    return $result;
}

function get_devices_names($client_id)
{
    $items = DB::instance()->fetchAll('SELECT name FROM serials WHERE client_id=?', [$client_id]);
    $names = [];
    foreach ($items as $item) {
        $name = strtolower(trim($item['name']));
        if ($name && !in_array($name, $names)) {
            $names[] = $name;
        }
    }
    return $names;
}


/**
 * @param $server Server
 * @return array
 */
function deviceLockStateReport($server)
{
    $device_id = $server->agentId;
    if (!$device_id) {
        throw new ServerException(Util::_t('Invalid request'), Util::_t('Not a valid device Id.'));
    }
    $account_id = $server->accountId;
    $lock = $server->isLocked;
    $reason = $server->reason;

    $device = new Device($device_id);
    if (!$device->info) {
        throw new ServerException(Util::_t('Invalid request'), Util::_t('No device found.'));
    }

    $client = new Client($device->client_id);
    if ($client->lock_device($device_id, $account_id, $lock, $reason)) {
        $client->save();
    }

    return [];
}

/**
 * @param $server Server
 * @return array
 */
function webGrabberReport($server)
{
    $device_id = $server->agentId;
    if (!$device_id) {
        throw new ServerException(Util::_t('Invalid request'), Util::_t('Not a valid device Id.'));
    }
    $account_id = $server->accountId;

    $profile = $server->profile;
    $error = $server->error;

    $device = new Device($device_id);
    if (!$device->info) {
        throw new ServerException(Util::_t('Invalid request'), Util::_t('No device found.'));
    }

    $account = new Account($device->client_id, $device_id, $account_id);
    if ($account->set_account($profile, $error)) {
        $account->save();
    }

    return [];
}

/**
 * @param $server Server
 * @return array
 */
function webCredentialsReport($server)
{
    return webGrabberReport($server);
}