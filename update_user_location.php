<?php
// Include database connection
require_once 'db.php';

header('Content-Type: application/json');

// Get the JSON data from the request
$data = json_decode(file_get_contents("php://input"), true);

if (isset($data['User_id'], $data['latitude'], $data['longitude'])) {
    $user_id = $data['User_id']; // Fetch user_id
    $latitude = $data['latitude'];
    $longitude = $data['longitude'];

    // Combine latitude and longitude as a string
    $user_loc_coordinate = $latitude . ',' . $longitude;

    // Update the user's location in the database
    $query = "UPDATE user SET User_loc_coordinate = ? WHERE User_ID = ?";
    $stmt = $conn->prepare($query);

    if (!$stmt) {
        echo json_encode(["success" => false, "message" => "Failed to prepare statement."]);
        exit;
    }

    $stmt->bind_param("si", $user_loc_coordinate, $user_id);

    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Location updated successfully."]);
    } else {
        echo json_encode(["success" => false, "message" => "Failed to execute query."]);
    }

    $stmt->close();
} else {
    echo json_encode(["success" => false, "message" => "Invalid input."]);
}

$conn->close();
?>
