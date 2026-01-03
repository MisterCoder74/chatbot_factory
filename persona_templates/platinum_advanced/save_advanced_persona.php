<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
// Retrieve form data
$data = [
"Name" => $_POST['name'],
"Surname" => $_POST['surname'],
"BirthDate" => $_POST['birthdate'],
"BirthDay" => $_POST['birthday'],
"BirthPlace" => $_POST['birthplace'],
"ZodiacalSign" => $_POST['zodiacsign'],
"Img" => uploadFile($_FILES['img']),
"Height" => $_POST['height'],
"Weight" => $_POST['weight'],
"Background" => $_POST['biography'],
"Website" => $_POST['website'], 
"Email" => $_POST['email'],         
"CurrentAddress" => $_POST['currentaddress'],
"CurrentAddressMoveDate" => $_POST['currentaddressmovedate'],
"PreviousAddresses" => [
[
"Address1" => [
"Address" => $_POST['address1'],
"FromDate" => $_POST['address1_from'],
"ToDate" => $_POST['address1_to']
]
],
[        
"Address2" => [
"Address" => $_POST['address2'],
"FromDate" => $_POST['address2_from'],
"ToDate" => $_POST['address2_to']
]
],
[        
"Address3" => [
"Address" => $_POST['address3'],
"FromDate" => $_POST['address3_from'],
"ToDate" => $_POST['address3_to']
]
]
],        
"MaritalStatus" => $_POST['maritalstatus'],
"SpouseSurname" => $_POST['spousesurname'],
"SpouseName" => $_POST['spouse'],
"SpouseBirthdate" => $_POST['spousebirthdate'],
"SpouseBirthplace" => $_POST['spousebirthplace'],
"SpouseImg" => uploadFile($_FILES['spouseimg']),
];

// Load existing data
$filename = 'persona_details.json';
$existingData = [];
if (file_exists($filename)) {
$existingData = json_decode(file_get_contents($filename), true);
$existingData['Persona'] = [$data];
} else {
$existingData['Persona'] = [$data];
}

// Save the new data back to the file
file_put_contents($filename, json_encode($existingData, JSON_PRETTY_PRINT));
//file_put_contents($filename, json_encode($data, JSON_PRETTY_PRINT));        
echo "Data saved/updated successfully.";
}

// Function to handle file uploads
function uploadFile($file) {
if (isset($file) && $file['error'] == 0) {
$targetDir = "uploads/";
$targetFile = $targetDir . basename($file["name"]);

// Create the target directory if it doesn't exist
if (!is_dir($targetDir)) {
mkdir($targetDir, 0777, true);
}

move_uploaded_file($file["tmp_name"], $targetFile);
return $targetFile; // Return the uploaded file path
}
return null; // Return null if there was no upload
}
?>