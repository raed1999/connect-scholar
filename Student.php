<?php

require_once 'Client.php';

class Student
{

    private static function getClient()
    {
        // Create and return a Neo4j client instance
        return Client::getClient();
    }

    public static function create($firstName, $lastName, $yearLevel, $middleName = null)
    {
        $client = self::getClient();
        $result = $client->run('CREATE (s:Student {firstName: $firstName, middleName: $middleName, lastName: $lastName, yearLevel: $yearLevel, is_approve: $is_approve}) RETURN s', [
            'firstName' => $firstName,
            'middleName' => $middleName,
            'lastName' => $lastName,
            'yearLevel' => $yearLevel,
            'is_approve' => false
        ]);

        // Return true if a node was created
        header('Content-Type: application/json');
        if ($result->count() < 1) {
            return json_encode([
                "status" => "error",
                "message" => "Failed to create!",
            ]);
        }

        return json_encode([
            "status" => "success",
            "message" => "Student created successfully",
        ]);
    }

    public static function read($firstName, $lastName)
    {
        $client = self::getClient();
        $result = $client->run('MATCH (s:Student {firstName: $firstName, lastName: $lastName}) RETURN s', ['firstName' => $firstName, 'lastName' => $lastName]);

        // Return the first record if available
        return 

        header('Content-Type: application/json');
        return json_encode([
            "status" => "success",
            "data" => $result->first()->get('s')
        ]);
    }

    public static function update($firstName, $lastName, $yearLevel, $newFirstName, $newLastName, $newYearLevel, $newMiddleName = null)
    {
        $client = self::getClient();
        $result = $client->run('MATCH (s:Student {firstName: $firstName, lastName: $lastName}) SET s.firstName = $newFirstName, s.middleName = $newMiddleName, s.lastName = $newLastName, s.yearLevel = $newYearLevel RETURN s', [
            'firstName' => $firstName,
            'lastName' => $lastName,
            'newFirstName' => $newFirstName,
            'newMiddleName' => $newMiddleName,
            'newLastName' => $newLastName,
            'newYearLevel' => $newYearLevel
        ]);

        // Return true if properties were updated
        return $result->count() > 0;

        // Return true if a node was created
        header('Content-Type: application/json');
        if ($result->count() < 1) {
            return json_encode([
                "status" => "error",
                "message" => "Failed to update!",
            ]);
        }

        return json_encode([
            "status" => "success",
            "message" => "Student information updated successfully",
        ]);
    }

    public static function delete($firstName, $lastName)
    {
        $client = self::getClient();
        $result = $client->run('MATCH (s:Student {firstName: $firstName, lastName: $lastName}) DELETE s', ['firstName' => $firstName, 'lastName' => $lastName]);

        // Return true if a node was deleted
        return $result->count() > 0;
    }
}