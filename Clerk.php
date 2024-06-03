<?php
require_once 'Client.php';

class Clerk
{
    private static function getClient()
    {
        // Create and return a Neo4j client instance
        return Client::getClient();
    }

    public static function create($firstName, $lastName, $username, $password, $middleName = null)
    {
        $client = self::getClient();
        $result = $client->run('CREATE (c:Clerk {firstName: $firstName, middleName: $middleName, lastName: $lastName, username: $username, password: $password}) RETURN c', [
            'firstName' => $firstName,
            'middleName' => $middleName,
            'lastName' => $lastName,
            'username' => $username,
            'password' => password_hash($password, PASSWORD_DEFAULT) // Hash the password
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

        // Check if the clerk exists
        $result = $client->run('MATCH (c:Clerk {username: $username}) RETURN c.password AS password', ['username' => $username]);

        if ($result->count() < 1) {
            // Clerk not found
            return false;
        }

        $record = $result->first();
        $hashedPassword = $record->get('password');

        // Update the existing password
        $client->run('MATCH (c:Clerk {username: $username}) SET c.password = $newPassword', [
            'username' => $username,
            'newPassword' => password_hash($newPassword, PASSWORD_DEFAULT) // Hash the new password
        ]);

        return true;
    }

    public static function delete($username)
    {
        $client = self::getClient();
        $result = $client->run('MATCH (c:Clerk {username: $username}) DELETE c', ['username' => $username]);

        // Return true if a node was deleted
        return $result->count() > 0;
    }

    public static function approveStudentAccount($studentUsername)
    {
        $client = self::getClient();

        // Update the student's account approval status to true
        $result = $client->run('MATCH (s:Student {username: $studentUsername}) SET s.is_approved = true', ['studentUsername' => $studentUsername]);

        // Return true if the student's account was approved successfully
        header('Content-Type: application/json');
        if ($result->count() > 0) {
            return json_encode([
                "status" => "success",
                "message" => "Approved successfully!",
            ]);
        }
    }

    public static function approveResearch(int $paperId, $status)
    {
        $client = self::getClient();

        echo $paperId;
        echo $status;

        // Update the research's approval status to true
        $result = $client->run(
            'MATCH (r:Research) WHERE id(r) = $paperId SET r.status = $status RETURN r',
            [
                'paperId' => $paperId,
                'status' => $status,
            ]
        );        

        // Return response based on the result
        header('Content-Type: application/json');
        if ($result->count() > 0) {
            return json_encode([
                "is-success" => true,
                "message" => null //message is expected to be null on success.
            ]);
        } else {
            return json_encode([
                "is-success" => false,
                "message" => "Unable to update status"
            ]);
        }
    }

    public static function disapproveResearch($title)
    {
        $client = self::getClient();

        // Update the research's approval status to false
        $result = $client->run('MATCH (r:Research {title: $title}) SET r.approved = false RETURN r', ['title' => $title]);

        // Return response based on the result
        header('Content-Type: application/json');
        if ($result->count() > 0) {
            return json_encode([
                "status" => "success",
                "message" => "Research disapproved successfully!",
            ]);
        } else {
            return json_encode([
                "status" => "error",
                "message" => "Research disapproval failed!",
            ]);
        }
    }
}
