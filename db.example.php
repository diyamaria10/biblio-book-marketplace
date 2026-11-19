<?php

$servername = "localhost";
$username   = "root";
$password   = "YOUR_MYSQL_PASSWORD";
$dbname     = "BIBLIO";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

?>
