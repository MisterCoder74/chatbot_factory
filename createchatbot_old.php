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

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['chatbot_type'])) {
        $chatbot_type = $_POST['chatbot_type'];

if ($chatbot_type === 'Conversational Chatbot') {
switch ($plan_type) {
case 'Free':
$source_directory = './chatbot_templates/free/01_conversational/';
$files_to_copy = ['conf.json', 'conv_chatbot.jpg', 'default.png', 'info.txt'];                
break;
case 'Silver':
$source_directory = './chatbot_templates/silver/01_conversational/';
$files_to_copy = ['conf.json', 'conv_chatbot.jpg', 'default.png', 'info.txt'];                
break;
case 'Gold':
$source_directory = './chatbot_templates/gold/01_conversational/';
$files_to_copy = ['conf.json', 'conv_chatbot.jpg', 'default.png', 'info.txt', 'preloader.gif', 'upload.php', 'save_chat_resmem.php'];                
break;
case 'Platinum':
$source_directory = './chatbot_templates/platinum/01_conversational/';
$files_to_copy = ['conf.json', 'conv_chatbot.jpg', 'default.png', 'info.txt', 'preloader.gif', 'upload.php', 'vupload.php', 'save_chat_resmem.php'];                 
break;
default:
throw new Exception('Piano non valido per Conversational Chatbot');
}
} elseif ($chatbot_type === 'Content Creation Chatbot') {
switch ($plan_type) {
case 'Free':
$source_directory = './chatbot_templates/free/02_contentcreator/';
$files_to_copy = ['conf.json', 'content_chatbot.jpg', 'default.png', 'info.txt'];                
break;
case 'Silver':
$source_directory = './chatbot_templates/silver/02_contentcreator/';
$files_to_copy = ['conf.json', 'content_chatbot.jpg', 'default.png', 'info.txt'];                
break;
case 'Gold':
$source_directory = './chatbot_templates/gold/02_contentcreator/';
$files_to_copy = ['conf.json', 'content_chatbot.jpg', 'default.png', 'info.txt', 'preloader.gif', 'upload.php', 'save_chat_resmem.php'];                
break;
case 'Platinum':
$source_directory = './chatbot_templates/platinum/02_contentcreator/';
$files_to_copy = ['conf.json', 'content_chatbot.jpg', 'default.png', 'info.txt', 'preloader.gif', 'upload.php', 'save_chat_resmem.php'];                
break;
default:
throw new Exception('Piano non valido per Content Creation Chatbot');
}
} elseif ($chatbot_type === 'SEO Master Chatbot') {
switch ($plan_type) {
case 'Free':
$source_directory = './chatbot_templates/free/03_seomaster/';
$files_to_copy = ['conf.json', 'seo_chatbot.jpg', 'default.png', 'info.txt'];                
break;
case 'Silver':
$source_directory = './chatbot_templates/silver/03_seomaster/';
$files_to_copy = ['conf.json', 'seo_chatbot.jpg', 'default.png', 'info.txt'];                
break;
case 'Gold':
$source_directory = './chatbot_templates/gold/03_seomaster/';
$files_to_copy = ['conf.json', 'seo_chatbot.jpg', 'default.png', 'info.txt'];                
break;
case 'Platinum':
$source_directory = './chatbot_templates/platinum/03_seomaster/';
$files_to_copy = ['conf.json', 'seo_chatbot.jpg', 'default.png', 'info.txt'];                
break;
default:
throw new Exception('Piano non valido per SEO Master Chatbot');
}        
} else {
throw new Exception('Tipo di chatbot non valido');
}

        $unique_id = substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 6);
        $destination_directory = $user_id . '/chatbots/' . $unique_id . '/';

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

        if (!copy($source_directory . 'chatbot.html', $dest_file_html)) {
            $errors[] = "Errore nella copia del file: chatbot.html";
        }

        if (!empty($errors)) {
            throw new Exception(implode(', ', $errors));
        }

        $background_color = $_POST['background_color'] ?? '';
        $text_color = $_POST['text_color'] ?? '';
        $style_color = $_POST['style_color'] ?? '';
        $chatbot_name = $_POST['chatbot_name'] ?? '';
        $chatbot_heading = $_POST['chatbot_heading'] ?? '';
        $chatbot_apikey = $_POST['chatbot_apikey'] ?? '';    

        $conf_file_path = $destination_directory . 'conf.json';
            //echo "<script>alert(" . json_encode($conf_file_path) . ");</script>"; 
        $conf_data = json_decode(file_get_contents($conf_file_path), true);

        if (!empty($conf_data)) {
            $conf_data[0]['chatbot_id'] = $unique_id;
            $conf_data[0]['background_color'] = $background_color;
            $conf_data[0]['text_color'] = $text_color;
            $conf_data[0]['style_color'] = $style_color;
            $conf_data[0]['chatbot_name'] = $chatbot_name;
            $conf_data[0]['header'] = $chatbot_heading;
            $conf_data[0]['chatbot_apikey'] = $chatbot_apikey;    
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
            if (!isset($all_users_data[$current_user_index]["chatbots"])) {
                $all_users_data[$current_user_index]["chatbots"] = [];
            }
            $all_users_data[$current_user_index]["chatbots"][] = $unique_id;
        } else {
            throw new Exception('Utente non trovato');
        }

        if (file_put_contents('userdata.json', json_encode($all_users_data, JSON_PRETTY_PRINT)) === false) {
            throw new Exception('Errore nella scrittura di userdata.json');
        }

        echo json_encode(['success' => true, 'message' => 'Chatbot creato con successo!', 'chatbot_id' => $unique_id]);
    } else {
        throw new Exception('Invalid Request');
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
ob_end_flush();
?>