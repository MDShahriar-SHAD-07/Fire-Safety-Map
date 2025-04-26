<?php
header("Content-Type: application/json");
require_once "db.php"; // Replace with your DB connection file

$response = ["success" => false, "message" => "", "spectatorData" => []];

if (isset($_GET['spec_id'])) {
    $specID = intval($_GET['spec_id']); // Sanitize input

    try {
        $sql = "
            SELECT 
                `intensity of the fire_Low`,
                `intensity of the fire_Mid`,
                `intensity of the fire_High`,
                `injured_No`,
                `injured_Few`,
                `injured_Huge`,
                `Need_Resource_Water`,
                `Need_Resource_Treatement`,
                `Need_Resource_Rescue`
            FROM spectator_table
            WHERE Spec_ID = ?
        ";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $specID);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $response["spectatorData"] = $result->fetch_assoc();
            $response["success"] = true;
        } else {
            $response["message"] = "No spectator data found for Spec_ID: $specID";
        }
    } catch (Exception $e) {
        $response["message"] = "Error: " . $e->getMessage();
    }
} else {
    $response["message"] = "Spec_ID is required.";
}

echo json_encode($response);
?>
