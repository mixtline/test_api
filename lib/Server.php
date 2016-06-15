<?php
class Server
{
    public static $lang = 'en';

    private $post_data = [];
    private $method = null;
    private $token_data = null;
    private $complex_request = false;
    private $authorized = false;

    public function __construct()
    {
        error_reporting(0);

        @set_exception_handler(array($this, 'exception_handler'));
        @set_error_handler(array($this, 'error_handler'));
        @register_shutdown_function(array($this, 'shutdown_handler'));
    }

    /**
     * @param $exception ServerException
     * @return array
     */
    private function get_error($exception)
    {
        $error = [];
        if (method_exists($exception, 'getServerCode')) {
            $code = $exception->getServerCode();
            if ($code) {
                $error['code'] = $code;
            }
        }
        if (method_exists($exception, 'getTitle')) {
            $title = $exception->getTitle();
            if ($title) {
                $error['title'] = $title;
            }
        }
        $message = $exception->getMessage();
        if ($message) {
            $error['message'] = $message;
        }
        if (method_exists($exception, 'getDetails')) {
            $details = $exception->getDetails();
            if ($details) {
                $error['details'] = $details;
            }
        }
        return $error;
    }

    /**
     * @param $exception ServerException
     */
    public function exception_handler($exception)
    {
        //debug_print_backtrace();die;

        $this->output(['error' => $this->get_error($exception)]);
    }
    public function error_handler($errno, $errstr, $errfile, $errline)
    {
        if ($errstr) {
            $this->output(['error' => [
                'message' => $errstr,
                'file' => $errfile,
                'line' => $errline,
            ]]);
            die;
        }
    }
    public function shutdown_handler()
    {
        $error = error_get_last();
        if (isset($error['message'])) {
            $this->output(['error' => $error]);
        }
    }

    /**
     * @param $data string
     */
    private function log($data)
    {
        //@file_put_contents(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'api.log', date('d.m H:i:s').': '.$data."\n", FILE_APPEND);
        //DB::instance()->execute('INSERT INTO api_log (created, data) VALUES (UNIX_TIMESTAMP(), ?)', [$data]);
        if (!($device_id = $this->agentId)) {
            $device_id = isset($this->deviceInfo['agentId'])? $this->deviceInfo['agentId'] : false;
        }
        if (!$device_id) {
            if (preg_match('/"agentId":"(.*?)"/i', $data, $matches)) {
                $device_id = $matches[1];
            } else {
                $device_id = '000000';
            }
        }

        $config = Util::config();

        $first = strtolower(substr($device_id, 0, 1));
        $second = strtolower(substr($device_id, 1, 1));
        $path = $config['params']['api_archive_path'] . DIRECTORY_SEPARATOR . $first;
        if (!file_exists($path)) {
            @mkdir($path, 0770);
        }
        $path .= DIRECTORY_SEPARATOR . $second;
        if (!file_exists($path)) {
            @mkdir($path, 0770);
        }
        $path .= DIRECTORY_SEPARATOR . $device_id;
        if (!file_exists($path)) {
            @mkdir($path, 0770);
        }

        $data = [
            'date' => time(),
            'device_id' => $device_id,
            'data' => $data,
        ];
        $name = date('Ymd');
        file_put_contents($path . DIRECTORY_SEPARATOR . $name, JSON::encode($data)."\n==========\n", FILE_APPEND);
        @chmod($path . DIRECTORY_SEPARATOR . $name, 0660);

        $path = $config['params']['api_archive_path'] . DIRECTORY_SEPARATOR . 'recent';
        if (!file_exists($path)) {
            @mkdir($path, 0770);
        }
        $name = date('Ymd');
        file_put_contents($path . DIRECTORY_SEPARATOR . $name, JSON::encode($data)."\n==========\n", FILE_APPEND);
        @chmod($path . DIRECTORY_SEPARATOR . $name, 0660);
    }

    public function __get($name)
    {
        if ($name == 'client_id') {
            $data = $this->get_authorization_data();
            return isset($data['client_id'])? $data['client_id'] : false;
        } else
        if ($name == 'device_id') {
            $data = $this->get_authorization_data();
            return isset($data['device_id'])? $data['device_id'] : false;
        }
        return isset($this->post_data['params'][$name])? $this->post_data['params'][$name] : false;
    }
    public function get_bool($name, $value)
    {
        return isset($this->post_data['params'][$name])? ($this->post_data['params'][$name] === false || (string)$this->post_data['params'][$name] == 'false' || (string)$this->post_data['params'][$name] == '0'? false : true) : $value;
    }

