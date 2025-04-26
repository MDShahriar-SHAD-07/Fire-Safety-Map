<?php
$conn = new mysqli('localhost', 'root', '', 'project_fire_safety');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch fire station details from the database
$sql = "SELECT 
            St_name AS name, 
            Station_Coordinate AS coordinates, 
            St_contact AS phone, 
            Station_FireOfficer AS fire_officer, 
            St_address AS address, 
            Num_of_Fighter AS num_of_fighters 
        FROM `fire_station`";

$result = $conn->query($sql);

$fireStations = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $coords = json_decode($row['coordinates'], true);
        if ($coords) {
            $fireStations[] = [
                'name' => $row['name'],
                'lat' => $coords['lat'],
                'lng' => $coords['lng'],
                'phone' => $row['phone'],
                'fire_officer' => $row['fire_officer'],
                'address' => $row['address'],
                'num_of_fighters' => $row['num_of_fighters'],
            ];
        }
    }
}

header('Content-Type: application/json');
echo json_encode($fireStations);
?>
