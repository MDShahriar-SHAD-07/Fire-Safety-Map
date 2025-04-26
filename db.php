<?php
// Database connection
$host = 'localhost'; // Database host
$user = 'root'; // Database username
$pass = ''; // Database password
$db = 'project_fire_safety'; // Database name

$conn = new mysqli($host, $user, $pass, $db);

// Check for connection errors
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}