    private function get_authorization_data()
    {
        if (isset($this->token_data)) return $this->token_data;

        if (!$this->post_data) $this->fetch_request();

        $token = isset($this->post_data['meta']['authToken'])? $this->post_data['meta']['authToken'] : false;
        if ($token !== false) {
            $token_json = RedisDB::instance()->get('api.token.'.$token);
            $token_data = json_decode($token_json, true);
            $this->token_data = $token_data;
            return $token_data;
        }
        return null;
    }
    public function check_authorization()
    {
        if (!$this->post_data) $this->fetch_request();

        $token = isset($this->post_data['meta']['authToken'])? $this->post_data['meta']['authToken'] : false;
        if ($token !== false) {
            $token_json = RedisDB::instance()->get('api.token.'.$token);
            $token_data = json_decode($token_json, true);
            if (!$token_json || !$token_data) {
                throw new ServerException(Util::_t('Auth error'), Util::_t('Token is expired'), 'invalid_license');
            }
            $this->token_data = $token_data;

            $device_id = $this->agentId;
            if ($device_id !== false) {
                /* lets allow to get another device
                if ((string)$device_id != (string)$token_data['device_id']) {
                    throw new ServerException(Util::_t('Auth error'), Util::_t('Hacker attack detected'), 'invalid_license');
                }
                */
            }
            RedisDB::instance()->setTimeout('api.token.'.$token, 3600);
        } else {
            throw new ServerException(Util::_t('Auth error'), Util::_t('Not authorized'), 'not_authorized');
        }
    }

    /**
     * @param $client Client
     */
    public function authorize($client, $device_id)
    {
        if (!$device_id) {
            throw new ServerException(Util::_t('Authorization failed'), Util::_t('Malformed request.'));
        }

        if ($data = DB::instance()->fetch('SELECT client_id FROM serials WHERE device_id=?', [$device_id])) {
            if ($data['client_id'] != $client->client_id) {
                throw new ServerException(Util::_t('Authorization failed'), Util::_t('This device is already linked to other Parent Account. Please, contact Support if you own this device and want to link it to your Parent Account. Your Device ID: {agent_id}. Support might ask you to provide this device ID.', ['{agent_id}' => $device_id]));
            }
        }

        $token = uniqid(rand(), true);
        RedisDB::instance()->set('api.token.'.$token, json_encode(['device_id' => $device_id, 'email' => $client->email, 'client_id' => $client->client_id]), 3600);
        return $token;
    }

    public function fetch_request()
    {
        $in = fopen("php://input", "rb");
        if (!$in) {
            throw new ServerException(Util::_t('Invalid request'), Util::_t('Malformed request'));
        }

        $post = '';
        while ($buff = fread($in, 4096)) {
            $post .= $buff;
        }

        $this->log($post);

        $data = json_decode($post, true);
        if (!$data) {
            throw new ServerException(Util::_t('Invalid request'), Util::_t('Malformed request'));
        }
        $this->post_data = $data;

        $method = isset($data['method'])? $data['method'] : false;
        if (!$method) {
            throw new ServerException(Util::_t('Invalid request'), Util::_t('Unknown method'));
        }
        $this->method = $method;

        Server::$lang = isset($data['meta']['locale'])? $data['meta']['locale'] : 'en';
    }

    public function output($data)
    {
        header('Content-type: text/json');
        $result = str_replace('"children":{}', '"children":[]', str_replace('[]', '{}', json_encode($data)));
        print($result);
        $this->log($result);
    }

    public function run()
    {
        if (!$this->method) $this->fetch_request();

        if ($this->method == 'Composite.handleRequestsArray') {
            $this->complex_request = true;
            if (!isset($this->post_data['params']['requests'])) {
                throw new ServerException(Util::_t('Invalid request'), Util::_t('No requests found'));
            }
            $requests = $this->post_data['params']['requests'];
            $result = [];
            foreach ($requests as $request) {
                $this->post_data = $request;
                $this->method = isset($request['method'])? $request['method'] : false;
                try {
                    $result[] = ['result' => $this->run_once($this->method)];
                } catch (Exception $exception) {
                    $result[] = ['error' => $this->get_error($exception)];
                }
            }
            return ['result' => ['responses' => $result]];
        } else {
            return ['result' => $this->run_once($this->method)];
        }
    }

    public function run_once($action)
    {
        $parts = explode('.', $action);
        $file = strtolower(array_shift($parts));
        $func = implode('_', $parts);
        include_once(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'methods' . DIRECTORY_SEPARATOR . $file . '.php');
        if (function_exists($func)) {
            return $func($this);
        } else {
            throw new ServerException(Util::_t('Invalid request'), str_replace('{method}', $action, Util::_t('Unknown method {method}.')));
        }
    }

    public function avatar()
    {
        $avatar_type = isset($this->child['avatar']['type'])? (string)$this->child['avatar']['type'] : false;
        $avatar_id = isset($this->child['avatar']['data']['predefinedId'])? (string)$this->child['avatar']['data']['predefinedId'] : false;
        $avatar_raw = isset($this->child['avatar']['data']['binaryBase64'])? (string)$this->child['avatar']['data']['binaryBase64'] : false;

        if ($avatar_type == 'predefined') {
            if (!in_array($avatar_id, Util::allowed_avatars())) {
                throw new ServerException(Util::_t('Invalid request'), Util::_t('Unknown predefined avatar Id.'));
            }
            $avatar = $avatar_id;
        } else {
            $file = Util::getRS(12) . '.' . $avatar_type;
            $path = Util::get_avatar_path($file, true);
            $avatar = $file;
            file_put_contents($path . DIRECTORY_SEPARATOR . $file, base64_decode($avatar_raw), FILE_BINARY);
            chmod($path . DIRECTORY_SEPARATOR . $file, 0666);
        }
        return $avatar;
    }
}