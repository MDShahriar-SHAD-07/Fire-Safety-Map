<?php
// Database connection
$conn = new mysqli('localhost', 'root', '', 'project_fire_safety');

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get the road ID from the POST request
$road_id = $_POST['road_id'];

if (!empty($road_id)) {
    $sql = "DELETE FROM road WHERE Road_ID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $road_id);

    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Road deleted successfully!"]);
    } else {
        echo json_encode(["success" => false, "message" => "Failed to delete road."]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Road ID is missing!"]);
}

$conn->close();
?>
