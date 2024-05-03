<?php

require_once 'Client.php';

class Research
{

    private static function getClient()
    {
        // Create and return a Neo4j client instance
        return Client::getClient();
    }

    public static function create($title, $year, $abstract, $citations = [], $authors = [])
    {
        $client = self::getClient();
        // Use MERGE to create a new node only if it doesn't already exist
        $result = $client->run('MERGE (r:Research {title: $title}) 
            ON CREATE SET r.year = $year, r.abstract = $abstract 
            RETURN r, CASE WHEN r.year IS NULL THEN false ELSE true END as isNew', [
            'title' => $title,
            'year' => $year,
            'abstract' => $abstract
        ]);

        // Check if a new node was created
        $record = $result->first();
        $created = $record->get('isNew');

        // If there are citations, create citation relationships
        if ($created && !empty($citations)) {
            foreach ($citations as $citation) {
                self::cite($title, (int)$citation);
            }
        }

        // If there are authors, assign them to the research
        if ($created && !empty($authors)) {
            foreach ($authors as $author) {
                self::assignAuthors($title, (int)$author);
            }
        }

        header('Content-Type: application/json');
        return json_encode([
            "status" => "success",
            "message" => "Research added successfully",
        ]);
    }


    public static function read(int $id)
    {
        header('Content-Type: application/json');

        $client = self::getClient();
        $result = $client->run('MATCH (r:Research)
            WHERE id(r) = $id RETURN r', ['id' => $id]);

        return json_encode([
            "researches" => $result->first()->get('r')
        ]);
    }

    public static function all()
    {
        $client = self::getClient();
        $results = $client->run('MATCH (r:Research) RETURN r');

        header('Content-Type: application/json');
        return json_encode([
            "status" => "success",
            "message" => "Retrieve successfully",
            "researches" => $results
        ]);
    }

    public static function update($title, $newYear, $newAbstract)
    {
        $client = self::getClient();
        $result = $client->run('MATCH (r:Research {title: $title}) SET r.year = $newYear, r.abstract = $newAbstract RETURN r', [
            'title' => $title,
            'newYear' => $newYear,
            'newAbstract' => $newAbstract
        ]);

        header('Content-Type: application/json');
        // Return true if properties were set
        if ($result->count() < 1) {
            return json_encode([
                "status" => "error",
                "message" => "Failed to update!",
            ]);
        }

        return json_encode([
            "status" => "success",
            "message" => "Updated successfully",
        ]);
    }

    public static function delete($title)
    {
        $client = self::getClient();
        // First, delete any relationships connected to the Research node
        $client->run('MATCH (r:Research {title: $title})-[rel]-() DELETE rel', ['title' => $title]);
        // Then, delete the Research node
        $result = $client->run('MATCH (r:Research {title: $title}) DELETE r', ['title' => $title]);
    }


    public static function cite($sourceTitle, $targetTitle)
    {
        $client = self::getClient();

        $result = $client->run(
            'MATCH (source:Research {title: $sourceTitle}), (target:Research)
                WHERE ID(target) = $targetTitle
                CREATE (source)-[:CITED]->(target)',
            ['sourceTitle' => $sourceTitle, 'targetTitle' => $targetTitle]
        );
    }

    public static function assignAuthors($researchTitle, $authorInternalId)
    {
        $client = self::getClient(); // Make sure getClient is a static method
        $result = $client->run(
            'MATCH (r:Research {title: $researchTitle}), (s:Student)
                WHERE ID(s) = $authorInternalId
                CREATE (s)-[:AUTHOR_OF]->(r)',
            ['researchTitle' => $researchTitle, 'authorInternalId' => $authorInternalId]
        );
    }
}
