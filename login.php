<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST["email"];
    $password = $_POST["password"];
    

    $json_data = file_get_contents("userdata.json");
    $data_array = json_decode($json_data, true);
    

    $authenticated = false;
    $user_data = null;

    foreach ($data_array as $user) {
        if ($user["email"] == $email && $user["password"] == $password && $user["status"] == "active") {
            $authenticated = true;
            $user_data = $user;
            break;
        }
    }

    if ($authenticated) {
        session_start();
        $_SESSION["user_id"] = $user_data["id"];
        header("Location: dashboard.php");
        exit;
    } else {
        echo "Invalid email, password, or account is not active.";
    }
}
?>

