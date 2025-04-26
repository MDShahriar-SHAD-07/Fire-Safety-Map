<?php
$conn = new mysqli('localhost', 'root', '', 'project_fire_safety');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set the content type to JSON
header('Content-Type: application/json');

// Query to fetch all roads
$query = "SELECT Road_ID, Road_Name, Road_Coordinates FROM road";
$result = $conn->query($query);

$roads = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Decode the road coordinates
        $coordinates = json_decode($row['Road_Coordinates'], true);

        // Calculate the midpoint
        $midpoint = ['lat' => 0, 'lng' => 0];
        if (!empty($coordinates)) {
            $totalPoints = count($coordinates);
            foreach ($coordinates as $coord) {
                $midpoint['lat'] += $coord['lat'];
                $midpoint['lng'] += $coord['lng'];
            }
            $midpoint['lat'] /= $totalPoints;
            $midpoint['lng'] /= $totalPoints;
        }

        // Add the midpoint and original coordinates to the response
        $row['Midpoint'] = $midpoint;
        $row['Road_Coordinates'] = $coordinates;

        $roads[] = $row;
    }
}

// Return the roads data as JSON
echo json_encode($roads);

// Close the database connection
$conn->close();
?>