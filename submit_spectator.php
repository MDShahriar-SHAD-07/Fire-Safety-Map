<?php
header("Content-Type: application/json");
require 'db.php';

// Check if Spec_ID is provided
if (!isset($_POST['Spec_ID'])) {
    echo json_encode(["success" => false, "message" => "Spec_ID is required."]);
    exit();
}

$spec_id = intval($_POST['Spec_ID']);
$fire_intensity = $_POST['fire_intensity'] ?? null;
$casualties = $_POST['casualties'] ?? null;
$resources = $_POST['resources'] ?? [];
$spec_picture = $_FILES['spec_picture'] ?? null;

// Prepare default responses
$response = ["success" => true, "message" => ""];

// Update Fire Intensity
if ($fire_intensity) {
    $fire_low = $fire_intensity === 'low' ? 1 : 0;
    $fire_mid = $fire_intensity === 'medium' ? 1 : 0;
    $fire_high = $fire_intensity === 'high' ? 1 : 0;

    $sql = "UPDATE spectator_table 
            SET `intensity of the fire_Low` = ?, 
                `intensity of the fire_Mid` = ?, 
                `intensity of the fire_High` = ? 
            WHERE Spec_ID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiii", $fire_low, $fire_mid, $fire_high, $spec_id);

    if ($stmt->execute()) {
        $response["message"] .= "Fire intensity updated. ";
    } else {
        $response["success"] = false;
        $response["message"] .= "Failed to update fire intensity. ";
    }
    $stmt->close();
}

// Update Casualties
if ($casualties) {
    $injured_no = $casualties === 'none' ? 1 : 0;
    $injured_few = $casualties === 'few' ? 1 : 0;
    $injured_huge = $casualties === 'many' ? 1 : 0;

    $sql = "UPDATE spectator_table 
            SET injured_No = ?, 
                injured_Few = ?, 
                injured_Huge = ? 
            WHERE Spec_ID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiii", $injured_no, $injured_few, $injured_huge, $spec_id);

    if ($stmt->execute()) {
        $response["message"] .= "Casualties updated. ";
    } else {
        $response["success"] = false;
        $response["message"] .= "Failed to update casualties. ";
    }
    $stmt->close();
}

// Update Resources
$resource_water = in_array('water', $resources) ? 1 : 0;
$resource_treatment = in_array('medical', $resources) ? 1 : 0;
$resource_rescue = in_array('rescue', $resources) ? 1 : 0;

$sql = "UPDATE spectator_table 
        SET Need_Resource_Water = ?, 
            Need_Resource_Treatement = ?, 
            Need_Resource_Rescue = ? 
        WHERE Spec_ID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iiii", $resource_water, $resource_treatment, $resource_rescue, $spec_id);

if ($stmt->execute()) {
    $response["message"] .= "Resources updated. ";
} else {
    $response["success"] = false;
    $response["message"] .= "Failed to update resources. ";
}
$stmt->close();

// Handle Image Upload
if ($spec_picture) {
    if ($spec_picture['error'] === UPLOAD_ERR_OK) {
        // Log the file size and type for debugging
        error_log('File uploaded: ' . $spec_picture['name'] . ' (' . $spec_picture['size'] . ' bytes)');
        
        $picture_blob = file_get_contents($spec_picture['tmp_name']);

        // Check if file_get_contents worked properly
        if ($picture_blob === false) {
            echo json_encode(["success" => false, "message" => "Failed to read the image file."]);
            exit();
        }

        $sql = "UPDATE spectator_table SET spec_picture = ? WHERE Spec_ID = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("bi", $picture_blob, $spec_id);

        if ($stmt->execute()) {
            $response["message"] .= "Image uploaded successfully. ";
        } else {
            $response["success"] = false;
            $response["message"] .= "Failed to upload image. ";
        }
        $stmt->close();
    } else {
        $response["success"] = false;
        $response["message"] .= "Error uploading image: " . $spec_picture['error'];
    }
}

$conn->close();
// Return the response as JSON
echo json_encode($response);
?>
