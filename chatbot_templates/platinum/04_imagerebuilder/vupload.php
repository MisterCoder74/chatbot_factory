<?php
function uploadFile() {
    $targetDir = "./upload/";

    // Controlla se la cartella esiste, altrimenti la crea
    if (!is_dir($targetDir)) {
        if (!mkdir($targetDir, 0755, true)) {
            echo "Errore: impossibile creare la cartella di upload.";
            return;
        }
    }

    $fileType = strtolower(pathinfo($_FILES["file"]["name"], PATHINFO_EXTENSION));
    $originalFileName = basename($_FILES["file"]["name"]);
    $targetFile = $targetDir . $originalFileName;
    $uploadOk = 1;

    // Verifica se il file è un documento jpg o png
    if ($fileType != "jpg" && $fileType != "png") {
        echo "Only JPG and PNG files are allowed.";
        $uploadOk = 0;
    }

    // Verifica se ci sono errori durante il caricamento
    if ($uploadOk == 0) {
        echo "<b>Your file was NOT uploaded.</b><br>Make sure the file is in JPG or PNG format.";
    } else {
        if (move_uploaded_file($_FILES["file"]["tmp_name"], $targetFile)) {
            // Restituisce l'URL del file caricato in formato JSON
            echo json_encode(["status" => "success", "url" => $targetFile]);
        } else {
            echo "An error occurred during file upload.";
        }
    }
}

uploadFile();
?>
