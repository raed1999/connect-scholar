<?php

require_once 'Client.php';

class Research
{

    private static function getClient()
    {
        // Create and return a Neo4j client instance
        return Client::getClient();
    }

    public static function create($title, $year, $abstract, $citations = [], $authors = [],  $keywords = [])
    {
        $client = self::getClient();
        // Use MERGE to create a new node only if it doesn't already exist
        $result = $client->run('MERGE (r:Research {title: $title}) 
        ON CREATE SET r.year = $year, r.abstract = $abstract 
        RETURN r, CASE WHEN r.year IS NULL THEN false ELSE true END as isNew', [
            'title' => $title,
            'year' => $year,
            'abstract' => $abstract,
            'approved' => false,
        ]);

        // Check if a new node was created
        $record = $result->first();
        $created = $record->get('isNew');

        // If there are keywords, add them
        if ($created && !empty($keywords)) {
            foreach ($keywords as $keyword) {
                self::addKeyword($title, $keyword);
            }
        }

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

    public static function getResearch(int $userid)
    {
        header('Content-Type: application/json');

        $client = self::getClient();
        $results = $client->run('
            MATCH (a:Student)-[:AUTHOR_OF]->(r:Research)
            WHERE ID(a) = $id
            RETURN r, collect(a) AS authors', ['id' => $userid]);

        // Initialize an array to hold all research data
        $researchList = [];

        // Loop through each record to get research and author data
        foreach ($results as $record) {
            $research = $record->get('r');
            $authors = $record->get('authors');

            // Extract data from the research node
            $researchProperties = $research->getProperties();
            $researchData = [
                "id" => $research->getId(), // Correctly get the node ID
                "image-url" => "", // This can be filled in later as needed
                "title" => $researchProperties['title'] ?? '',
                "authors" => [],
                "date-published" => $researchProperties['date_published'] ?? '',
                "rates" => $researchProperties['rates'] ?? 0,
                "likes" => $researchProperties['likes'] ?? 0,
                "views" => $researchProperties['views'] ?? 0,
                "status" => $researchProperties['status'] ?? 'pending'
            ];

            // Extract data from the author nodes
            foreach ($authors as $author) {
                $authorProperties = $author->getProperties();
                $researchData['authors'][] = [
                    "user-id" => $author->getId(),
                    "name" => $authorProperties['firstName']  . ' ' . $authorProperties['lastName'],
                ];
            }

            // Add the research data to the research list
            $researchList[] = $researchData;
        }

        // Return the response as a JSON object
        return json_encode([
            "is-success" => true,
            "message" => null,
            "user-papers" => $researchList
        ]);
    }





    public static function searchByTitle($query)
    {
        $client = self::getClient();

        // Convert the search query to lowercase or uppercase for case-insensitive search
        $query = strtolower($query);

        // Construct the Cypher query to search based on title, year, author, or keywords (case-insensitive)
        $result = $client->run(
            'MATCH (r:Research)
        WHERE toLower(r.title) CONTAINS $query OR toLower(r.year) CONTAINS $query
        OPTIONAL MATCH (r)<-[:AUTHOR_OF]-(a:Student)
        OPTIONAL MATCH (r)-[:KEYWORD_OF]->(k:Keyword)
        RETURN r.title, r.year, collect(distinct a) as authors, collect(distinct k) as keywords',
            ['query' => $query]
        );

        // Extract the results
        $researches = [];
        foreach ($result as $record) {
            $research = [
                'title' => $record->get('r.title'),
                'year' => $record->get('r.year'),
            ];
            $authors = $record->get('authors');
            $keywords = $record->get('keywords');

            $researches[] = [
                'research' => $research,
                'authors' => $authors,
                'keywords' => $keywords
            ];
        }

        header('Content-Type: application/json');
        if (empty($researches)) {
            return json_encode([
                "status" => "error",
                "message" => "No research data found for the given query",
                "researches" => []
            ]);
        }

        return json_encode([
            "status" => "success",
            "message" => "Research found successfully",
            "researches" => $researches
        ]);
    }

    public static function searchByAuthor($query)
    {
        $client = self::getClient();

        // Convert the search query to lowercase or uppercase for case-insensitive search
        $query = strtolower($query);

        // Construct the Cypher query to search based on author's name (case-insensitive)
        $result = $client->run(
            'MATCH (r:Research)<-[:AUTHOR_OF]-(a:Student)
        WHERE toLower(a.firstName) CONTAINS $query OR
              toLower(a.middleName) CONTAINS $query OR
              toLower(a.lastName) CONTAINS $query
        OPTIONAL MATCH (r)-[:KEYWORD_OF]->(k:Keyword)
        RETURN r.title, r.year, collect(distinct a) as authors, collect(distinct k) as keywords',
            ['query' => $query]
        );

        // Extract the results
        $researches = [];
        foreach ($result as $record) {
            $research = [
                'title' => $record->get('r.title'),
                'year' => $record->get('r.year'),
            ];
            $authors = $record->get('authors');
            $keywords = $record->get('keywords');

            $researches[] = [
                'research' => $research,
                'authors' => $authors,
                'keywords' => $keywords
            ];
        }

        header('Content-Type: application/json');
        if (empty($researches)) {
            header('Content-Type: application/json');
            return json_encode([
                "status" => "error",
                "message" => "No research data found for the given query",
                "researches" => []
            ]);
        }

        return json_encode([
            "status" => "success",
            "message" => "Research found successfully",
            "researches" => $researches
        ]);
    }

    public static function searchByYear($query)
    {
        $client = self::getClient();

        // Construct the Cypher query to search based on year
        $result = $client->run(
            'MATCH (r:Research)
        WHERE r.year = $query
        OPTIONAL MATCH (r)<-[:AUTHOR_OF]-(a:Student)
        OPTIONAL MATCH (r)-[:KEYWORD_OF]->(k:Keyword)
        RETURN r.title, r.year, collect(distinct a) as authors, collect(distinct k) as keywords',
            ['query' => $query]
        );

        // Extract the results
        $researches = [];
        foreach ($result as $record) {
            $research = [
                'title' => $record->get('r.title'),
                'year' => $record->get('r.year'),
            ];
            $authors = $record->get('authors');
            $keywords = $record->get('keywords');

            $researches[] = [
                'research' => $research,
                'authors' => $authors,
                'keywords' => $keywords
            ];
        }

        header('Content-Type: application/json');
        if (empty($researches)) {
            return json_encode([
                "status" => "error",
                "message" => "No research data found for the given query",
                "researches" => []
            ]);
        }

        return json_encode([
            "status" => "success",
            "message" => "Research found successfully",
            "researches" => $researches
        ]);
    }

    public static function searchByKeyword($query)
    {
        $client = self::getClient();

        // Convert the search query to lowercase or uppercase for case-insensitive search
        $query = strtolower($query);

        // Construct the Cypher query to search based on keyword (case-insensitive)
        $result = $client->run(
            'MATCH (r:Research)-[:KEYWORD_OF]->(k:Keyword)
        WHERE toLower(k.name) CONTAINS $query
        OPTIONAL MATCH (r)<-[:AUTHOR_OF]-(a:Student)
        RETURN r.title, r.year, collect(distinct a) as authors, collect(distinct k) as keywords',
            ['query' => $query]
        );

        // Extract the results
        $researches = [];
        foreach ($result as $record) {
            $research = [
                'title' => $record->get('r.title'),
                'year' => $record->get('r.year'),
            ];
            $authors = $record->get('authors');
            $keywords = $record->get('keywords');

            $researches[] = [
                'research' => $research,
                'authors' => $authors,
                'keywords' => $keywords
            ];
        }

        // Check if no research data was found
        header('Content-Type: application/json');
        if (empty($researches)) {
            return json_encode([
                "status" => "error",
                "message" => "No research data found for the given keyword",
                "researches" => []
            ]);
        }

        return json_encode([
            "status" => "success",
            "message" => "Research found successfully",
            "researches" => $researches
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

    public static function addKeyword($researchTitle, $keyword)
    {
        $client = self::getClient();

        // Check if the keyword exists
        $result = $client->run('MATCH (k:Keyword {name: $keyword}) RETURN k', ['keyword' => $keyword]);

        // Check if any records were returned
        if ($result->count() > 0) {
            // Keyword exists, get the first record
            $keywordNode = $result->first()->get('k');
        } else {
            // Keyword doesn't exist, create it
            $client->run('CREATE (k:Keyword {name: $keyword})', ['keyword' => $keyword]);

            // Fetch the newly created keyword node
            $result = $client->run('MATCH (k:Keyword {name: $keyword}) RETURN k', ['keyword' => $keyword]);
            $keywordNode = $result->first()->get('k');
        }

        // Create the relationship
        $client->run(
            'MATCH (r:Research {title: $researchTitle}), (k:Keyword {name: $keyword})
                MERGE (r)-[:KEYWORD_OF]->(k)',
            ['researchTitle' => $researchTitle, 'keyword' => $keyword]
        );

        header('Content-Type: application/json');
        return json_encode([
            "status" => "success",
            "message" => "Keyword added to research successfully",
        ]);
    }

    public static function recommendByKeywords($title)
    {
        $client = self::getClient();

        $result = $client->run(
            'MATCH (r:Research {title: $title})-[:KEYWORD_OF]->(k:Keyword)<-[:KEYWORD_OF]-(rec:Research)
            WHERE r <> rec
            RETURN rec.title AS recommendedTitle, collect(k.name) AS sharedKeywords, count(k) AS keywordCount
            ORDER BY keywordCount DESC
            LIMIT 5',
            ['title' => $title]
        );

        // Extract the results
        $recommendations = [];
        foreach ($result as $record) {
            $recommendations[] = [
                'title' => $record->get('recommendedTitle'),
                'sharedKeywords' => $record->get('sharedKeywords')
            ];
        }

        header('Content-Type: application/json');
        if (empty($recommendations)) {
            return json_encode([
                "status" => "error",
                "message" => "No recommendations found",
                "recommendations" => []
            ]);
        }

        return json_encode([
            "status" => "success",
            "message" => "Recommendations found successfully",
            "recommendations" => $recommendations
        ]);
    }

    public static function popularResearch()
    {
        $client = self::getClient();

        $result = $client->run(
            'MATCH (r:Research)-[:KEYWORD_OF]->(k:Keyword)
        RETURN k.name AS keyword, count(r) AS occurrence
        ORDER BY occurrence DESC
        LIMIT 5'
        );

        // Extract the results
        $themes = [];
        foreach ($result as $record) {
            $themes[] = [
                'keyword' => $record->get('keyword'),
                'occurrence' => $record->get('occurrence')
            ];
        }

        header('Content-Type: application/json');
        if (empty($themes)) {
            return json_encode([
                "status" => "error",
                "message" => "No popular themes found",
                "themes" => []
            ]);
        }

        return json_encode([
            "status" => "success",
            "message" => "Popular themes found successfully",
            "themes" => $themes
        ]);
    }
}
