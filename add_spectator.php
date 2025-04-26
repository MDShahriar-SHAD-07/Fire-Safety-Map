<?php
header("Content-Type: application/json");
session_start();
include('db.php'); // Include your database connection

// Check if the user is logged in
if (!isset($_SESSION['User_ID'])) {
    echo json_encode(["success" => false, "message" => "User is not logged in."]);
    exit();
}

// Get the logged-in user's ID from the session
$userId = $_SESSION['User_ID'];

// Prepare the SQL query to insert a record into the spectator_table
$query = "INSERT INTO spectator_table (User_ID) VALUES (?)";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $userId);

if ($stmt->execute()) {
    // If the insertion is successful, get the inserted Spec_ID
    $specId = $stmt->insert_id; // This is the last inserted ID (Spec_ID)
    // Return success along with the Spec_ID
    echo json_encode(["success" => true, "message" => "Record added to spectator_table.", "Spec_ID" => $specId]);
} else {
    // If there is an error, return a failure response
    echo json_encode(["success" => false, "message" => "Failed to add record to spectator_table."]);
}

$stmt->close();
$conn->close();
?>
