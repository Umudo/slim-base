<?php

namespace App\Connection;


use App\Base\Connection;

class Redis extends Connection
{
    /**
     * @var \Redis
     */
    protected $client;

    private $optionsMustInclude = [
        "host",
        "port",
        "timeout",
        "persistent"
    ];

    private $options = [];

    private $connected = false;

    public function __construct(array $options)
    {
        foreach ($this->optionsMustInclude as $mustKey)
        {
            if (!isset($options[$mustKey])) {
                throw new \Exception("Provided options array does not contain all required fields. (". implode(", ", $this->optionsMustInclude) .")");
            }
        }

        $this->options = $options;

        $this->client = new \Redis();
        $this->connect();
    }

    public function connect()
    {
        if ($this->options["persistent"] === true) {
            try {
                $this->client->pconnect($this->options["host"], $this->options["port"], $this->options["timeout"]);
                $this->connected = true;
            } catch (\RedisException $e) {
                $this->client->pconnect($this->options["host"], $this->options["port"], $this->options["timeout"]);
                $this->connected = true;
            }
        } else {
            try {
                $this->client->connect($this->options["host"], $this->options["port"], $this->options["timeout"]);
                $this->connected = true;
            } catch (\RedisException $e) {
                $this->client->connect($this->options["host"], $this->options["port"], $this->options["timeout"]);
                $this->connected = true;
            }
        }

        if (!empty($this->options["password"])) {
            $this->client->auth($this->options["password"]);
        }
    }

    public function isConnected()
    {
        return $this->connected;
    }

    /**
     * @return \Redis
     */
    public function getClient()
    {
        return $this->client;
    }

    public function __call($name, $arguments) {
        if (method_exists($this->getClient(), $name)) {
            if (!$this->isConnected()) {
                $this->connect();
            }

            return call_user_func_array([$this->getClient(), $name], $arguments);
        }
    }
}