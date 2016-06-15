<?php


class ServerException extends Exception
{
    private $title;
    private $server_code;

    public function __construct($title = null, $message = null, $code = 1)
    {
        $this->title = $title;
        $this->server_code = $code;

        parent::__construct($message);
    }

    public function getServerCode()
    {
        return $this->server_code;
    }
    public function getTitle()
    {
        return $this->title;
    }
    public function getDetails()
    {
        return null;
    }
} 