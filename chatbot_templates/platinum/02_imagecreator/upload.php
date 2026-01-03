<?php
function uploadFile() {
    $targetDir = "./upload/";

    // Controlla se la cartella upload esiste e, in caso contrario, creala
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    $fileType = strtolower(pathinfo($_FILES["file"]["name"], PATHINFO_EXTENSION));
    $originalFileName = basename($_FILES["file"]["name"]);
    $targetFile = $targetDir . $originalFileName;
    $uploadOk = 1;

    // Verifica se il file è un documento TXT
    if ($fileType != "txt" || $fileType != "html" || $fileType != "css" || $fileType != "js" || $fileType != "json" || $fileType != "php" || $fileType != "py" ||  $fileType != "vb" || $fileType != "ini" || $fileType != "cpp") {
        echo "File type is not allowed.";
        $uploadOk = 0;
    }

    // Verifica se ci sono errori durante il caricamento
    if ($uploadOk == 0) {
        echo "<b>Your file was NOT uploaded.</b><br>Make sure the file is an allowed format and has less than 15000 chars.";
    } else {
        if (move_uploaded_file($_FILES["file"]["tmp_name"], $targetFile)) {
            echo json_encode(["status" => "success", "url" => $targetFile]);
        } else {
            echo "An error occurred.";
        }
    }
}

uploadFile();
?>
