<?php
// Controlla se la cartella esiste, altrimenti creala
$directory = 'resident_memory';
if (!is_dir($directory)) {
mkdir($directory, 0755, true);
}

// Ottieni il contenuto e il nome del file dalla richiesta POST
$content = isset($_POST['content']) ? $_POST['content'] : '';
$filename = isset($_POST['filename']) ? $_POST['filename'] : '';

if (!empty($content) && !empty($filename)) {
// Specifica il percorso completo del file
$filePath = $directory . '/' . $filename . '.txt';

// Salva il contenuto nel file
if (file_put_contents($filePath, $content) !== false) {
echo "File salvato con successo.";
} else {
echo "Errore nel salvataggio del file.";
}
} else {
echo "Dati non validi.";
}
?>
