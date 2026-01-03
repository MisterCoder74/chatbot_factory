<?php
// Inizia la sessione
session_start();

// Cancella tutte le variabili di sessione
$_SESSION = [];

// Se desideri distruggere anche la sessione, puoi farlo
session_destroy();

// Reindirizza alla pagina index.html
header("Location: index.html");
exit();
?>