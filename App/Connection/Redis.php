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

        if ($options["connect"] === true) {
            $this->connect();
        }
    }

    public function connect()
    {
        if ($this->options["persistent"] === true) {
            try {
                $this->client->pconnect($this->options["host"], $this->options["port"], $this->options["timeout"]);
            } catch (\RedisException $e) {
                $this->client->pconnect($this->options["host"], $this->options["port"], $this->options["timeout"]);
            }
        } else {
            try {
                $this->client->connect($this->options["host"], $this->options["port"], $this->options["timeout"]);
            } catch (\RedisException $e) {
                $this->client->connect($this->options["host"], $this->options["port"], $this->options["timeout"]);
            }
        }

        if (!empty($this->options["password"])) {
            $this->client->auth($this->options["password"]);
        }
    }

    public function getClient()
    {
        return $this->client;
    }
}