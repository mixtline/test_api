<?php

class Mdm
{
    private $device_id;

    public function __construct($device_id)
    {
        $this->device_id = $device_id;
    }

    public function __get($name)
    {
        if ($name == 'installed') {
            $data = $this->get_installed_status();
            return isset($data['result']) && $data['result']? true : false;
        }
    }

    private function get_installed_status()
    {
        $config = Util::config();
        $options = array(
            'http' => array(
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                'method'  => 'POST',
                'content' => '{"jsonrpc":"2.0","method":"device:isEnrolledByAgentId","id":"mdm2516","params":{"agentId":"'.$this->device_id.'"}}',
            ),
        );
        $context  = stream_context_create($options);
        $result = file_get_contents($config['params']['mdm_server_url'], false, $context);
        return json_decode($result, true);
    }
}