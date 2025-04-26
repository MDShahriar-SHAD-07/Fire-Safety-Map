<?php
// Include database connection
require_once 'db.php';

header('Content-Type: application/json');

// Get the JSON data from the request
$data = json_decode(file_get_contents("php://input"), true);

// Check if User_ID is provided
if (isset($data['User_id'])) {
    $user_id = $data['User_id']; // Fetch user ID

    // Fetch the user's location from the `user` table
    $queryUser = "SELECT User_loc_coordinate FROM user WHERE User_ID = ?";
    $stmtUser = $conn->prepare($queryUser);

    if (!$stmtUser) {
        echo json_encode(["success" => false, "message" => "Failed to prepare user statement."]);
        exit;
    }

    $stmtUser->bind_param("i", $user_id);
    $stmtUser->execute();
    $resultUser = $stmtUser->get_result();

    if ($resultUser->num_rows === 0) {
        echo json_encode(["success" => false, "message" => "User not found."]);
        exit;
    }

    $user = $resultUser->fetch_assoc();
    $userLocCoordinate = explode(',', $user['User_loc_coordinate']); // Split coordinates into lat, lng
    $userLat = floatval($userLocCoordinate[0] ?? 0);
    $userLng = floatval($userLocCoordinate[1] ?? 0);

    if (!$userLat || !$userLng) {
        echo json_encode(["success" => false, "message" => "Invalid user coordinates."]);
        exit;
    }

    // Fetch fire station data
    $queryStations = "SELECT Station_ID, Station_Coordinate FROM fire_station";
    $resultStations = $conn->query($queryStations);

    if ($resultStations->num_rows === 0) {
        echo json_encode(["success" => false, "message" => "No fire stations found."]);
        exit;
    }

    // Find the nearest fire station
    $nearestStationId = null;
    $minDistance = PHP_INT_MAX;

    while ($station = $resultStations->fetch_assoc()) {
        $stationCoordinates = json_decode($station['Station_Coordinate'], true); // Decode JSON format
        $stationLat = $stationCoordinates['lat'] ?? null;
        $stationLng = $stationCoordinates['lng'] ?? null;

        if ($stationLat && $stationLng) {
            // Calculate distance (Haversine formula)
            $distance = sqrt(
                pow($userLat - $stationLat, 2) + pow($userLng - $stationLng, 2)
            );

            if ($distance < $minDistance) {
                $minDistance = $distance;
                $nearestStationId = $station['Station_ID'];
            }
        }
    }

    if ($nearestStationId === null) {
        echo json_encode(["success" => false, "message" => "No nearest station found."]);
        exit;
    }

    // Update the user's nearest fire station in the database
    $queryUpdate = "UPDATE user SET Nearest_Fire_Station_ID = ? WHERE User_ID = ?";
    $stmtUpdate = $conn->prepare($queryUpdate);

    if (!$stmtUpdate) {
        echo json_encode(["success" => false, "message" => "Failed to prepare update statement."]);
        exit;
    }

    $stmtUpdate->bind_param("ii", $nearestStationId, $user_id);

    if ($stmtUpdate->execute()) {
        echo json_encode([
            "success" => true,
            "message" => "Nearest fire station updated successfully.",
            "Nearest_Station_ID" => $nearestStationId,
            "Distance" => $minDistance,
        ]);
    } else {
        echo json_encode(["success" => false, "message" => "Failed to update nearest fire station."]);
    }

    $stmtUpdate->close();
} else {
    echo json_encode(["success" => false, "message" => "User_ID is required."]);
}

$conn->close();
