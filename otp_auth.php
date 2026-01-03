<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST["email"];
    $otp = $_POST["otp"];

    $json_data = file_get_contents("userdata.json");
    $data_array = json_decode($json_data, true);

    $authenticated = false;
    $user_data = null;

    foreach ($data_array as &$user) {
        if ($user["email"] == $email && $user["status"] == "pending" && $user["otp"] == $otp) {
            $authenticated = true;
            $user["status"] = "active";
            $user_data = $user;
            break;
        }
    }

    if ($authenticated) {
        $updated_json_data = json_encode($data_array, JSON_PRETTY_PRINT);
        file_put_contents("userdata.json", $updated_json_data);

        session_start();
        $_SESSION["user_id"] = $user_data["id"];
            
// Messaggio di validazione e reindirizzamento
echo "
<!DOCTYPE html>
<html lang='en'>
<head>
<meta charset='UTF-8'>
<meta name='viewport' content='width=device-width, initial-scale=1.0'>
<title>OTP Authentication Result</title>
<script>
setTimeout(function() {
window.location.href = 'index.html';
}, 3000); // 3000 millisecondi = 3 secondi
</script>
</head>
<body>
<h1>Your authentication was successfull!</h1>
<p>You will be redirected to the login page in 3 seconds.</p>
</body>
</html>
";
exit;
} else {
echo "<script>alert('Invalid email, OTP, or account is not approved.');</script>";
}
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Authentication Page</title>
    <style>
            /* Reset some default styles */
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    /* Base styles */
    body {
      font-family: Arial, sans-serif;
      background-color: #f5f5f5;
      color: #333;
            text-align: center;
    }

    .container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 20px;
    }

    /* Header styles */
    header {
      background-color: #4CAF50;
      color: #fff;
      padding: 20px;
      text-align: center;
      font-size: 24px;
      font-weight: bold;
    }
            
    /* Form styles */
    form {
      display: flex;
      flex-direction: column;
      align-items: center;
      margin: .5rem auto;      
    }

    input {
      width: 40%;
      padding: 10px;
      margin: 10px 0;
      border: 1px solid #ccc;
      border-radius: 4px;
    }

    button {
      background-color: #4CAF50;
      border: none;
      color: #fff;
      padding: 10px 20px;
      text-align: center;
      text-decoration: none;
      display: inline-block;
      font-size: 14px;
      border-radius: 4px;
      cursor: pointer;
    }            
    </style>
</head>
<body>
    <header>Vivacity Design</header>
    <div class="container">
        <div class="modal-content">
            <h2>OTP Check</h2>
            <form method="post">
                <input type="email" name="email" placeholder="Email" required>
                <input type="text" name="otp" placeholder="OTP" required>
                <button type="submit">Authenticate</button>
            </form>
        </div>
    </div>
</body>
</html>