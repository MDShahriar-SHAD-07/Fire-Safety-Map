<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include database connection
include('db.php'); // Ensure the path is correct

// Read incoming JSON data
$data = json_decode(file_get_contents("php://input"), true);

// Debug: Check if JSON data is received
if (!$data) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid JSON received', 'input' => file_get_contents("php://input")]);
    exit;
}

// Extract variables (add error checks for missing fields)
$roadName = $data['road_name'] ?? null;
$coordinates = $data['road_coordinates'] ?? null;
$busyYes = $data['busy_all_time_Yes_Vote_Count'] ?? 0;
$busyNo = $data['busy_all_time_No_Vote_Count'] ?? 0;
$busySometimes = $data['busy_all_time_Sometimes_Vote_Count'] ?? 0;
$narrowYes = $data['too_narrow_Yes_Vote_Count'] ?? 0;
$narrowNo = $data['too_narrow_No_Vote_Count'] ?? 0;
$truckYes = $data['heavy_truck_pass_easily_Yes_Vote_Count'] ?? 0;
$truckNo = $data['heavy_truck_pass_easily_No_Vote_Count'] ?? 0;
$constructionYes = $data['under_construction_Yes_Vote_Count'] ?? 0;
$constructionNo = $data['under_construction_No_Vote_Count'] ?? 0;

// Debug: Check if essential variables are present
if (!$roadName || !$coordinates) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields: road_name or road_coordinates']);
    exit;
}

// Validate coordinates format (optional: add stricter validation for JSON)
if (!is_array($coordinates) && json_decode($coordinates) === null) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid road_coordinates format. It must be a valid JSON array.']);
    exit;
}

// Convert coordinates to JSON if needed
$coordinatesJson = is_array($coordinates) ? json_encode($coordinates) : $coordinates;

// Check if the road exists
$stmt = $conn->prepare("SELECT * FROM road WHERE Road_Name = ?");
if (!$stmt) {
    echo json_encode(['status' => 'error', 'message' => 'SQL prepare error: ' . $conn->error]);
    exit;
}

$stmt->bind_param("s", $roadName);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Road exists, update vote counts
    $row = $result->fetch_assoc();

    // Increment total vote count
    $newTotalVotes = $row['busy_all_time_Total_Vote_Count'] + 1;

    // Prepare the update query
    $updateStmt = $conn->prepare("
        UPDATE road SET
        busy_all_time_Total_Vote_Count = busy_all_time_Total_Vote_Count + 1,
        busy_all_time_Yes_Vote_Count = busy_all_time_Yes_Vote_Count + ?,
        busy_all_time_No_Vote_Count = busy_all_time_No_Vote_Count + ?,
        busy_all_time_Sometimes_Vote_Count = busy_all_time_Sometimes_Vote_Count + ?,
        too_narrow_Total_Vote_Count = too_narrow_Total_Vote_Count + 1,
        too_narrow_Yes_Vote_Count = too_narrow_Yes_Vote_Count + ?,
        too_narrow_No_Vote_Count = too_narrow_No_Vote_Count + ?,
        heavy_truck_pass_easily_Total_Vote_Count = heavy_truck_pass_easily_Total_Vote_Count + 1,
        heavy_truck_pass_easily_Yes_Vote_Count = heavy_truck_pass_easily_Yes_Vote_Count + ?,
        heavy_truck_pass_easily_No_Vote_Count = heavy_truck_pass_easily_No_Vote_Count + ?,
        under_construction_Total_Vote_Count = under_construction_Total_Vote_Count + 1,
        under_construction_Yes_Vote_Count = under_construction_Yes_Vote_Count + ?,
        under_construction_No_Vote_Count = under_construction_No_Vote_Count + ?
    WHERE Road_ID = ?
    ");

    if (!$updateStmt) {
        echo json_encode(['status' => 'error', 'message' => 'SQL prepare error: ' . $conn->error]);
        exit;
    }

    $updateStmt->bind_param(
        "iiiiiiiiiiis",
        $newTotalVotes, $busyYes, $busyNo, $busySometimes,
        $narrowYes, $narrowNo,
        $truckYes, $truckNo,
        $constructionYes, $constructionNo,
        $roadName
    );

    if ($updateStmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Vote updated successfully.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error updating vote: ' . $updateStmt->error]);
    }
} else {
    // Road does not exist, insert a new record
    $insertStmt = $conn->prepare("
        INSERT INTO road (
            Road_Name, Road_Coordinates,
            busy_all_time_Total_Vote_Count, busy_all_time_Yes_Vote_Count, busy_all_time_No_Vote_Count, busy_all_time_Sometimes_Vote_Count,
            too_narrow_Total_Vote_Count, too_narrow_Yes_Vote_Count, too_narrow_No_Vote_Count,
            heavy_truck_pass_easily_Total_Vote_Count, heavy_truck_pass_easily_Yes_Vote_Count, heavy_truck_pass_easily_No_Vote_Count,
            under_construction_Total_Vote_Count, under_construction_Yes_Vote_Count, under_construction_No_Vote_Count
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    if (!$insertStmt) {
        echo json_encode(['status' => 'error', 'message' => 'SQL prepare error: ' . $conn->error]);
        exit;
    }

    $totalVotes = 1;

    $insertStmt->bind_param(
        "ssiiiiiiiiiiiii",
        $roadName, $coordinatesJson,
        $totalVotes, $busyYes, $busyNo, $busySometimes,
        $totalVotes, $narrowYes, $narrowNo,
        $totalVotes, $truckYes, $truckNo,
        $totalVotes, $constructionYes, $constructionNo
    );

    if ($insertStmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'New road added successfully.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error inserting road: ' . $insertStmt->error]);
    }
}

$conn->close();
?>
