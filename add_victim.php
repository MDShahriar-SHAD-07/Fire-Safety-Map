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

// Prepare the SQL query to insert a record into the victim_table
$query = "INSERT INTO victim_table (User_ID) VALUES (?)";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $userId);

if ($stmt->execute()) {
    // If the insertion is successful, get the inserted Vic_ID
    $vicId = $stmt->insert_id; // This is the last inserted ID (Vic_ID)
    // Return success along with the Vic_ID
    echo json_encode(["success" => true, "message" => "Record added to victim_table.", "Vic_ID" => $vicId]);
} else {
    // If there is an error, return a failure response
    echo json_encode(["success" => false, "message" => "Failed to add record to victim_table."]);
}

$stmt->close();
$conn->close();
?>
