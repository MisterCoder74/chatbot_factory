<?php
session_start();

// Verifica se l'utente è loggato
if (!isset($_SESSION["user_id"])) {
    die("Accesso non autorizzato");
}

$user_id = $_SESSION["user_id"];
$persona_id = $_GET['id'] ?? null;

// Funzione per leggere i dati della persona
function getPersonaData($user_id, $persona_id) {
    $conf_path = $user_id . '/personas/' . $persona_id . '/conf.json';
    if (!file_exists($conf_path)) {
        return null;
    }
    return json_decode(file_get_contents($conf_path), true)[0] ?? null;
}

// Gestione richiesta di aggiornamento
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    try {
        $destination_conf = $user_id . '/personas/' . $persona_id . '/conf.json';

        if (!file_exists($destination_conf)) {
            throw new Exception('File di configurazione non trovato');
        }

        $background_color = $_POST['background_color'] ?? '';
        $text_color = $_POST['text_color'] ?? '';
        $style_color = $_POST['style_color'] ?? '';
        $persona_name = $_POST['persona_name'] ?? '';
        $persona_heading = $_POST['persona_heading'] ?? '';
        $persona_apikey = $_POST['persona_apikey'] ?? '';    

        $conf_data = json_decode(file_get_contents($destination_conf), true);

        if (!empty($conf_data)) {
            $conf_data[0]['background_color'] = $background_color;
            $conf_data[0]['text_color'] = $text_color;
            $conf_data[0]['style_color'] = $style_color;
            $conf_data[0]['persona_name'] = $persona_name;
            $conf_data[0]['header'] = $persona_heading;
            $conf_data[0]['persona_apikey'] = $persona_apikey;    
        }

        if (file_put_contents($destination_conf, json_encode($conf_data, JSON_PRETTY_PRINT)) === false) {
            throw new Exception('Error while writing conf.json');
        }

        echo json_encode([
            'success' => true, 
            'message' => 'Persona successfully updated!', 
            'persona_id' => $persona_id
        ]);
        exit;
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}

// Ottieni i dati attuali del chatbot
$personaData = getPersonaData($user_id, $persona_id);
if (!$personaData) {
    die("Persona not found");
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Modifica Persona</title>
    <style>
.modal {
position: fixed;
z-index: 1;
left: 0;
top: 0;
width: 100%;
height: 100%;
overflow: auto;
background-color: rgb(0,0,0);
background-color: rgba(0,0,0,0.4);
padding-top: 60px;
}

.modal-content {
background-color: #fefefe;
margin: 5% auto;
padding: 20px;
border: 1px solid #888;
width: 40%;
}

.close-button {
color: #aaa;
float: right;
font-size: 28px;
font-weight: bold;
}

.close-button:hover,
.close-button:focus {
color: black;
text-decoration: none;
cursor: pointer;
} 
            
 /* Form styles */
    form {
      display: flex;
      flex-direction: column;
      align-items: center;
    }

    input[type=text] {
      width: 50%;
      padding: 10px;
      margin: 10px 0;
      border: 1px solid #ccc;
      border-radius: 4px;
    }
    input[type=color] {
            width: 50%;
            height: 48px;
      padding: 2px;
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
            margin-top: .5rem;
    }
            
        label {
            margin-top: 10px;
            display: block;
        }
        input {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
        }
        #edit-confirm-button {
            width: 100%;
            padding: 10px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div id="editModal" class="modal" style="display: block;">
        <div class="modal-content">
            <span class="close-button" onclick="window.close()">&times;</span>
            <h2>Modifica Chatbot: <?php echo htmlspecialchars($personaData['persona_name']); ?></h2>
            
            <label for="edit-chatbot-name">Persona Name:</label>
            <input type="text" id="edit-chatbot-name" name="chatbot-name" 
                   value="<?php echo htmlspecialchars($personaData['persona_name'] ?? ''); ?>" 
                   placeholder="Insert a name" required>
            
            <label for="edit-chatbot-heading">Persona Heading:</label>
            <input type="text" id="edit-chatbot-heading" name="chatbot-heading" 
                   value="<?php echo htmlspecialchars($personaData['header'] ?? ''); ?>" 
                   placeholder="Insert a heading" required>
            
            <label for="edit-chatbot-apikey">OpenAI API Key:</label>
            <input type="text" id="edit-chatbot-apikey" name="persona_apikey" 
                   value="<?php echo htmlspecialchars($personaData['persona_apikey'] ?? ''); ?>" 
                   placeholder="Insert your API key" required>
            
            <label for="edit-background-color">Background Color:</label>
            <input type="color" id="edit-background-color" name="background-color" 
                   value="<?php echo htmlspecialchars($personaData['background_color'] ?? '#FFFFFF'); ?>">
            
            <label for="edit-text-color">Text Color:</label>
            <input type="color" id="edit-text-color" name="text-color" 
                   value="<?php echo htmlspecialchars($personaData['text_color'] ?? '#000000'); ?>">
            
            <label for="edit-style-color">Styled Elements Color:</label>
            <input type="color" id="edit-style-color" name="style-color" 
                   value="<?php echo htmlspecialchars($personaData['style_color'] ?? '#007bff'); ?>">
            
            <button id="edit-confirm-button">Confirm Changes</button>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const editConfirmButton = document.getElementById('edit-confirm-button');
        
        editConfirmButton.addEventListener('click', function() {
            // Raccogli i valori dai campi di input
            const formData = new FormData();
            formData.append('persona_name', document.getElementById('edit-chatbot-name').value);
            formData.append('persona_heading', document.getElementById('edit-chatbot-heading').value);
            formData.append('persona_apikey', document.getElementById('edit-chatbot-apikey').value);
            formData.append('background_color', document.getElementById('edit-background-color').value);
            formData.append('text_color', document.getElementById('edit-text-color').value);
            formData.append('style_color', document.getElementById('edit-style-color').value);
            
            // Invia la richiesta al server
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Persona successfully modified!');
                    window.close(); // Chiudi la finestra
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Errore:', error);
                alert('An error occurred while updating the Persona settings.');
            });
        });
    });
    </script>
</body>
</html>