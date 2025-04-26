<?php
header("Content-Type: application/json");
$response = ["success" => false, "message" => ""];

// Database connection
require_once "db.php"; // Replace with your DB connection file

// Get User_ID from the query string
$user_id = $_GET['user_id'] ?? null;

// Debugging: Check if User_ID is received correctly
if (!$user_id) {
    $response["message"] = "User_ID is missing.";
    echo json_encode($response);
    exit;
}

// Debugging: Print the received User_ID
error_log("Received User_ID: " . $user_id);

try {
    // Query to get Nearest_Fire_Station_ID for the user
    $sql = "SELECT Nearest_Fire_Station_ID FROM user WHERE User_ID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);

    if ($stmt->execute()) {
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $nearest_station_id = $row['Nearest_Fire_Station_ID'];

            // Debugging: Print the retrieved Nearest_Fire_Station_ID
            error_log("Nearest Fire Station ID: " . $nearest_station_id);

            // Return the Nearest_Fire_Station_ID
            $response["success"] = true;
            $response["nearest_station_id"] = $nearest_station_id;
        } else {
            $response["message"] = "User not found.";
        }
    } else {
        $response["message"] = "Failed to execute the query.";
    }
    $stmt->close();
} catch (Exception $e) {
    $response["message"] = "An error occurred: " . $e->getMessage();
}

// Return JSON response
echo json_encode($response);
?>
