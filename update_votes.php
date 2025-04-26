<?php
// Database connection
$conn = new mysqli('localhost', 'root', '', 'project_fire_safety');

// Check connection
if ($conn->connect_error) {
    die(json_encode(["status" => "error", "message" => "Database connection failed"]));
}

// Get JSON input from frontend
$input = json_decode(file_get_contents("php://input"), true);

// Validate input
if (!isset($input['road_id'], $input['question'], $input['answer'])) {
    echo json_encode(["status" => "error", "message" => "Missing required fields"]);
    exit;
}

$roadId = intval($input['road_id']);
$question = $conn->real_escape_string($input['question']);
$answer = $conn->real_escape_string($input['answer']);

// Define column mappings for questions
$columns = [
    "busy_all_time" => [
        "total" => "busy_all_time_Total_Vote_Count",
        "yes" => "busy_all_time_Yes_Vote_Count",
        "no" => "busy_all_time_No_Vote_Count",
        "sometimes" => "busy_all_time_Sometimes_Vote_Count",
    ],
    "too_narrow" => [
        "total" => "too_narrow_Total_Vote_Count",
        "yes" => "too_narrow_Yes_Vote_Count",
        "no" => "too_narrow_No_Vote_Count",
    ],
    "heavy_truck_pass_easily" => [
    "total" => "heavy_truck_pass_easily_Total_Vote_Count",
    "yes" => "heavy_truck_pass_easily_Yes_Vote_Count",
    "no" => "heavy_truck_pass_easily_No_Vote_Count",
    ],

    "under_construction" => [
        "total" => "under_construction_Total_Vote_Count",
        "yes" => "under_construction_Yes_Vote_Count",
        "no" => "under_construction_No_Vote_Count",
    ],
];

// Validate the question
if (!isset($columns[$question])) {
    echo json_encode(["status" => "error", "message" => "Invalid question"]);
    exit;
}

// Build SQL query
$totalColumn = $columns[$question]["total"];
$voteColumn = $columns[$question][$answer];

$sql = "
    UPDATE road 
    SET $totalColumn = $totalColumn + 1, $voteColumn = $voteColumn + 1, updated_at = NOW() 
    WHERE Road_ID = $roadId
";

// Execute the query
if ($conn->query($sql) === TRUE) {
    // Get updated vote percentages (assuming you want to return them to update the UI)
    $result = $conn->query("SELECT * FROM road WHERE Road_ID = $roadId");
    $row = $result->fetch_assoc();

    // Calculate the percentages based on vote counts
    $data = [
        'busy_all_time_percentage' => ($row['busy_all_time_Yes_Vote_Count'] / $row['busy_all_time_Total_Vote_Count']) * 100,
        'too_narrow_percentage' => ($row['too_narrow_Yes_Vote_Count'] / $row['too_narrow_Total_Vote_Count']) * 100,
        'heavy_truck_percentage' => ($row['heavy_truck_Yes_Vote_Count'] / $row['heavy_truck_Total_Vote_Count']) * 100,
        'under_construction_percentage' => ($row['under_construction_Yes_Vote_Count'] / $row['under_construction_Total_Vote_Count']) * 100,
    ];

    echo json_encode(["status" => "success", "message" => "Vote updated", "data" => $data]);
} else {
    echo json_encode(["status" => "error", "message" => $conn->error]);
}

// Close the database connection
$conn->close();
?>
