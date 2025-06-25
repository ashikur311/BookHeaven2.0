<?php
require_once '../db_connection.php';

header('Content-Type: application/json');

if (!isset($_GET['query']) || empty($_GET['query'])) {
    echo json_encode([]);
    exit;
}

$query = '%' . $_GET['query'] . '%';

$results = [];

// Search books
$stmt = $conn->prepare("
    SELECT book_id AS id, title AS name, 'book' AS type 
    FROM books 
    WHERE title LIKE ? 
    LIMIT 5
");
$stmt->bind_param("s", $query);
$stmt->execute();
$books = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$results = array_merge($results, $books);

// Search authors
$stmt = $conn->prepare("
    SELECT writer_id AS id, name, 'author' AS type 
    FROM writers 
    WHERE name LIKE ? 
    LIMIT 5
");
$stmt->bind_param("s", $query);
$stmt->execute();
$authors = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$results = array_merge($results, $authors);

// Search genres
$stmt = $conn->prepare("
    SELECT genre_id AS id, name, 'genre' AS type 
    FROM genres 
    WHERE name LIKE ? 
    LIMIT 5
");
$stmt->bind_param("s", $query);
$stmt->execute();
$genres = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$results = array_merge($results, $genres);

echo json_encode($results);