<?php
// Include Composer's autoloader
require_once 'vendor/autoload.php';

use Laudis\Neo4j\Authentication\Authenticate;
use Laudis\Neo4j\ClientBuilder;
use Laudis\Neo4j\Contracts\TransactionInterface;

class Client {
    public static $client;

    public static function getClient() {
        if (!self::$client) {
            self::$client = ClientBuilder::create()
                ->withDriver('bolt', 'bolt://neo4j:password123@localhost?database=connectscholar')
                ->build();
        }
        return self::$client;
    }

    // Add more methods to interact with the Neo4j client as needed
}

