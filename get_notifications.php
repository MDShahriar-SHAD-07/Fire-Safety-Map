<?php
header("Content-Type: application/json");
$response = ["success" => false, "message" => "", "notifications" => []];

// Database connection
require_once "db.php"; // Replace with your DB connection file

try {
    // Query to get Notification_Text, Spec_ID or Vic_ID, and join with spectator_table or victim_table based on content
    $sql = "
        SELECT 
            n.Notification_Text, 
            CASE 
                WHEN n.Notification_Text LIKE '%spectator%' THEN s.Spec_ID
                WHEN n.Notification_Text LIKE '%victim%' THEN v.Vic_ID
            END AS Related_ID,
            CASE 
                WHEN n.Notification_Text LIKE '%spectator%' THEN u.User_loc_coordinate
                WHEN n.Notification_Text LIKE '%victim%' THEN u.User_loc_coordinate
            END AS User_loc_coordinate
        FROM 
            notification_table n
        LEFT JOIN 
            spectator_table s ON n.Spec_ID = s.Spec_ID
        LEFT JOIN 
            victim_table v ON n.Vic_ID = v.Vic_ID
        LEFT JOIN 
            user u ON (s.User_ID = u.User_ID OR v.User_ID = u.User_ID)
        LIMIT 10
    "; // Limit to 10 records

    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $notifications = [];
        while ($row = $result->fetch_assoc()) {
            // Split the coordinate into latitude and longitude
            list($latitude, $longitude) = explode(',', $row['User_loc_coordinate']);
            
            // Create the notification object with coordinate details
            $notifications[] = [
                'Notification_Text' => $row['Notification_Text'],
                'Latitude' => $latitude,
                'Longitude' => $longitude,
                'Related_ID' => $row['Related_ID']  // This could be Spec_ID or Vic_ID
            ];
        }
        $response["success"] = true;
        $response["notifications"] = $notifications;
    } else {
        $response["message"] = "No notifications found.";
    }
} catch (Exception $e) {
    $response["message"] = "An error occurred: " . $e->getMessage();
}

// Return JSON response
echo json_encode($response);
?>
