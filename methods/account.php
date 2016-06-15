<?php
function createAccount($server)
{
    Client::create($server->email, $server->name, $server->password);
    return [];
}
function resetPassword($server)
{
    Client::reset_password($server->email);
    return [];
}

/**
 * @param $server Server
 * @return array
 */
function loginAsParent($server)
{
    $client = Client::login($server->email, $server->password);
    $token = $server->authorize($client, $server->agentId);
    return [
        'authToken' => $token,
        'client' => [
            'id' => $client->client_id,
            'email' => $server->email,
            'first_name' => $client->fname,
            'last_name' => $client->lname,
            'language' => $client->lang,
            'is_confirmed' => $client->is_confirmed? true : false,
        ]
    ];
}

function checkParentCredentials($server)
{
    $device_id = $server->agentId;
    if (!$device_id) {
        throw new ServerException(Util::_t('Invalid request'), Util::_t('Not a valid device Id.'));
    }

    $client = Client::login($server->email, $server->password);
    $found = array_filter($client->devices_as_array, function($a) use ($device_id) {return (string)$a[0] == $device_id;});
    if (!$found) {
        throw new ServerException(Util::_t('Invalid request'), Util::_t('No device found for this client.'));
    }

    return [];
}

function updateAccount($server)
{
    $client_id = $server->client['id'];

    if (!$client_id) {
        throw new ServerException(Util::_t('Invalid request'), Util::_t('Not a valid client Id.'));
    }

    $email = $server->client['email'];
    $first_name = $server->client['first_name'];
    $last_name = $server->client['last_name'];
    $lang = $server->client['language'];
    $is_confirmed = $server->client['is_confirmed']? 1 : 0;

    $client = new Client($client_id);
    $client->email = $email;
    $client->first_name = $first_name;
    $client->last_name = $last_name;
    $client->lang = $lang;
    $client->is_confirmed = $is_confirmed;

    if (!$client->exists) {
        $client->init_settings();
    }

    $serials = $server->license;
    if ($serials) {
        $created = 0;
        $expired = 0;
        $product = '10';
        $sn = '';
        $rebill = '';
        foreach ($serials as $p) {
            $cre = isset($p['registration_date'])? strtotime($p['registration_date']) : 0;
            if ($cre > $created) {
                $created = $cre;
                $expired = isset($p['expiration_date'])? strtotime($p['expiration_date']) : 0;
                if (isset($p['product'])) {
                    $product = $p['product'];
                }
                if (isset($p['sn'])) {
                    $sn = $p['sn'];
                }
                if (isset($p['rebillStatus'])) {
                    $rebill = $p['rebillStatus'];
                }
            }
        }
        if (!in_array($product, ['10', 'p3y', 'p10y', 'p3m', 'p10m'])) {
            $product = '10';
        }
        $client->update_serials([
            'sn' => $sn,
            'product' => $product,
            'rebill' => $rebill,
            'expired' => $expired]);
    }

    $client->save();

    return [];
}

/**
 * @param $server Server
 * @return array
 */
function getAccountInfo($server)
{
    $server->check_authorization();

    $client = new Client($server->client_id);

    return [
        'client' => [
            'id' => $client->client_id,
            'email' => $server->email,
            'first_name' => $client->fname,
            'last_name' => $client->lname,
            'language' => $client->lang,
            'is_confirmed' => $client->is_confirmed? true : false,
        ]
    ];
}

