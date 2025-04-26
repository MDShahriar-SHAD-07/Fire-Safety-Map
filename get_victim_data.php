<?php
header("Content-Type: application/json");
require_once "db.php"; // Replace with your DB connection file

$response = ["success" => false, "message" => "", "victimData" => []];

if (isset($_GET['vic_id'])) {
    $vicID = intval($_GET['vic_id']); // Sanitize input

    try {
        $sql = "
            SELECT 
                `smoke_low`,
                `smoke_mid`,
                `smoke_high`,
                `victim_num_1_to_5`,
                `victim_num_5_to_10`,
                `victim_num_10_plus`,
                `building_location_ground_floor`,
                `building_location_2ndfloor_to_4thfloor`,
                `building_location_above_4thfloor`
            FROM victim_table
            WHERE Vic_ID = ?
        ";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $vicID);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $response["victimData"] = $result->fetch_assoc();
            $response["success"] = true;
        } else {
            $response["message"] = "No victim data found for Vic_ID: $vicID";
        }
    } catch (Exception $e) {
        $response["message"] = "Error: " . $e->getMessage();
    }
} else {
    $response["message"] = "Vic_ID is required.";
}

echo json_encode($response);
?>
