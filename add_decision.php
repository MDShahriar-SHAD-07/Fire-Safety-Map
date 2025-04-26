<?php
header("Content-Type: application/json");
$response = ["success" => false, "message" => ""];

// Database connection
require_once "db.php"; // Replace with your DB connection file

// Get POST data
$data = json_decode(file_get_contents("php://input"), true);
$station_id = $data['Station_ID'] ?? null;
$road_id = $data['Road_ID'] ?? null;
$water_id = $data['Water_ID'] ?? null;
$notification_id = $data['Notification_ID'] ?? null;

if (!$station_id || !$road_id || !$water_id || !$notification_id) {
    $response["message"] = "Missing data.";
    echo json_encode($response);
    exit;
}

try {
    // Insert into decision_table
    $sql = "INSERT INTO decision_table (Station_ID, Road_ID, Water_ID, Notification_ID) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiii", $station_id, $road_id, $water_id, $notification_id);

    if ($stmt->execute()) {
        $response["success"] = true;
        $response["message"] = "Decision added successfully.";
    } else {
        $response["message"] = "Failed to add decision.";
    }
    $stmt->close();
} catch (Exception $e) {
    $response["message"] = "An error occurred: " . $e->getMessage();
}

// Return JSON response
echo json_encode($response);
?>
