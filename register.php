<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
$email = $_POST["email"];
$password = $_POST["password"];
$otp = rand(100000, 999999);
$status = "pending";
$plan_type = "Free";
$signup_date = date("Y-m-d H:i:s");
$lastseen_date = $signup_date;
$credits = 1000;
$chatbots = [];
$personas = [];        

// Creazione dell'ID unico per l'utente
$user_id = uniqid();

$user_data = [
"id" => $user_id,
"email" => $email,
"password" => $password,
"otp" => $otp,
"status" => $status,
"plan_type" => $plan_type,
"signup_date" => $signup_date,
"lastseen_date" => $lastseen_date,
"credits" => $credits,
"chatbots" => $chatbots,
"personas" => $personas        
];

// Aggiornamento del file JSON con i dati dell'utente
$json_data = file_get_contents("userdata.json");
$data_array = json_decode($json_data, true);
$data_array[] = $user_data;
$updated_json_data = json_encode($data_array, JSON_PRETTY_PRINT);
file_put_contents("userdata.json", $updated_json_data);

// Creazione delle cartelle dell'utente
$rootpath = $user_id . '/';
$subfolders = [
'chatbots',
'personas'
];

// Creazione delle cartelle
foreach ($subfolders as $folder) {
$path = $rootpath . $folder;
if (!mkdir($path, 0777, true) && !is_dir($path)) {
throw new \RuntimeException("Directory '$path' non è stata creata");
}
}

// Invio OTP via email
$to = $email;
$subject = "Your Vivacity Design OTP";
$message = "Your one-time password (OTP) is: " . $otp;
mail($to, $subject, $message);

echo "User registered successfully! Please check your email for the OTP.<br>
Go to the <a href=\"otp_auth.php\">OTP Authentipagion page</a>.";
}
?>