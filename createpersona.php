<?php
ob_start();

header('Content-Type: application/json');
session_start();

try {
    if (!isset($_SESSION["user_id"])) {
        echo json_encode(['success' => false, 'message' => 'User not logged in']);
        exit;
    }
   
    //$user_data = $_SESSION["user_id"];
    //$user_id = $user_data["id"];
      $user_id = $_SESSION["user_id"]; 
     
    $all_users_data = json_decode(file_get_contents('userdata.json'), true);
        
    foreach ($all_users_data as $user) {
    if ($user["id"] === $user_id) {
    $current_user_data = $user;
    break;
    }
    }  
    // Accedi al valore di plan_type
    $plan_type = $current_user_data['plan_type'] ?? null;    

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['persona_type'])) {
        $persona_type = $_POST['persona_type'];

        $plans = json_decode(file_get_contents('plans.json'), true);
        $selectedPlan = null;
        foreach ($plans as $plan) {
            if ($plan['plan'] === $plan_type) {
                $selectedPlan = $plan;
                break;
            }
        }

        if ($selectedPlan) {
            $current_persona_count = is_array($current_user_data['personas']) ? count($current_user_data['personas']) : 0;
            if ($current_persona_count >= $selectedPlan['personas']) {
                throw new Exception('Persona limit reached for your plan');
            }
        }

        if ($persona_type === 'Basic Persona') {

$source_directory = './persona_templates/gold_basic/';
$files_to_copy = ['conf.json', 'default.png', 'persona_schema.jpg', 'info.txt', 'preloader.gif', 'upload.php', 'save_chat_resmem.php', 'persona_basic_view.html', 'persona_details.json', 'persona_setup_basic.html', 'save_basic_persona.php'];                

} elseif ($persona_type === 'Advanced Persona') {

$source_directory = './persona_templates/platinum_advanced/';
$files_to_copy = ['conf.json', 'default.png', 'persona_schema.jpg', 'info.txt', 'preloader.gif', 'upload.php', 'save_chat_resmem.php', 'persona_detailed_view.html', 'persona_details.json', 'persona_setup_advanced.html', 'save_advanced_persona.php'];                

} else {
throw new Exception('Tipo di persona non valido');
}

        $unique_id = substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 6);
        $destination_directory = $user_id . '/personas/' . $unique_id . '/';

        if (!file_exists($destination_directory)) {
            mkdir($destination_directory, 0777, true);
        }


$dest_file_html = $destination_directory . $unique_id . '.html';
//$files_to_copy = ['conf.json', 'default.png', 'info.txt'];
$errors = [];

foreach ($files_to_copy as $file) {
if (!copy($source_directory . $file, $destination_directory . '/' . $file)) {
$errors[] = "Errore nella copia del file: " . $file;
}
}

        if (!copy($source_directory . 'persona.html', $dest_file_html)) {
            $errors[] = "Errore nella copia del file: persona.html";
        }

        if (!empty($errors)) {
            throw new Exception(implode(', ', $errors));
        }
//ridefinire chiavi dei form
        $background_color = $_POST['background_color'] ?? '';
        $text_color = $_POST['text_color'] ?? '';
        $style_color = $_POST['style_color'] ?? '';
        $persona_name = $_POST['persona_name'] ?? '';
        $persona_heading = $_POST['persona_heading'] ?? '';
        $persona_apikey = $_POST['persona_apikey'] ?? '';    

        $conf_file_path = $destination_directory . 'conf.json';
            //echo "<script>alert(" . json_encode($conf_file_path) . ");</script>"; 
        $conf_data = json_decode(file_get_contents($conf_file_path), true);

        if (!empty($conf_data)) {
            $conf_data[0]['persona_id'] = $unique_id;
            $conf_data[0]['background_color'] = $background_color;
            $conf_data[0]['text_color'] = $text_color;
            $conf_data[0]['style_color'] = $style_color;
            $conf_data[0]['persona_name'] = $persona_name;
            $conf_data[0]['header'] = $persona_heading;
            $conf_data[0]['persona_apikey'] = $persona_apikey;    
        }

        if (file_put_contents($conf_file_path, json_encode($conf_data, JSON_PRETTY_PRINT)) === false) {
            throw new Exception('Errore nella scrittura di conf.json');
        }

        $current_user_index = null;
        foreach ($all_users_data as $index => $user) {
            if ($user["id"] === $user_id) {
                $current_user_index = $index;
                break;
            }
        }

        if ($current_user_index !== null) {
            if (!isset($all_users_data[$current_user_index]["personas"])) {
                $all_users_data[$current_user_index]["personas"] = [];
            }
            $all_users_data[$current_user_index]["personas"][] = $unique_id;
        } else {
            throw new Exception('Utente non trovato');
        }

        if (file_put_contents('userdata.json', json_encode($all_users_data, JSON_PRETTY_PRINT)) === false) {
            throw new Exception('Errore nella scrittura di userdata.json');
        }

        echo json_encode(['success' => true, 'message' => 'Persona creato con successo!', 'persona_id' => $unique_id]);
    } else {
        throw new Exception('Invalid Request');
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
ob_end_flush();
?>