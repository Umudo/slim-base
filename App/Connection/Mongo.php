<?php

namespace App\Connection;

use App\Base\Connection;
use MongoDB\Client;
use MongoDB\Database;
use MongoDB\Driver\Exception\ConnectionException;

class Mongo extends Connection
{
    protected $collection_map = array();

    /**
     * @var Client
     */
    protected $client;

    /**
     * @var Database
     */
    protected $db;

    protected $uriOptions = [
        "connectTimeoutMS" => 2000,
        "socketTimeoutMS"  => 60000,
        "username"         => '',
        "password"         => '',
    ];

    protected $driverOptions = [];

    public function __construct($host = "mongodb://localhost", $port = 27017, array $uriOptions = [], array $driverOptions = [])
    {
        foreach ($uriOptions as $key => $value) {
            $this->uriOptions[$key] = $value;
        }

        foreach ($driverOptions as $key => $value) {
            $this->driverOptions[$key] = $value;
        }

        if (empty($this->uriOptions['username'])) {
            unset($this->uriOptions['username']);
        }

        if (empty($this->uriOptions['password'])) {
            unset($this->uriOptions['password']);
        }

        try {
            $this->client = new Client($host . ":" . $port, $this->uriOptions, $this->driverOptions);
        } catch (ConnectionException $e) {
            //Try again once.
            $this->client = new Client($host . ":" . $port, $this->uriOptions, $this->driverOptions);
        }
    }

    /**
     * @return Client
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @return Database
     */
    public function getDatabase()
    {
        return $this->db;
    }

    /**
     * @param string $db
     *
     * @throws \Exception
     */
    public function selectDatabase(string $db)
    {
        if (empty($db) || !is_string($db)) {
            throw new \Exception("Database name must be a string");
        }

        if (empty($this->client)) {
            throw new \Exception("Connection hasn't been established yet.");
        }

        $this->db = $this->client->selectDatabase($db);
    }

    /**
     * @param string $collectionName
     * @param bool   $forceCreate
     * @param bool   $overrideExistsCheck
     *
     * @return mixed
     * @throws \Exception
     */
    public function getCollection(string $collectionName, bool $forceCreate = false, bool $overrideExistsCheck = false)
    {
        if (empty($this->db)) {
            throw new \Exception("Database is not selected");
        }

        if (empty($collectionName) || !is_string($collectionName)) {
            throw new \Exception("Collection name must be a string");
        }

        if (isset($this->collection_map[$collectionName])) {
            return $this->collection_map[$collectionName];
        }

        if ($overrideExistsCheck || $this->checkCollectionExists($collectionName)) {
            $collection = $this->db->selectCollection($collectionName);
            $this->collection_map[$collectionName] = $collection;

            return $collection;
        }

        if ($forceCreate) {
            $this->db->createCollection($collectionName);
            $collection = $this->db->selectCollection($collectionName);
            $this->collection_map[$collectionName] = $collection;

            return $collection;
        }
    }

    public function checkCollectionExists(string $collectionName)
    {
        if (empty($this->db)) {
            throw new \Exception("Database is not selected");
        }

        $collection_check = $this->db->listCollections(array("filter" => array("name" => $collectionName)));

        return count(iterator_to_array($collection_check)) > 0;
    }
}