<?php
session_start();

// Controlla se l'ID utente è presente nella sessione
if (!isset($_SESSION["user_id"])) {
die("User not logged in");
}

// Carica i dati utente da userdata.json
$user_data_json = file_get_contents('userdata.json');
$readusers_data = json_decode($user_data_json, true);

// Trova l'utente specifico utilizzando l'ID dell'utente loggato
$user_found = false;
$user_data = null;

foreach ($readusers_data as &$user) {
if ($user['id'] == $_SESSION["user_id"]) {
$user_data = $user;
$user_found = true;

// Aggiorna la data dell'ultima visualizzazione
$user['lastseen_date'] = date("Y-m-d H:i:s");
break;
}
}

if (!$user_found) {
die("User data not found");
}

// Ora procediamo a contare i chatbot e le persona
$chatbot_count = is_array($user_data["chatbots"]) ? count($user_data["chatbots"]) : 0;
$persona_count = is_array($user_data["personas"]) ? count($user_data["personas"]) : 0;

$plans = json_decode(file_get_contents('plans.json'), true);

// Trova il piano corrispondente
$selectedPlan = null;
foreach ($plans as $plan) {
if ($plan['plan'] === $user_data["plan_type"]) {
$selectedPlan = $plan;
break;
}
}

// Verifica se l'utente ha raggiunto il limite di chatbot consentiti
$can_create_chatbot = $chatbot_count < $selectedPlan['allowed_chatbots'];
// Verifica se l'utente può avere Personas (Gold o Platinum)
$can_have_persona = ($selectedPlan['plan'] === 'Gold' || $selectedPlan['plan'] === 'Platinum');
$can_create_persona = $persona_count < $selectedPlan['personas'];

// Calcola totale crediti per il piano (non più moltiplicati per chatbot)
$available_credits = $selectedPlan['credits'];
$max_allowed_chatbots = $selectedPlan['allowed_chatbots'];

// Salva i dati aggiornati back to userdata.json con JSON_PRETTY_PRINT
file_put_contents('userdata.json', json_encode($readusers_data, JSON_PRETTY_PRINT));

// Qui puoi continuare a restituire altre informazioni o logica necessaria
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
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
    footer {
      background-color: #4CAF50;
      color: #fff;
      padding: 10px;
            margin-top: 4rem;
      text-align: center;
      font-size: 16px;
      font-weight: bold;
            left: 0px;
            bottom: 20px;
            width: 100%;
    }    
            
            details {
                    width: 70%;
            margin: 1rem auto;
                    padding: .5rem 1rem;
            }
            
            details table {
width: 90%;
            }
            details table th, details table td {
padding: 4px 6px;
            }   
            details summary h3 {
            color: black;
                    display: inline-block;
                    text-shadow: 0 2px 4px grey;
                    cursor: pointer;
            }

    /* Login and Registration Modals */
    .modal {
      display: none;
      position: fixed;
      z-index: 1;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      overflow: auto;
      background-color: rgba(0, 0, 0, 0.4);
      backdrop-filter: blur(3px);      
    }

    .modal-content {
      background-color: #fefefe;
      margin: 15% auto;
      padding: 20px;
      border: 1px solid #888;
      width: 30%;
    }

    .close {
      color: #aaa;
      float: right;
      font-size: 28px;
      font-weight: bold;
    }

    .close:hover,
    .close:focus {
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
        table {
            width: 90%;
                margin: .5rem auto;
                text-align: center;
            border-collapse: collapse;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
                font-size: .9rem;
        }
            
        .section {
            background: white;
            padding: 20px;
            margin: 10px 0;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        h2 {
            border-bottom: 2px solid #007bff;
            padding-bottom: 10px;
            color: #333;
        }
        .card {
            border: 1px solid #eaeaea;
            border-radius: 5px;
            padding: 15px;
            margin: 10px;
            text-align: center;
            width: 24rem;
            height: 24rem; 
                position: relative
        }
        .card button {
            position: absolute;
                width: 20rem;
            bottom: 10px; 
                left: 50%;
                transform: translateX(-50%);
            }  
            
            p.cost {
            margin: 10px auto;
                    font-size: .9rem;
                    color: red;
            }

        .image-placeholder {
            width: 100%;
            margin-bottom: 10px;
        }
        .image-placeholder img {
            width: 80%;
                height: auto;
                border-radius: 8px;
            }  
            
        .card-container {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
        }
        .features {
            text-align: center;
            margin-top: 10px;
        } 
             .card ul {
            margin-top: .5rem;
            
            }
            .card ul li {
            margin-left: 1rem;
                    text-align: left;
            }
            
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
            
// persona modal and styles
            
.persona-modal {
display: none;        
position: fixed;
z-index: 1;
left: 0;
top: 0;
width: 100%;
height: 100%;
overflow: auto;
background-color: rgba(0,0,0,0.4);
padding-top: 60px;
}

.persona-modal-content {
background-color: #fefefe;
margin: 5% auto;
padding: 20px;
border: 1px solid #888;
width: 40%;
} 
 
             
#persona-style {
    height: auto; /* o un'altezza specifica */
        
}            
  
#persona-style {
        width: 80%;
        padding: .5rem .25rem;
        margin: .25rem;
        border: 1px solid black;
            }            


   

