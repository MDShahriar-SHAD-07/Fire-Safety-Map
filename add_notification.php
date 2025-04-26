<?php
header("Content-Type: application/json");
$response = ["success" => false, "message" => ""];

// Database connection
require_once "db.php"; // Replace with your DB connection file

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Retrieve Spec_ID from POST data
    $spec_id = $_POST['Spec_ID'] ?? null;

    if (empty($spec_id)) {
        $response["message"] = "Spec_ID is missing.";
        echo json_encode($response);
        exit;
    }

    try {
        // Fetch user location and nearest fire station using Spec_ID
        $sql_location = "SELECT u.User_loc_coordinate, u.Nearest_Fire_Station_ID 
                         FROM user u 
                         JOIN spectator_table s ON u.User_ID = s.User_ID 
                         WHERE s.Spec_ID = ?";
        $stmt_location = $conn->prepare($sql_location);
        $stmt_location->bind_param("i", $spec_id);

        if ($stmt_location->execute()) {
            $result = $stmt_location->get_result();
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $location = $row['User_loc_coordinate'];
                $nearest_station_id = $row['Nearest_Fire_Station_ID'];

                if ($nearest_station_id) {
                    // Insert notification into notification_table
                    $notification_text = "A spectator from coordinate $location reported Fire.";
                    $sql_notification = "INSERT INTO notification_table (Notification_Text, Spec_ID, Station_ID) VALUES (?, ?, ?)";
                    $stmt_notification = $conn->prepare($sql_notification);
                    $stmt_notification->bind_param("sii", $notification_text, $spec_id, $nearest_station_id);

                    if ($stmt_notification->execute()) {
                        $response["success"] = true;
                        $response["message"] = "Notification added successfully.";
                    } else {
                        $response["message"] = "Failed to add notification.";
                    }
                    $stmt_notification->close();
                } else {
                    $response["message"] = "Nearest fire station not found for the user.";
                }
            } else {
                $response["message"] = "Location not found for the given Spec_ID.";
            }
        } else {
            $response["message"] = "Failed to fetch user location.";
        }
        $stmt_location->close();
    } catch (Exception $e) {
        $response["message"] = "An error occurred: " . $e->getMessage();
    }
} else {
    $response["message"] = "Invalid request method.";
}

// Return JSON response
echo json_encode($response);
?>
