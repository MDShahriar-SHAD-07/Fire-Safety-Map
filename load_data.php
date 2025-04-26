<?php
$conn = new mysqli('localhost', 'root', '', 'project_fire_safety');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "SELECT * FROM road";
$result = $conn->query($sql);

if (!$result) {
    die("Query failed: " . $conn->error);
}

$roads = [];
while ($row = $result->fetch_assoc()) {
    $roads[] = $row;
}

header('Content-Type: application/json');
echo json_encode($roads);

$conn->close();
?>