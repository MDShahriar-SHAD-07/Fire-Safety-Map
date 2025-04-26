<?php
header("Content-Type: application/json");
require_once "db.php"; // Replace with your DB connection file

$response = ["success" => false, "message" => "", "roads" => []];

if (isset($_GET['Vic_ID'])) {
    $vicID = $_GET['Vic_ID'];

    try {
        // Fetch user coordinates by joining victim_table and user
        $sqlUser = "
            SELECT u.User_loc_coordinate
            FROM victim_table vt
            JOIN user u ON vt.User_ID = u.User_ID
            WHERE vt.Vic_ID = ?
        ";
        $stmtUser = $conn->prepare($sqlUser);
        $stmtUser->bind_param("i", $vicID);
        $stmtUser->execute();
        $resultUser = $stmtUser->get_result();

        if ($resultUser->num_rows > 0) {
            $userData = $resultUser->fetch_assoc();
            $userCoordinate = $userData["User_loc_coordinate"];
            list($userLat, $userLon) = explode(',', $userCoordinate);

            // Fetch roads within 3km
            $sqlRoads = "
                SELECT 
                    r.Road_Name,
                    r.too_narrow_Yes_Vote_Count,
                    r.too_narrow_No_Vote_Count,
                    r.too_narrow_Total_Vote_Count,
                    r.heavy_truck_pass_easily_Yes_Vote_Count,
                    r.heavy_truck_pass_easily_No_Vote_Count,
                    r.heavy_truck_pass_easily_Total_Vote_Count,
                    r.under_construction_No_Vote_Count,
                    r.under_construction_Total_Vote_Count,
                    JSON_EXTRACT(r.Road_Coordinates, '$[0].lat') AS roadLat,
                    JSON_EXTRACT(r.Road_Coordinates, '$[0].lng') AS roadLng
                FROM road r
                WHERE 
                    ST_Distance_Sphere(
                        Point(?, ?), 
                        Point(
                            JSON_EXTRACT(r.Road_Coordinates, '$[0].lng'),
                            JSON_EXTRACT(r.Road_Coordinates, '$[0].lat')
                        )
                    ) <= 3000
            ";
            $stmtRoads = $conn->prepare($sqlRoads);
            $stmtRoads->bind_param("dd", $userLon, $userLat);
            $stmtRoads->execute();
            $resultRoads = $stmtRoads->get_result();

            if ($resultRoads->num_rows > 0) {
                while ($row = $resultRoads->fetch_assoc()) {
                    $response["roads"][] = $row;
                }
                $response["success"] = true;
            } else {
                $response["message"] = "No roads found within 3km.";
            }
        } else {
            $response["message"] = "No user found for the given Vic_ID.";
        }
    } catch (Exception $e) {
        $response["message"] = "Error: " . $e->getMessage();
    }
} else {
    $response["message"] = "Vic_ID is required.";
}

echo json_encode($response);
?>
