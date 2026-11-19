<?php
session_start();

// Connect to DB
$conn = new mysqli("localhost", "root", "", "BIBLIO");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize
$books = [];
$searchTerm = '';

// Grab search input safely (GET or POST)
if (isset($_GET['search'])) {
    $searchTerm = trim($_GET['search']);
}

// Only run query if search term is not empty
if ($searchTerm !== '') {
    // Escape to prevent SQL issues
    $searchTermEscaped = $conn->real_escape_string($searchTerm);

    // Query: search in title OR author
    $sql = "SELECT * FROM tbl_books 
            WHERE title LIKE '%$searchTermEscaped%' 
               OR author LIKE '%$searchTermEscaped%'";

    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $books[] = $row;
        }
    }
}

// Display results
if (count($books) > 0) {
    foreach ($books as $book) {
        echo "<div>";
        echo "<h3>" . html_entity_decode($book['title']) . "</h3>";
        echo "<p>Author: " . htmlspecialchars($book['author']) . "</p>";
        echo "</div>";
    }
} else {
    echo "<p>Book Not Found</p>";
}
?>