.persona-modal-content input[type=text], .persona-modal-content textarea  {
      width: 90%;
      padding: 10px;
      margin: 10px 0;
      border: 1px solid #ccc;
      border-radius: 4px;
    }
.persona-modal-content input[type=color], .persona-modal-content input[type=date], .persona-modal-content input[type=file] {
            width: 44%;
            height: 32px;
      padding: 2px;
      margin: 10px 0;
      border: 1px solid #ccc;
      border-radius: 4px;
            }       

.persona-modal-content button {
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
            
.persona-close-button {
color: #aaa;
float: right;
font-size: 28px;
font-weight: bold;
}

.persona-close-button:hover,
.persona-close-button:focus {
color: black;
text-decoration: none;
cursor: pointer;
}             
            
    </style>
</head>
<body data-chatbot-count="<?php echo $chatbot_count; ?>" 
      data-allowed-chatbots="<?php echo $selectedPlan['allowed_chatbots']; ?>"
      data-persona-count="<?php echo $persona_count; ?>"
      data-allowed-personas="<?php echo $selectedPlan['personas']; ?>">
    <header>Vivacity Design Chatbot Manager Dashboard</header>
        
             <div class="section" id="account">
 <h2>User Account Details <span style="float: right;"><a href="logout.php">❌</a></span></h2>
  <div class="card-container">
   
    <table>
      <tr>
        <th>ID</th>
        <th>Email</th>
        <th>Plan Type</th>
        <th>Signup Date</th>
        <th>Last Seen</th>
        <th>Total Credits for This Plan</th>
        <th>Maximum Allowed Chatbots</th>      
        <th>Chatbots</th>
              <?php if ($can_have_persona): ?>
              <th>Personas</th>
              <?php endif; ?>
      </tr>
      <tr>
        <td><?php echo htmlspecialchars($user_data["id"]); ?></td>
        <td><?php echo htmlspecialchars($user_data["email"]); ?></td>
        <td><?php echo htmlspecialchars($user_data["plan_type"]); ?></td>
        <td><?php echo htmlspecialchars($user_data["signup_date"]); ?></td>
        <td><?php echo htmlspecialchars($user_data["lastseen_date"]); ?></td>
        <td><?php echo htmlspecialchars($available_credits); ?> credits</td> 
        <td><?php echo htmlspecialchars($max_allowed_chatbots); ?> chatbots</td> 
        <td><?php echo $chatbot_count; ?></td>
              <?php if ($can_have_persona): ?>
              <td><?php echo $persona_count; ?></td>
              <?php endif; ?>
      </tr>
    </table>

    <div class="card">
<h3><?php echo $selectedPlan['plan']; ?></h3>
<!-- <p class="cost">Cost: <?php echo $selectedPlan['price'] . ' euro/month'; ?></p> -->
<div class="features">
<?php if ($selectedPlan): ?>
<p><?php echo $selectedPlan['allowed_chatbots']; ?> chatbots</p>        
<p><?php echo $selectedPlan['credits']; ?> credits per chatbot</p>
<p>Context Memory: <?php echo $selectedPlan['context_memory']; ?></p>
<p>Disk Memory: <?php echo $selectedPlan['disk_memory']; ?></p>        
<p>Personalities: <?php echo $selectedPlan['personality']; ?></p>
<p>Chat Copy: <?php echo $selectedPlan['copy']; ?> </p>
<p>Chat Export: <?php echo $selectedPlan['export']; ?> </p>
<p>TXT Import: <?php echo $selectedPlan['text_import']; ?> </p>        
<p>Personas: <?php echo $selectedPlan['personas'] == 0 ? 'No Personas' : $selectedPlan['personas'] . ' Personas'; ?></p>
<p>Personas Module: <?php echo $selectedPlan['personas_module'] == "no" ? 'No Personas Module' : $selectedPlan['personas_module'] . ' Personas Module'; ?></p>
<?php else: ?>

<?php endif; ?>
</div>
</div>
          
<div class="card">
<h3>Your Chatbots:</h3>


<?php if ($chatbot_count > 0): ?>
<ul>
<?php
// Recupera l'ID dell'utente dalla sessione
$user_id = $_SESSION['user_id']; // Assicurati che l'ID utente sia salvato in questo modo

foreach ($user_data["chatbots"] as $chatbot_id):
// Definire il percorso della cartella del chatbot
$chatconf_path = $user_data["id"] . "/chatbots/" . $chatbot_id . "/conf.json";
$chatbot_path = $user_data["id"] . "/chatbots/" . $chatbot_id . "/" . $chatbot_id . ".html";


// Controllare se il file esiste
if (file_exists($chatconf_path)) {
// Leggere il file conf.json
$json_content = file_get_contents($chatconf_path);


$chatbot_config = json_decode($json_content, true);


// Estrarre le proprietà
$chatbot_name = isset($chatbot_config[0]['chatbot_name']) ? $chatbot_config[0]['chatbot_name'] : 'Unknown';
$chatbot_credits = isset($chatbot_config[0]['credits_count']) ? $chatbot_config[0]['credits_count'] : '0';
}


?>
<li>Chatbot ID: <?php echo $chatbot_id; ?></li>
<li>Chatbot Name: <?php echo $chatbot_name; ?> <a href="<?php echo $chatbot_path ?>" target="_blank"><small>Open</small></a> <a href="editchatbot.php?id=<?php echo $chatbot_id; ?>" target="_blank"><small>Edit</small></a></li>
<li>Chatbot Credits: <?php echo $chatbot_credits; ?></li>
<?php endforeach; ?>
</ul>
<?php else: ?>
<p>You haven't any chatbot yet.<br>
This plan allows <?php echo $selectedPlan['allowed_chatbots']; ?> chatbots creation.</p>
<?php endif; ?>
</div>

<?php if ($can_have_persona): ?>
<div class="card">
<h3>Your AI Persona</h3>

<?php if ($persona_count > 0): ?>
<ul>
<?php
// Recupera l'ID dell'utente dalla sessione
$user_id = $_SESSION['user_id'];

// Assicurati che $user_data['personas'] contenga dati
if (!empty($user_data["personas"])) {
foreach ($user_data["personas"] as $persona_id):
// Definire il percorso della cartella della persona
$personaconf_path = $user_data["id"] . "/personas/" . $persona_id . "/conf.json";
$persona_path = $user_data["id"] . "/personas/" . $persona_id . "/" . $persona_id . ".html";

// Controllare se il file esiste
if (file_exists($personaconf_path)) {
// Leggere il file conf.json
$json_content = file_get_contents($personaconf_path);
$persona_config = json_decode($json_content, true);

// Estrarre le proprietà
$persona_name = isset($persona_config[0]['persona_name']) ? $persona_config[0]['persona_name'] : 'Unknown';
$persona_credits = isset($persona_config[0]['credits_count']) ? $persona_config[0]['credits_count'] : '0';
?>
<li>Persona ID: <?php echo $persona_id; ?></li>
<li>Persona Name: <?php echo $persona_name; ?> <a href="<?php echo $persona_path ?>" target="_blank"><small>Open</small></a> <a href="editpersona.php?id=<?php echo $persona_id; ?>" target="_blank"><small>Edit</small></a>
</li>
<li>Persona Credits: <?php echo $persona_credits; ?></li>
<?php
}
endforeach;
}
?>
</ul>
<?php else: ?>
<p>You haven't any Persona yet.</p>
<?php endif; ?>

<?php if ($can_create_persona): ?>
<p>This plan allows <?php echo $selectedPlan['personas']; ?> 
<?php if ($selectedPlan['plan'] === 'Gold'): ?>        
        Basic Persona
<?php else: ?>  
        Advanced Persona
<?php endif; ?>        
        </p>

<?php if ($selectedPlan['plan'] === 'Gold'): ?>
<button class="button create-persona" data-module-type="Basic Persona">Create Basic Persona</button>
<?php elseif ($selectedPlan['plan'] === 'Platinum'): ?>
<button class="button create-persona" data-module-type="Advanced Persona">Create Advanced Persona</button>
<?php else: ?>
<p>This plan does not allow Persona creation.</p>
<?php endif; ?>
<?php else: ?>
<p>You have reached the maximum number of Personas allowed for your plan.</p>
<?php endif; ?>
</div>
<?php endif; ?>        
       
                        
            <div class="section" id="create">
<h2>Chatbot Creation</h2>
<div class="card-container">
<?php if ($can_create_chatbot): ?>
<div class="card">
<div class="image-placeholder"><img src="chatbot_face_01.jpg"></div>
<p class="cost">All-Plans Available</p>
<button class="button create-chatbot" data-chatbot-type="Conversational Chatbot">Create Conversational Chatbot</button>
</div>
<div class="card">
<div class="image-placeholder"><img src="chatbot_face_03.jpg"></div>
<p class="cost">All-Plans Available</p>
<button class="button create-chatbot" data-chatbot-type="Image Generation Chatbot">Create Image Generation Chatbot</button>
</div>
<div class="card">
<div class="image-placeholder"><img src="chatbot_face_04.jpg"></div>
<p class="cost">Gold / Platinum only</p>
<button class="button create-chatbot" data-chatbot-type="Website Composer Chatbot">Create Website Composer Chatbot</button>
</div>
<div class="card">
<div class="image-placeholder"><img src="chatbot_face_05.jpg"></div>
<p class="cost">Gold / Platinum only</p>
<button class="button create-chatbot" data-chatbot-type="Image Rebuilder Chatbot">Create Image Cartoonifier Chatbot</button>
</div>
<div class="card">
<div class="image-placeholder"><img src="chatbot_face_06.jpg"></div>
<p class="cost">Platinum only</p>
<button class="button create-chatbot" data-chatbot-type="Content Creation Chatbot">Create Content Creation Chatbot</button>
</div>
<div class="card">
<div class="image-placeholder"><img src="chatbot_face_02.jpg"></div>
<p class="cost">Platinum only</p>
<button class="button create-chatbot" data-chatbot-type="SEO Optimizer Chatbot">Create SEO Optimizer Chatbot</button>
</div>

<?php else: ?>
<p>You have reached the maximum number of chatbots allowed for your plan.</p>
<?php endif; ?>
</div>
</div>
<!-- DA FARE: settare con PHP per mostrare solo i disponibili rispetto all'attuale -->
<div class="section" id="plan">
            <h2>Plan Purchase / Upgrade / Downgrade</h2>
            <div class="card-container">
                <div class="card">
                    <h3>Silver Plan</h3>
                    <p class="cost">Cost: 9.90 euro/month</p>
                    <div class="features">
                        <p>2 chatbots</p>    
                        <p>5000 credits per chatbot</p>
                        <p>Context Memory Array</p>
                        <p>Multiple Personalities <small>(3)</small></p>
                        <p>Chat Export</p>    
                    </div>
                     <?php if ($selectedPlan['plan'] === 'Free'): ?>   
                        <a href="bgt_silver_accnt.html"><button class="button">Upgrade to Silver</button></a>
                     <?php elseif ($selectedPlan['plan'] === 'Silver'): ?>  
                        <button class="button" disabled>Current Plan</button>
                     <?php else: ?>
                        <a href="bgt_silver_accnt.html"><button class="button">Return to Silver</button></a>
                     <?php endif; ?>
                        <br>
                        <form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top">
  <input type="hidden" name="cmd" value="_s-xclick" />
  <input type="hidden" name="hosted_button_id" value="C46V5U7WR3XEC" />
  <input type="hidden" name="currency_code" value="EUR" />
  <input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_buynow_SM.gif" border="0" name="submit" title="PayPal, il metodo semplice e sicuro per pagare online" alt="Acquista ora" />
</form>
</div>
                <div class="card">
                    <h3>Gold Plan</h3>
                    <p class="cost">Cost: 19.90 euro/month</p>
                    <div class="features">
                        <p>4 chatbots</p>     
                        <p>7500 credits per chatbot</p>
                        <p>Context Memory Array</p>
                        <p>Multiple Personalities <small>(5)</small></p>
                        <p>Chat Export</p> 
                        <p>TXT File Import in Chatbots<br><small>(2 formats, up to 15000 chars)</small></p>     
                        <p>Resident Memory on Disk <small>(up to 20MB)</small></p>
                        <p>Low-Res Image Generation <small>(dall-e-2)</small></p>                            
                        <p>1 AI Persona (Basic Module)</p>
                    </div>
                    <?php if ($selectedPlan['plan'] === 'Free' || $selectedPlan['plan'] === 'Silver'): ?>   
                        <a href="bgt_gold_accnt.html"><button class="button">Upgrade to Gold</button></a>
                     <?php elseif ($selectedPlan['plan'] === 'Gold'): ?>  
                        <button class="button" disabled>Current Plan</button>
                     <?php else: ?>
                        <a href="bgt_gold_accnt.html"><button class="button">Return to Gold</button></a>
                     <?php endif; ?>
                </div>
                <div class="card">
                    <h3>Platinum Plan</h3>
                    <p class="cost">Cost: 24.90 euro/month</p>
                    <div class="features">
                        <p>6 chatbots</p>     
                        <p>10000 credits per chatbot</p>
                        <p>Context Memory Array</p>
                        <p>Multiple Personalities <small>(15)</small></p>
                        <p>Chat Export</p>
                        <p>TXT File Import in Chatbots<br><small>(2 formats, up to 30000 chars)</small></p>
                        <p>Image Vision in Chatbots</p>    
                        <p>Resident Memory on Disk <small>(up to 40MB)</small></p>
                        <p>Low-Res and Hi-Res Image Generation <small>(dall-e-2/3)</small></p>                            
                        <p>1 AI Persona (Advanced Module)</p>
                    </div>
                     <?php if ($selectedPlan['plan'] === 'Free' || $selectedPlan['plan'] === 'Silver' || $selectedPlan['plan'] === 'Gold'): ?>   
                        <a href="bgt_platinum_accnt.html"><button class="button">Upgrade to Platinum</button></a>
                     <?php else: ?> 
                        <button class="button" disabled>Current Plan</button>
                     <?php endif; ?>
                </div>
                    
            </div>
<small><b>Notice:</b> If you downgrade to a cheaper plan, your <b>existing chatbot</b> will keep their original messages capacity, but functionalities will be downgraded to the target plan.</small>       
</div>        <details>
                <summary> <h3>Open Comparative Table</h3></summary>
<table border="1">
    <thead>
        <tr>
            <th>Feature</th>
            <th>Free Plan</th>
            <th>Silver Plan</th>
            <th>Gold Plan</th>
            <th>Platinum Plan</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>Cost</td>
            <td>0.00 euro/month</td>
            <td>9.90 euro/month</td>
            <td>19.90 euro/month</td>
            <td>29.90 euro/month</td>
        </tr>
        <tr>
            <td>Number of chatbots</td>
            <td>1</td>
            <td>2</td>
            <td>4</td>
            <td>6</td>
        </tr>
        <tr>
            <td>Credits per chatbot / month</td>
            <td>1000</td>
            <td>5000</td>
            <td>7500</td>
            <td>10000</td>
        </tr>
        <tr>
            <td>Context Memory</td>
            <td>Yes</td>
            <td>Yes</td>
            <td>Yes</td>
            <td>Yes</td>
        </tr>
        <tr>
            <td>Resident Memory</td>
            <td>No</td>
            <td>No</td>
            <td>Yes</td>
            <td>Yes</td>
        </tr>            
        <tr>
            <td>Disk Quota</td>
            <td>N/A</td>
            <td>N/A</td>
            <td>20MB</td>
            <td>40MB</td>
        </tr>
        <tr>
            <td>Personalities</td>
            <td>1</td>
            <td>3</td>
            <td>5</td>
            <td>15</td>
        </tr>
        <tr>
            <td>Chat Copy</td>
            <td>Yes</td>
            <td>Yes</td>
            <td>Yes</td>
            <td>Yes</td>
        </tr>
        <tr>
            <td>Chat Export</td>
            <td>No</td>
            <td>Yes</td>
            <td>Yes</td>
            <td>Yes</td>
        </tr>
        <tr>
            <td>TXT File Import in Chatbots</td>
            <td>No</td>
            <td>No</td>
            <td>Two formats, up to 15000 characters</td>
            <td>Multiple formats, up to 30000 characters</td>
        </tr>
        <tr>
            <td>Image Vision in Chatbots</td>
            <td>No</td>
            <td>No</td>
            <td>No</td>
            <td>Yes</td>
        </tr>
        <tr>
            <td>Web Search in Chatbots</td>
            <td>No</td>
            <td>No</td>
            <td>No</td>
            <td>Yes</td>
        </tr>            
        <tr>
            <td>Image Generation Model</td>
            <td>Dall-E-2</td>
            <td>Dall-E-2</td>
            <td>Dall-E-2/3</td>
            <td>Dall-E-2/3</td>
        </tr>            
        <tr>
            <td>AI Personas</td>
            <td>No</td>
            <td>No</td>
            <td>1 (Basic Module)</td>
            <td>1 (Advanced Module)</td>
        </tr>
        <tr>
            <td>Personas Module</td>
            <td>No</td>
            <td>No</td>
            <td>Basic Module (2000 chars)</td>
            <td>Advanced Module (3500 chars)</td>
        </tr>
        <tr>
            <td>Personas Capacity</td>
            <td>N/A</td>
            <td>N/A</td>
            <td>5000 credits / 20 MB</td>
            <td>7500 credits / 40 MB</td>
        </tr>            
    </tbody>
</table>
     
                
</details>
</div> 
            <footer>Brought to you by Vivacity Design</footer>
        
        <!-- Chatbot creation modal -->
<div id="modal" class="modal" style="display: none;">
<div class="modal-content">
<span class="close-button">&times;</span>
<h2 id="modal-header">Chatbot Type</h2>
<p> Set chatbot styles <br></p> 
        <br>
<!-- Campo di testo per il nome del chatbot -->
<label for="chatbot-name">Chatbot Name:</label><br>
<input type="text" id="chatbot-name" name="chatbot-name" placeholder="Insert chatbot name"  required><br><br>

<!-- Campo di testo per l'intestazione del chatbot -->
<label for="chatbot-heading">Chatbot Heading:</label><br>
<input type="text" id="chatbot-heading" name="chatbot-heading" placeholder="Insert chatbot heading"  required><br><br>
        
<!-- Campo di testo per la API Key di OpenAI -->
<label for="chatbot_apikey">OpenAI API Key:</label><br>
<input type="text" id="chatbot_apikey" name="chatbot_apikey" placeholder="Insert your API key" required><br><br>        

<!-- Label e color picker per il background color -->
<label for="background-color">Background Color:</label><br>
<input type="color" id="background-color" name="background-color"><br><br>

<!-- Label e color picker per il text color -->
<label for="text-color">Text Color:</label><br>
<input type="color" id="text-color" name="text-color"><br><br>

<!-- Label e color picker per il style color -->
<label for="style-color">Style Color:</label><br>
<input type="color" id="style-color" name="style-color"><br><br>        
<p>Are you sure you want to create this chatbot?</p>
<button id="confirm-button">Confirm</button>
</div>
</div>
        
        
        <!-- persona creation modal -->
<div id="personamodal" class="modal" style="display: none;">
<div class="persona-modal-content">
<span class="persona-close-button">&times;</span>
<h2 id="persona-modal-header">Persona Type</h2>


<p> Set persona styles <br></p> 
  <br>
<label for="persona-name">Persona Name:</label><br>
<input type="text" id="persona-name" name="persona-name" placeholder="Insert persona name"  required><br><br>

<label for="persona-heading">Persona Heading:</label><br>
<input type="text" id="persona-heading" name="persona-heading" placeholder="Insert persona heading"  required><br><br>
        
<label for="persona_apikey">OpenAI API Key:</label><br>
<input type="text" id="persona_apikey" name="persona_apikey" placeholder="Insert your API key" required><br><br>        


  <label for="persona-background-color">Background Color:</label><br>
<input type="color" id="persona-background-color" name="persona-background-color"><br><br>

<label for="persona-text-color">Text Color:</label><br>
<input type="color" id="persona-text-color" name="persona-text-color"><br><br>

<label for="persona-style-color">Style Color:</label><br>
<input type="color" id="persona-style-color" name="persona-style-color"><br><br> 

 


    
<p>Are you sure you want to create this persona?</p>
<button id="persona-confirm-button">Confirm</button>
</div>
</div>
</div>         
 
        
<script>
//gestisce modale creazione chatbot        
document.addEventListener('DOMContentLoaded', function() {
const buttons = document.querySelectorAll('.create-chatbot'); // Seleziona solo i bottoni per la creazione dei chatbot
const modal = document.getElementById('modal');
const modalHeader = document.getElementById('modal-header');
const closeButton = document.querySelector('.close-button');
const createButton = document.getElementById('confirm-button'); // Assicurati di avere un pulsante di conferma nel tuo modale

buttons.forEach(button => {
button.addEventListener('click', function() {
const chatbotType = this.getAttribute('data-chatbot-type'); // Ottieni il tipo di chatbot dal dato
modalHeader.textContent = chatbotType; // Imposta l'intestazione del modale
modal.style.display = 'block'; // Mostra il modale
});
});

closeButton.addEventListener('click', function() {
modal.style.display = 'none'; // Nascondi il modale
});

window.addEventListener('click', function(event) {
if (event.target === modal) {
modal.style.display = 'none'; // Nascondi il modale se si clicca fuori di esso
}
});

// Funzione di creazione del chatbot
createButton.addEventListener('click', function() {
const chatbotType = modalHeader.textContent; // Riconosce il tipo di chatbot

// Quota validation
const currentChatbotCount = parseInt(document.body.getAttribute('data-chatbot-count'));
const allowedChatbots = parseInt(document.body.getAttribute('data-allowed-chatbots'));

if (currentChatbotCount >= allowedChatbots) {
    alert('Error: Chatbot limit reached for your plan');
    return;
}

// Recupera i valori dal modale
const backgroundColor = document.getElementById('background-color').value;
const textColor = document.getElementById('text-color').value;
const styleColor = document.getElementById('style-color').value;
const chatbotName = document.getElementById('chatbot-name').value;
const chatbotHeading = document.getElementById('chatbot-heading').value;
const openaiAPIkey = document.getElementById('chatbot_apikey').value;        

// Crea il corpo della richiesta
const body = new URLSearchParams({
'chatbot_type': chatbotType,
'background_color': backgroundColor,
'text_color': textColor,
'style_color': styleColor,
'chatbot_name': chatbotName,
'chatbot_heading': chatbotHeading,
'chatbot_apikey': openaiAPIkey        
}).toString();

fetch('createchatbot.php', { // Percorso al file PHP per creare il chatbot
method: 'POST',
headers: {
'Content-Type': 'application/x-www-form-urlencoded',
},
//credentials: 'include',
body: body
})
.then(response => {
console.log(response);
if (!response.ok) {
throw new Error('Network response was not ok'); // Gestione degli errori di rete
}
return response.json();
console.log(response); 
console.log(response).toString();        
})
.then(data => {
if (data.success) {
alert(data.message); // Mostra l'alert di successo
modal.style.display = 'none'; // Nascondi il modale dopo la creazione
window.location.reload();        
} else {
alert('Error: ' + data.message); // Mostra messaggio d'errore
}
})
       

});
});    
        
        
// gestisce modale e creazione persona
        
        //gestisce modale creazione persona        
document.addEventListener('DOMContentLoaded', function() {
const personabutton = document.querySelector('.create-persona'); // Seleziona solo i bottoni per la creazione dei chatbot
const modal = document.getElementById('personamodal');
const modalHeader = document.getElementById('persona-modal-header');
const closeButton = document.querySelector('.persona-close-button');
const personaCreateButton = document.getElementById('persona-confirm-button'); // Assicurati di avere un pulsante di conferma nel tuo modale


personabutton.addEventListener('click', function() {
const moduleType = this.getAttribute('data-module-type'); // Ottieni il tipo di chatbot dal dato
modalHeader.textContent = moduleType; // Imposta l'intestazione del modale
modal.style.display = 'block'; // Mostra il modale
});


closeButton.addEventListener('click', function() {
modal.style.display = 'none'; // Nascondi il modale
});

window.addEventListener('click', function(event) {
if (event.target === modal) {
modal.style.display = 'none'; // Nascondi il modale se si clicca fuori di esso
}
});
     
        
// Funzione di creazione della persona
personaCreateButton.addEventListener('click', function() {
const personaType = modalHeader.textContent; // Riconosce il tipo di chatbot

// Quota validation
const currentPersonaCount = parseInt(document.body.getAttribute('data-persona-count'));
const allowedPersonas = parseInt(document.body.getAttribute('data-allowed-personas'));

if (currentPersonaCount >= allowedPersonas) {
    alert('Error: Persona limit reached for your plan');
    return;
}

// Recupera i valori dal modale
const backgroundColor = document.getElementById('persona-background-color').value;
const textColor = document.getElementById('persona-text-color').value;
const styleColor = document.getElementById('persona-style-color').value;
const personaName = document.getElementById('persona-name').value;
const personaHeading = document.getElementById('persona-heading').value;
const openaiAPIkey = document.getElementById('persona_apikey').value;        

// Crea il corpo della richiesta
const body = new URLSearchParams({
'persona_type': personaType,
'background_color': backgroundColor,
'text_color': textColor,
'style_color': styleColor,
'persona_name': personaName,
'persona_heading': personaHeading,
'persona_apikey': openaiAPIkey        
}).toString();

fetch('createpersona.php', { // Percorso al file PHP per creare il chatbot
method: 'POST',
headers: {
'Content-Type': 'application/x-www-form-urlencoded',
},
//credentials: 'include',
body: body
})
.then(response => {
console.log(response);
if (!response.ok) {
throw new Error('Network response was not ok'); // Gestione degli errori di rete
}
return response.json();
console.log(response); 
console.log(response).toString();        
})
.then(data => {
if (data.success) {
alert(data.message); // Mostra l'alert di successo
modal.style.display = 'none'; // Nascondi il modale dopo la creazione
window.location.reload();        
} else {
alert('Error: ' + data.message); // Mostra messaggio d'errore
}
})
       

});
});    
               
        
</script>      
        
</body>
</html>