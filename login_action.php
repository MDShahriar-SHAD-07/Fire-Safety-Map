<?php
// Include your database connection file
include('db.php');

// Start session for user tracking
session_start();


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $data = json_decode(file_get_contents("php://input"), true);

    if (!empty($data['email']) && !empty($data['password']) && !empty($data['role_hidden'])) {
        $email = trim($data['email']);
        $password = $data['password'];
        $selectedRole = strtolower(trim($data['role_hidden']));

        $query = "SELECT * FROM user WHERE User_Email = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $dbRole = strtolower(trim($user['User_Role']));

            if (password_verify($password, $user['User_Password'])) {
                if ($dbRole === $selectedRole) {
                    $_SESSION['User_ID'] = $user['User_ID'];
                    $_SESSION['User_Name'] = $user['User_Name'];
                    $_SESSION['User_Role'] = $user['User_Role'];

                    echo json_encode([
                        "success" => true,
                        "User_ID" => $user['User_ID'],
                        "User_Name" => $user['User_Name'],
                        "User_Role" => $user['User_Role'],
                        "redirect" => $dbRole === "civilian" ? "civilian_dashboard.html" : "fire_dashboard.html",
                    ]);
                } else {
                    echo json_encode(["success" => false, "message" => "The role you selected does not match our records."]);
                }
            } else {
                echo json_encode(["success" => false, "message" => "Incorrect password."]);
            }
        } else {
            echo json_encode(["success" => false, "message" => "ব্যবহারকারীর নাম খুঁজে পাওয়া যায়নি।"]);
        }
    } else {
        echo json_encode(["success" => false, "message" => "Please provide email, password, and role."]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Invalid request method."]);
}

$stmt->close();
$conn->close();
?>
