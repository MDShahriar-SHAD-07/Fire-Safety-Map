<?php
header("Content-Type: application/json");
require 'db.php';

// Check if Vic_ID is provided
if (!isset($_POST['Vic_ID'])) {
    echo json_encode(["success" => false, "message" => "Vic_ID is required."]);
    exit();
}

$vic_id = intval($_POST['Vic_ID']);
$smoke_intensity = $_POST['smoke_condition'] ?? null;
$victim_count = $_POST['victim_count'] ?? null;
$building_location = $_POST['building_location'] ?? null;
$vic_picture = $_FILES['vic_picture'] ?? null;

// Prepare default responses
$response = ["success" => true, "message" => ""];

// Debugging: Check all inputs
error_log("smoke_condition: $smoke_intensity, victim_count: $victim_count, building_location: $building_location");

// Update Smoke Intensity
if ($smoke_intensity) {
    $smoke_low = $smoke_intensity === 'low' ? 1 : 0;
    $smoke_mid = $smoke_intensity === 'medium' ? 1 : 0;
    $smoke_high = $smoke_intensity === 'high' ? 1 : 0;

    // Debugging the values before update
    error_log("smoke_low: $smoke_low, smoke_mid: $smoke_mid, smoke_high: $smoke_high");

    $sql = "UPDATE victim_table 
            SET smoke_low = ?, 
                smoke_mid = ?, 
                smoke_high = ? 
            WHERE Vic_ID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiii", $smoke_low, $smoke_mid, $smoke_high, $vic_id);

    if ($stmt->execute()) {
        $response["message"] .= "Smoke intensity updated. ";
    } else {
        $response["success"] = false;
        $response["message"] .= "Failed to update smoke intensity. ";
    }
    $stmt->close();
} else {
    error_log("smoke_intensity is not set or empty.");
}

// Update Victim Count
if ($victim_count) {
    $victim_1_5 = $victim_count === '1-5' ? 1 : 0;
    $victim_5_10 = $victim_count === '5-10' ? 1 : 0;
    $victim_10_plus = $victim_count === '10+' ? 1 : 0;

    $sql = "UPDATE victim_table 
            SET victim_num_1_to_5 = ?, 
                victim_num_5_to_10 = ?, 
                victim_num_10_plus = ? 
            WHERE Vic_ID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiii", $victim_1_5, $victim_5_10, $victim_10_plus, $vic_id);

    if ($stmt->execute()) {
        $response["message"] .= "Victim count updated. ";
    } else {
        $response["success"] = false;
        $response["message"] .= "Failed to update victim count. ";
    }
    $stmt->close();
}

// Update Building Location
if ($building_location) {
    $ground_floor = $building_location === 'ground_floor' ? 1 : 0;
    $second_to_fourth = $building_location === '2nd_to_4thfloor' ? 1 : 0;
    $above_fourth = $building_location === 'above_4thfloor' ? 1 : 0;

    $sql = "UPDATE victim_table 
            SET building_location_ground_floor = ?, 
                building_location_2ndfloor_to_4thfloor = ?, 
                building_location_above_4thfloor = ? 
            WHERE Vic_ID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiii", $ground_floor, $second_to_fourth, $above_fourth, $vic_id);

    if ($stmt->execute()) {
        $response["message"] .= "Building location updated. ";
    } else {
        $response["success"] = false;
        $response["message"] .= "Failed to update building location. ";
    }
    $stmt->close();
}

// Handle Image Upload
if ($vic_picture) {
    if ($vic_picture['error'] === UPLOAD_ERR_OK) {
        // Log the file size and type for debugging
        error_log('File uploaded: ' . $vic_picture['name'] . ' (' . $vic_picture['size'] . ' bytes)');
        
        $picture_blob = file_get_contents($vic_picture['tmp_name']);

        // Check if file_get_contents worked properly
        if ($picture_blob === false) {
            echo json_encode(["success" => false, "message" => "Failed to read the image file."]);
            exit();
        }

        $sql = "UPDATE victim_table SET vic_picture = ? WHERE Vic_ID = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("bi", $picture_blob, $vic_id);

        if ($stmt->execute()) {
            $response["message"] .= "Image uploaded successfully. ";
        } else {
            $response["success"] = false;
            $response["message"] .= "Failed to upload image. ";
        }
        $stmt->close();
    } else {
        $response["success"] = false;
        $response["message"] .= "Error uploading image: " . $vic_picture['error'];
    }
}

// Fetch User Location and Add Notification
$sql_location = "SELECT u.User_loc_coordinate, u.Nearest_Fire_Station_ID 
                 FROM user u 
                 JOIN victim_table v ON u.User_ID = v.User_ID 
                 WHERE v.Vic_ID = ?";
$stmt_location = $conn->prepare($sql_location);
$stmt_location->bind_param("i", $vic_id);

if ($stmt_location->execute()) {
    $result = $stmt_location->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $location = $row['User_loc_coordinate'];
        $nearest_station_id = $row['Nearest_Fire_Station_ID'];

        if ($nearest_station_id) {
            // Add notification with Station_ID
            $notification_text = "A victim from coordinate $location reported Fire.";
            $sql_notification = "INSERT INTO notification_table (Notification_Text, Spec_ID, Vic_ID, Station_ID) VALUES (?, NULL, ?, ?)";
            $stmt_notification = $conn->prepare($sql_notification);
            $stmt_notification->bind_param("sii", $notification_text, $vic_id, $nearest_station_id);

            if ($stmt_notification->execute()) {
                $response["message"] .= "Notification added successfully.";
            } else {
                $response["success"] = false;
                $response["message"] .= "Failed to add notification.";
            }
            $stmt_notification->close();
        } else {
            $response["success"] = false;
            $response["message"] .= "Nearest fire station not found for the user.";
        }
    } else {
        $response["success"] = false;
        $response["message"] .= "Location not found for the victim.";
    }
} else {
    $response["success"] = false;
    $response["message"] .= "Failed to fetch location.";
}
$stmt_location->close();


$conn->close();

// Return the response as JSON
echo json_encode($response);
?>
