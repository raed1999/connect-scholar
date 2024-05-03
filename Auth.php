<?php

require_once 'Client.php';
require_once 'Student.php';

class Auth
{
    private static function getClient()
    {
        // Create and return a Neo4j client instance
        return Client::getClient();
    }

    public static function register($username, $password, $firstName, $lastName, $yearLevel, $middleName = null)
    {
        $client = self::getClient();

        // Check if the username already exists
        $result = $client->run('MATCH (s:Student {username: $username}) RETURN count(s) AS count', ['username' => $username]);
        $count = $result->first()->get('count');

        // If username exists, return false (registration failed)
        if ($count > 0) {
            // echo 'username already exist';
            // return false;
            header('Content-Type: application/json');
            return json_encode([
                "status" => "error",
                "message" => "Username already exist!",
            ]);
        }

        // Create the student node
        $created = Student::create($firstName, $lastName, $yearLevel, $middleName);

        // If student node creation failed, return false
        if (!$created) {
            header('Content-Type: application/json');
            return json_encode([
                "status" => "error",
                "message" => "Unknown error occured!",
            ]);
        }

        // Hash the password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Create the student's login credentials
        $client->run(
            'MATCH (s:Student {firstName: $firstName, lastName: $lastName}) 
                      SET s.username = $username, s.password = $hashedPassword',
            ['username' => $username, 'hashedPassword' => $hashedPassword, 'firstName' => $firstName, 'lastName' => $lastName]
        );

        // Registration successful
        header('Content-Type: application/json');
        return json_encode([
            "status" => "success",
            "message" => "Account created successfully",
        ]);
    }

    public static function login($username, $password)
    {
        $client = self::getClient();

        // Find the student node by username
        $results = $client->run('MATCH (s:Student {username: $username}) RETURN count(s) AS count, s.password AS password', ['username' => $username]);

        if ($results->count() <= 0) {
            // No records found, user doesn't exist
            header('Content-Type: application/json');
            return json_encode([
                "status" => "error",
                "message" => "User doesn't exist",
                "data" => [$username, $password],
            ]);
        }

        $record = $results->first();
        $storedPassword = $record->get('password');

        if (!password_verify($password, $storedPassword)) {

            header('Content-Type: application/json');
            return json_encode([
                "status" => "error",
                "message" => "Incorrect password",
            ]);
        }

        if (password_verify($password, $storedPassword)) {

            header('Content-Type: application/json');
            return json_encode([
                "status" => "success",
                "message" => "Logged in successfully!",
            ]);
        }
    }
}
