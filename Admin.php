<?php
require_once 'Client.php';

class Admin
{
    private static function getClient()
    {
        // Create and return a Neo4j client instance
        return Client::getClient();
    }

    public static function create($username, $password)
    {
        $client = self::getClient();
        $result = $client->run('CREATE (a:Admin {username: $username, password: $password}) RETURN a', [
            'username' => $username,
            'password' => password_hash($password, PASSWORD_DEFAULT), // Hash the password
        ]);

        // Return true if a node was created
        if ($result->count() < 1) {
            return false;
        }

        return true;
    }


    public static function changePassword($username, $newPassword)
    {
        $client = self::getClient();

        // Check if the admin exists
        $result = $client->run('MATCH (a:Admin {username: $username}) RETURN a.password AS password', ['username' => $username]);

        if ($result->count() < 1) {
            // Admin not found
            return false;
        }

        $record = $result->first();
        $hashedPassword = $record->get('password');

        // Update the existing password
        $client->run('MATCH (a:Admin {username: $username}) SET a.password = $newPassword', [
            'username' => $username,
            'newPassword' => password_hash($newPassword, PASSWORD_DEFAULT) // Hash the new password
        ]);

        return true;
    }

    public static function delete($username)
    {
        $client = self::getClient();
        $result = $client->run('MATCH (a:Admin {username: $username}) DELETE a', ['username' => $username]);

        // Return true if a node was deleted
        return $result->count() > 0;
    }
}
