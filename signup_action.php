<?php
include('db.php'); // Your database connection file

$name = $_POST['name'];
$email = $_POST['email'];
$phone = $_POST['phone'];
$location = $_POST['location'];
$password = password_hash($_POST['password'], PASSWORD_DEFAULT); // Hash the password
$role = $_POST['role'];

echo "Role: " . $role . "<br>";  // Debugging: Print role to ensure it's passed correctly

$conn->begin_transaction();

try {
    // Insert user into user table
    $user_sql = "INSERT INTO `user` (`User_Name`, `User_Email`, `User_PhoneNo`, `User_Location`, `User_Password`, `User_Role`) 
                 VALUES (?, ?, ?, ?, ?, ?)";
    $user_stmt = $conn->prepare($user_sql);
    $user_stmt->bind_param("ssssss", $name, $email, $phone, $location, $password, $role);
    
    if ($user_stmt->execute()) {
        $user_id = $conn->insert_id;
        echo "User inserted with ID: " . $user_id . "<br>"; // Debugging: Check if user is inserted

        // Insert fire station if role is fire_officer
        if ($role === "fire_officer") {
            $fire_station_name = $_POST['fire_station_name'];
            $num_of_fighters = $_POST['num_of_fighters'];
            
            echo "Fire Station Name: " . $fire_station_name . "<br>";  // Debugging
            echo "Number of Fighters: " . $num_of_fighters . "<br>";  // Debugging

            // Insert into fire_station table
            $station_sql = "INSERT INTO `fire_station` (`St_name`, `Station_FireOfficer`, `St_address`, `St_contact`, `Num_of_Fighter`, `Fire_InfoFire_Info_ID`) 
                            VALUES (?, ?, ?, ?, ?, NULL)";
            $station_stmt = $conn->prepare($station_sql);
            $station_stmt->bind_param("ssssi", $fire_station_name, $name, $location, $phone, $num_of_fighters);

            if (!$station_stmt->execute()) {
                throw new Exception("Error inserting into fire_station table: " . $station_stmt->error);
            } else {
                echo "Fire Station inserted successfully.<br>";
            }
        }

        $conn->commit();
        echo "Sign up successful!";
        header("Location: login.html");
        exit();
    } else {
        throw new Exception("Error inserting into user table: " . $user_stmt->error);
    }
} catch (Exception $e) {
    $conn->rollback();
    echo "Error: " . $e->getMessage();
}

$user_stmt->close();
if (isset($station_stmt)) $station_stmt->close();
$conn->close();
?>
