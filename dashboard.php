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

<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        :root {
            --cb-bg: #ffffff;
            --cb-text: #212529;
            --cb-accent: #4CAF50;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background: #f8f9fa;
            color: var(--cb-text);
            min-height: 100vh;
        }

        .accent { color: var(--cb-accent); }
        .btn-accent { background: var(--cb-accent); border-color: var(--cb-accent); color: #fff; }
        .btn-accent:hover { filter: brightness(0.95); color: #fff; }

        .navbar { 
            z-index: 1030; 
            background: rgba(255,255,255,0.95); 
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .main-content {
            padding: 2rem 0;
        }

        .section-card {
            background: #fff;
            border-radius: 12px;
            border: 1px solid rgba(0,0,0,0.08);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
        }

        .section-title {
            font-weight: 700;
            font-size: 1.25rem;
            margin-bottom: 1.25rem;
            color: #212529;
            border-bottom: 2px solid var(--cb-accent);
            padding-bottom: 0.75rem;
        }

        .user-info-card {
            background: #fff;
            border-radius: 12px;
            border: 1px solid rgba(0,0,0,0.08);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
        }

        .feature-card {
            background: #fff;
            border-radius: 12px;
            border: 1px solid rgba(0,0,0,0.08);
            padding: 1.25rem;
            margin-bottom: 1.25rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
            height: 100%;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .feature-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .feature-card h5 {
            font-weight: 700;
            font-size: 1rem;
            margin-bottom: 1rem;
            color: #212529;
        }

        .feature-card .cost {
            color: #dc3545;
            font-weight: 600;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }

        .feature-card .features {
            font-size: 0.85rem;
            color: #6c757d;
        }

        .feature-card .features p {
            margin-bottom: 0.25rem;
        }

        .card-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.25rem;
        }

        .table-responsive {
            border-radius: 8px;
            overflow: hidden;
        }

        .custom-table {
            margin-bottom: 0;
        }

        .custom-table thead th {
            background: #f8f9fa;
            font-weight: 600;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #868e96;
            border-bottom: 2px solid #dee2e6;
            padding: 0.75rem 1rem;
        }

        .custom-table tbody td {
            padding: 0.75rem 1rem;
            vertical-align: middle;
            font-size: 0.9rem;
            border-bottom: 1px solid #dee2e6;
        }

        .custom-table tbody tr:last-child td {
            border-bottom: none;
        }

        .chatbot-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .chatbot-list li {
            padding: 0.75rem 0;
            border-bottom: 1px solid #dee2e6;
            font-size: 0.9rem;
        }

        .chatbot-list li:last-child {
            border-bottom: none;
        }

        .chatbot-list li a {
            color: var(--cb-accent);
            text-decoration: none;
            transition: opacity 0.2s;
        }

        .chatbot-list li a:hover {
            opacity: 0.7;
        }

        .btn-action {
            padding: 0.5rem 1rem;
            font-size: 0.85rem;
            border-radius: 6px;
            font-weight: 500;
        }

        .btn-primary-custom {
            background: var(--cb-accent);
            border-color: var(--cb-accent);
            color: #fff;
        }

        .btn-primary-custom:hover {
            background: #43a047;
            border-color: #43a047;
            color: #fff;
        }

        .btn-outline-custom {
            background: transparent;
            border: 1px solid var(--cb-accent);
            color: var(--cb-accent);
        }

        .btn-outline-custom:hover {
            background: var(--cb-accent);
            color: #fff;
        }

        .btn-disabled {
            background: #6c757d;
            border-color: #6c757d;
            color: #fff;
            cursor: not-allowed;
        }

        /* Modal Styles */
        .modal-overlay {
            display: none;
            position: fixed;
            z-index: 1050;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.4);
            backdrop-filter: blur(3px);
        }

        .modal-dialog-custom {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 0;
            border-radius: 12px;
            border: 1px solid #888;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        }

        .modal-header-custom {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid #dee2e6;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header-custom h4 {
            margin: 0;
            font-weight: 700;
            font-size: 1.1rem;
        }

        .modal-body-custom {
            padding: 1.5rem;
        }

        .modal-footer-custom {
            padding: 1rem 1.5rem;
            border-top: 1px solid #dee2e6;
            display: flex;
            justify-content: flex-end;
            gap: 0.75rem;
        }

        .close-modal {
            color: #aaa;
            font-size: 1.75rem;
            font-weight: bold;
            cursor: pointer;
            line-height: 1;
        }

        .close-modal:hover,
        .close-modal:focus {
            color: #000;
            text-decoration: none;
        }

        .form-label {
            font-weight: 500;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
            color: #495057;
        }

        .form-control-custom {
            width: 100%;
            padding: 0.625rem 0.875rem;
            font-size: 0.9rem;
            border: 1px solid #ced4da;
            border-radius: 6px;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .form-control-custom:focus {
            border-color: var(--cb-accent);
            box-shadow: 0 0 0 0.2rem rgba(76, 175, 80, 0.25);
            outline: none;
        }

        .color-input-group {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .color-input-group input[type="color"] {
            width: 48px;
            height: 38px;
            padding: 2px;
            border: 1px solid #ced4da;
            border-radius: 6px;
            cursor: pointer;
        }

        .confirm-btn {
            background: var(--cb-accent);
            border: none;
            color: #fff;
            padding: 0.625rem 1.25rem;
            font-size: 0.9rem;
            border-radius: 6px;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.2s;
        }

        .confirm-btn:hover {
            background: #43a047;
        }

        .cancel-btn {
            background: #fff;
            border: 1px solid #ced4da;
            color: #495057;
            padding: 0.625rem 1.25rem;
            font-size: 0.9rem;
            border-radius: 6px;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.2s;
        }

        .cancel-btn:hover {
            background: #f8f9fa;
        }

        /* Plan comparison table */
        .comparison-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.85rem;
        }

        .comparison-table th,
        .comparison-table td {
            padding: 0.75rem 1rem;
            text-align: center;
            border: 1px solid #dee2e6;
        }

        .comparison-table thead th {
            background: #f8f9fa;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.75rem;
        }

        .comparison-table tbody tr:nth-child(even) {
            background: #f8f9fa;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .main-content {
                padding: 1rem 0;
            }

            .section-card,
            .user-info-card {
                padding: 1rem;
            }

            .card-container {
                grid-template-columns: 1fr;
            }

            .modal-dialog-custom {
                margin: 10% auto;
                width: 95%;
            }
        }

        /* Footer */
        .dashboard-footer {
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
            border-top: 1px solid rgba(0,0,0,0.08);
            padding: 1.5rem 0;
            margin-top: 2rem;
        }

        .handbook-link {
            background-color: rgba(76, 175, 80, 0.1);
            color: var(--cb-accent);
            padding: 0.5rem 1rem;
            text-decoration: none;
            border-radius: 6px;
            font-size: 0.875rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: background 0.2s;
        }

        .handbook-link:hover {
            background-color: rgba(76, 175, 80, 0.2);
            color: var(--cb-accent);
        }

        .logout-link {
            color: #dc3545;
            text-decoration: none;
            font-size: 0.875rem;
            padding: 0.5rem;
            border-radius: 6px;
            transition: background 0.2s;
        }

        .logout-link:hover {
            background: rgba(220, 53, 69, 0.1);
        }

        .details-toggle {
            cursor: pointer;
            color: var(--cb-accent);
            font-weight: 500;
            margin-top: 1rem;
            display: inline-block;
        }

        .details-toggle:hover {
            text-decoration: underline;
        }

        .notice-text {
            font-size: 0.85rem;
            color: #6c757d;
            margin-top: 1rem;
            padding: 1rem;
            background: #fff3cd;
            border-radius: 8px;
        }
    </style>
</head>
<body data-chatbot-count="<?php echo $chatbot_count; ?>" 
      data-allowed-chatbots="<?php echo $selectedPlan['allowed_chatbots']; ?>"
      data-persona-count="<?php echo $persona_count; ?>"
      data-allowed-personas="<?php echo $selectedPlan['personas']; ?>">

    <nav class="navbar navbar-expand-lg border-bottom shadow-sm">
        <div class="container-fluid px-4">
            <div class="d-flex align-items-center gap-3">
                <span class="fw-bold fs-5">Vivacity Design Chatbot Manager</span>
                <a href="handbook.html" target="_blank" class="handbook-link">
                    📚 User Handbook
                </a>
            </div>
            <a href="logout.php" class="logout-link" title="Logout">
                ❌
            </a>
        </div>
    </nav>

    <main class="main-content">
        <div class="container-fluid px-4">
            <!-- User Account Details -->
            <div class="user-info-card">
                <h4 class="section-title">User Account Details</h4>
                <div class="table-responsive">
                    <table class="table custom-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Email</th>
                                <th>Plan Type</th>
                                <th>Signup Date</th>
                                <th>Last Seen</th>
                                <th>Total Credits</th>
                                <th>Max Chatbots</th>
                                <th>Chatbots</th>
                                <?php if ($can_have_persona): ?>
                                <th>Personas</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><?php echo htmlspecialchars($user_data["id"]); ?></td>
                                <td><?php echo htmlspecialchars($user_data["email"]); ?></td>
                                <td><span class="badge bg-success"><?php echo htmlspecialchars($user_data["plan_type"]); ?></span></td>
                                <td><?php echo htmlspecialchars($user_data["signup_date"]); ?></td>
                                <td><?php echo htmlspecialchars($user_data["lastseen_date"]); ?></td>
                                <td><?php echo htmlspecialchars($available_credits); ?> credits</td>
                                <td><?php echo htmlspecialchars($max_allowed_chatbots); ?></td>
                                <td><?php echo $chatbot_count; ?></td>
                                <?php if ($can_have_persona): ?>
                                <td><?php echo $persona_count; ?></td>
                                <?php endif; ?>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Current Plan -->
            <div class="section-card">
                <h4 class="section-title">Your Current Plan: <?php echo $selectedPlan['plan']; ?></h4>
                <div class="card-container">
                    <div class="feature-card">
                        <h5>Plan Features</h5>
                        <?php if ($selectedPlan): ?>
                        <div class="features">
                            <p><strong>Chatbots:</strong> <?php echo $selectedPlan['allowed_chatbots']; ?></p>
                            <p><strong>Credits per chatbot:</strong> <?php echo $selectedPlan['credits']; ?></p>
                            <p><strong>Context Memory:</strong> <?php echo $selectedPlan['context_memory']; ?></p>
                            <p><strong>Disk Memory:</strong> <?php echo $selectedPlan['disk_memory']; ?></p>
                            <p><strong>Personalities:</strong> <?php echo $selectedPlan['personality']; ?></p>
                            <p><strong>Chat Copy:</strong> <?php echo $selectedPlan['copy']; ?></p>
                            <p><strong>Chat Export:</strong> <?php echo $selectedPlan['export']; ?></p>
                            <p><strong>TXT Import:</strong> <?php echo $selectedPlan['text_import']; ?></p>
                            <p><strong>Personas:</strong> <?php echo $selectedPlan['personas'] == 0 ? 'No Personas' : $selectedPlan['personas'] . ' Personas'; ?></p>
                            <p><strong>Personas Module:</strong> <?php echo $selectedPlan['personas_module'] == "no" ? 'No Personas Module' : $selectedPlan['personas_module'] . ' Personas Module'; ?></p>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Your Chatbots -->
                    <div class="feature-card">
                        <h5>Your Chatbots</h5>
                        <?php if ($chatbot_count > 0): ?>
                        <ul class="chatbot-list">
                            <?php
                            $user_id = $_SESSION['user_id'];
                            foreach ($user_data["chatbots"] as $chatbot_id):
                                $chatconf_path = $user_data["id"] . "/chatbots/" . $chatbot_id . "/conf.json";
                                $chatbot_path = $user_data["id"] . "/chatbots/" . $chatbot_id . "/" . $chatbot_id . ".html";

                                if (file_exists($chatconf_path)) {
                                    $json_content = file_get_contents($chatconf_path);
                                    $chatbot_config = json_decode($json_content, true);

                                    $chatbot_name = isset($chatbot_config[0]['chatbot_name']) ? $chatbot_config[0]['chatbot_name'] : 'Unknown';
                                    $chatbot_credits = isset($chatbot_config[0]['credits_count']) ? $chatbot_config[0]['credits_count'] : '0';
                            ?>
                            <li>
                                <strong><?php echo htmlspecialchars($chatbot_name); ?></strong> (ID: <?php echo $chatbot_id; ?>)<br>
                                <small>Credits: <?php echo $chatbot_credits; ?></small><br>
                                <a href="<?php echo $chatbot_path ?>" target="_blank" class="me-2">Open</a>
                                <a href="editchatbot.php?id=<?php echo $chatbot_id; ?>" target="_blank">Edit</a>
                            </li>
                            <?php
                                }
                            endforeach;
                            ?>
                        </ul>
                        <?php else: ?>
                        <p class="text-muted small">You haven't created any chatbot yet.<br>
                        This plan allows <?php echo $selectedPlan['allowed_chatbots']; ?> chatbots.</p>
                        <?php endif; ?>
                    </div>

                    <!-- Your Personas -->
                    <?php if ($can_have_persona): ?>
                    <div class="feature-card">
                        <h5>Your AI Persona</h5>
                        <?php if ($persona_count > 0): ?>
                        <ul class="chatbot-list">
                            <?php
                            $user_id = $_SESSION['user_id'];
                            if (!empty($user_data["personas"])) {
                                foreach ($user_data["personas"] as $persona_id):
                                    $personaconf_path = $user_data["id"] . "/personas/" . $persona_id . "/conf.json";
                                    $persona_path = $user_data["id"] . "/personas/" . $persona_id . "/" . $persona_id . ".html";

                                    if (file_exists($personaconf_path)) {
                                        $json_content = file_get_contents($personaconf_path);
                                        $persona_config = json_decode($json_content, true);

                                        $persona_name = isset($persona_config[0]['persona_name']) ? $persona_config[0]['persona_name'] : 'Unknown';
                                        $persona_credits = isset($persona_config[0]['credits_count']) ? $persona_config[0]['credits_count'] : '0';
                            ?>
                            <li>
                                <strong><?php echo htmlspecialchars($persona_name); ?></strong> (ID: <?php echo $persona_id; ?>)<br>
                                <small>Credits: <?php echo $persona_credits; ?></small><br>
                                <a href="<?php echo $persona_path ?>" target="_blank" class="me-2">Open</a>
                                <a href="editpersona.php?id=<?php echo $persona_id; ?>" target="_blank">Edit</a>
                            </li>
                            <?php
                                    }
                                endforeach;
                            }
                            ?>
                        </ul>
                        <?php else: ?>
                        <p class="text-muted small">You haven't created any Persona yet.</p>
                        <?php endif; ?>

                        <?php if ($can_create_persona): ?>
                        <p class="small mt-2">This plan allows <?php echo $selectedPlan['personas']; ?> 
                        <?php if ($selectedPlan['plan'] === 'Gold'): ?>        
                            Basic Persona
                        <?php else: ?>  
                            Advanced Persona
                        <?php endif; ?></p>
                        <?php if ($selectedPlan['plan'] === 'Gold'): ?>
                        <button class="btn btn-accent btn-action w-100 create-persona" data-module-type="Basic Persona">Create Basic Persona</button>
                        <?php elseif ($selectedPlan['plan'] === 'Platinum'): ?>
                        <button class="btn btn-accent btn-action w-100 create-persona" data-module-type="Advanced Persona">Create Advanced Persona</button>
                        <?php else: ?>
                        <p class="small text-muted">This plan does not allow Persona creation.</p>
                        <?php endif; ?>
                        <?php else: ?>
                        <p class="small text-warning mt-2">You have reached the maximum number of Personas allowed for your plan.</p>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Chatbot Creation -->
            <div class="section-card">
                <h4 class="section-title">Chatbot Creation</h4>
                <div class="card-container">
                    <?php if ($can_create_chatbot): ?>
                    <div class="feature-card">
                        <div class="text-center mb-3">
                            <img src="chatbot_face_01.jpg" alt="Conversational Chatbot" class="img-fluid rounded" style="max-height: 120px;">
                        </div>
                        <h5 class="text-center">Conversational Chatbot</h5>
                        <p class="cost text-center">All Plans Available</p>
                        <button class="btn btn-accent btn-action w-100 create-chatbot" data-chatbot-type="Conversational Chatbot">Create Conversational Chatbot</button>
                    </div>
                    <div class="feature-card">
                        <div class="text-center mb-3">
                            <img src="chatbot_face_03.jpg" alt="Image Generation Chatbot" class="img-fluid rounded" style="max-height: 120px;">
                        </div>
                        <h5 class="text-center">Image Generation Chatbot</h5>
                        <p class="cost text-center">All Plans Available</p>
                        <button class="btn btn-accent btn-action w-100 create-chatbot" data-chatbot-type="Image Generation Chatbot">Create Image Generation Chatbot</button>
                    </div>
                    <div class="feature-card">
                        <div class="text-center mb-3">
                            <img src="chatbot_face_04.jpg" alt="Website Composer Chatbot" class="img-fluid rounded" style="max-height: 120px;">
                        </div>
                        <h5 class="text-center">Website Composer Chatbot</h5>
                        <p class="cost text-center">Gold / Platinum only</p>
                        <button class="btn btn-accent btn-action w-100 create-chatbot" data-chatbot-type="Website Composer Chatbot">Create Website Composer Chatbot</button>
                    </div>
                    <div class="feature-card">
                        <div class="text-center mb-3">
                            <img src="chatbot_face_05.jpg" alt="Image Cartoonifier Chatbot" class="img-fluid rounded" style="max-height: 120px;">
                        </div>
                        <h5 class="text-center">Image Cartoonifier Chatbot</h5>
                        <p class="cost text-center">Gold / Platinum only</p>
                        <button class="btn btn-accent btn-action w-100 create-chatbot" data-chatbot-type="Image Rebuilder Chatbot">Create Image Cartoonifier Chatbot</button>
                    </div>
                    <div class="feature-card">
                        <div class="text-center mb-3">
                            <img src="chatbot_face_06.jpg" alt="Content Creation Chatbot" class="img-fluid rounded" style="max-height: 120px;">
                        </div>
                        <h5 class="text-center">Content Creation Chatbot</h5>
                        <p class="cost text-center">Platinum only</p>
                        <button class="btn btn-accent btn-action w-100 create-chatbot" data-chatbot-type="Content Creation Chatbot">Create Content Creation Chatbot</button>
                    </div>
                    <div class="feature-card">
                        <div class="text-center mb-3">
                            <img src="chatbot_face_02.jpg" alt="SEO Optimizer Chatbot" class="img-fluid rounded" style="max-height: 120px;">
                        </div>
                        <h5 class="text-center">SEO Optimizer Chatbot</h5>
                        <p class="cost text-center">Platinum only</p>
                        <button class="btn btn-accent btn-action w-100 create-chatbot" data-chatbot-type="SEO Optimizer Chatbot">Create SEO Optimizer Chatbot</button>
                    </div>
                    <?php else: ?>
                    <div class="feature-card">
                        <h5>Chatbot Limit Reached</h5>
                        <p class="text-muted">You have reached the maximum number of chatbots allowed for your plan.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Plan Purchase / Upgrade / Downgrade -->
            <div class="section-card">
                <h4 class="section-title">Plan Purchase / Upgrade / Downgrade</h4>
                <div class="card-container">
                    <div class="feature-card">
                        <h5>Silver Plan</h5>
                        <p class="cost">Cost: €9.90/month</p>
                        <div class="features">
                            <p>2 chatbots</p>
                            <p>5000 credits per chatbot</p>
                            <p>Context Memory Array</p>
                            <p>Multiple Personalities (3)</p>
                            <p>Chat Export</p>
                        </div>
                        <?php if ($selectedPlan['plan'] === 'Free'): ?>   
                            <a href="bgt_silver_accnt.html"><button class="btn btn-accent btn-action w-100">Upgrade to Silver</button></a>
                        <?php elseif ($selectedPlan['plan'] === 'Silver'): ?>  
                            <button class="btn btn-disabled btn-action w-100" disabled>Current Plan</button>
                        <?php else: ?>
                            <a href="bgt_silver_accnt.html"><button class="btn btn-outline-custom btn-action w-100">Return to Silver</button></a>
                        <?php endif; ?>
                        <form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top" class="mt-2">
                            <input type="hidden" name="cmd" value="_s-xclick" />
                            <input type="hidden" name="hosted_button_id" value="C46V5U7WR3XEC" />
                            <input type="hidden" name="currency_code" value="EUR" />
                            <input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_buynow_SM.gif" border="0" name="submit" title="PayPal - The safer, easier way to pay online!" alt="Buy Now" style="width: 100%; max-width: 150px;" />
                        </form>
                    </div>
                    <div class="feature-card">
                        <h5>Gold Plan</h5>
                        <p class="cost">Cost: €19.90/month</p>
                        <div class="features">
                            <p>4 chatbots</p>
                            <p>7500 credits per chatbot</p>
                            <p>Context Memory Array</p>
                            <p>Multiple Personalities (5)</p>
                            <p>Chat Export</p>
                            <p>TXT File Import (10 formats, up to 15000 chars)</p>
                            <p>Resident Memory on Disk (up to 20MB)</p>
                            <p>Low-Res Image Generation (dall-e-2)</p>
                            <p>1 AI Persona (Basic Module)</p>
                        </div>
                        <?php if ($selectedPlan['plan'] === 'Free' || $selectedPlan['plan'] === 'Silver'): ?>   
                            <a href="bgt_gold_accnt.html"><button class="btn btn-accent btn-action w-100">Upgrade to Gold</button></a>
                        <?php elseif ($selectedPlan['plan'] === 'Gold'): ?>  
                            <button class="btn btn-disabled btn-action w-100" disabled>Current Plan</button>
                        <?php else: ?>
                            <a href="bgt_gold_accnt.html"><button class="btn btn-outline-custom btn-action w-100">Return to Gold</button></a>
                        <?php endif; ?>
                    </div>
                    <div class="feature-card">
                        <h5>Platinum Plan</h5>
                        <p class="cost">Cost: €24.90/month</p>
                        <div class="features">
                            <p>6 chatbots</p>
                            <p>10000 credits per chatbot</p>
                            <p>Context Memory Array</p>
                            <p>Multiple Personalities (15)</p>
                            <p>Chat Export</p>
                            <p>TXT File Import (10 formats, up to 30000 chars)</p>
                            <p>Image Vision in Chatbots</p>
                            <p>Resident Memory on Disk (up to 40MB)</p>
                            <p>Low-Res and Hi-Res Image Generation (dall-e-2/3)</p>
                            <p>1 AI Persona (Advanced Module)</p>
                        </div>
                        <?php if ($selectedPlan['plan'] === 'Free' || $selectedPlan['plan'] === 'Silver' || $selectedPlan['plan'] === 'Gold'): ?>   
                            <a href="bgt_platinum_accnt.html"><button class="btn btn-accent btn-action w-100">Upgrade to Platinum</button></a>
                        <?php else: ?> 
                            <button class="btn btn-disabled btn-action w-100" disabled>Current Plan</button>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="notice-text">
                    <strong>Notice:</strong> If you downgrade to a cheaper plan, your <strong>existing chatbot</strong> will keep their original messages capacity, but functionalities will be downgraded to the target plan.
                </div>
                <details class="mt-3">
                    <summary class="details-toggle">Open Comparative Table</summary>
                    <div class="table-responsive mt-3">
                        <table class="table comparison-table">
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
                                    <td>€0.00/month</td>
                                    <td>€9.90/month</td>
                                    <td>€19.90/month</td>
                                    <td>€29.90/month</td>
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
                                    <td>10 formats, up to 15000 characters</td>
                                    <td>10 formats, up to 30000 characters</td>
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
                    </div>
                </details>
            </div>
        </div>
    </main>

    <footer class="dashboard-footer">
        <div class="container-fluid text-center">
            <p class="mb-0 text-muted">Brought to you by Vivacity Design</p>
        </div>
    </footer>

    <!-- Chatbot creation modal -->
    <div id="modal" class="modal-overlay" style="display: none;">
        <div class="modal-dialog-custom">
            <div class="modal-header-custom">
                <h4 id="modal-header">Chatbot Type</h4>
                <span class="close-modal">&times;</span>
            </div>
            <div class="modal-body-custom">
                <p class="text-muted mb-3">Set chatbot styles</p>
                
                <label for="chatbot-name" class="form-label">Chatbot Name:</label>
                <input type="text" id="chatbot-name" name="chatbot-name" class="form-control-custom mb-3" placeholder="Insert chatbot name" required>

                <label for="chatbot-heading" class="form-label">Chatbot Heading:</label>
                <input type="text" id="chatbot-heading" name="chatbot-heading" class="form-control-custom mb-3" placeholder="Insert chatbot heading" required>
                
                <label for="chatbot_apikey" class="form-label">OpenAI API Key:</label>
                <input type="text" id="chatbot_apikey" name="chatbot_apikey" class="form-control-custom mb-3" placeholder="Insert your API key" required>
                
                <label for="background-color" class="form-label">Background Color:</label>
                <div class="color-input-group mb-3">
                    <input type="color" id="background-color" name="background-color" value="#ffffff">
                </div>
                
                <label for="text-color" class="form-label">Text Color:</label>
                <div class="color-input-group mb-3">
                    <input type="color" id="text-color" name="text-color" value="#000000">
                </div>
                
                <label for="style-color" class="form-label">Style Color:</label>
                <div class="color-input-group mb-3">
                    <input type="color" id="style-color" name="style-color" value="#4CAF50">
                </div>

                <p class="text-center mb-0">Are you sure you want to create this chatbot?</p>
            </div>
            <div class="modal-footer-custom">
                <button class="cancel-btn close-modal-btn">Cancel</button>
                <button id="confirm-button" class="confirm-btn">Confirm</button>
            </div>
        </div>
    </div>
        
    <!-- Persona creation modal -->
    <div id="personamodal" class="modal-overlay" style="display: none;">
        <div class="modal-dialog-custom">
            <div class="modal-header-custom">
                <h4 id="persona-modal-header">Persona Type</h4>
                <span class="close-modal">&times;</span>
            </div>
            <div class="modal-body-custom">
                <p class="text-muted mb-3">Set persona styles</p>
                
                <label for="persona-name" class="form-label">Persona Name:</label>
                <input type="text" id="persona-name" name="persona-name" class="form-control-custom mb-3" placeholder="Insert persona name" required>

                <label for="persona-heading" class="form-label">Persona Heading:</label>
                <input type="text" id="persona-heading" name="persona-heading" class="form-control-custom mb-3" placeholder="Insert persona heading" required>
                
                <label for="persona_apikey" class="form-label">OpenAI API Key:</label>
                <input type="text" id="persona_apikey" name="persona_apikey" class="form-control-custom mb-3" placeholder="Insert your API key" required>
                
                <label for="persona-background-color" class="form-label">Background Color:</label>
                <div class="color-input-group mb-3">
                    <input type="color" id="persona-background-color" name="persona-background-color" value="#ffffff">
                </div>
                
                <label for="persona-text-color" class="form-label">Text Color:</label>
                <div class="color-input-group mb-3">
                    <input type="color" id="persona-text-color" name="persona-text-color" value="#000000">
                </div>
                
                <label for="persona-style-color" class="form-label">Style Color:</label>
                <div class="color-input-group mb-3">
                    <input type="color" id="persona-style-color" name="persona-style-color" value="#4CAF50">
                </div>

                <p class="text-center mb-0">Are you sure you want to create this persona?</p>
            </div>
            <div class="modal-footer-custom">
                <button class="cancel-btn persona-close-modal-btn">Cancel</button>
                <button id="persona-confirm-button" class="confirm-btn">Confirm</button>
            </div>
        </div>
    </div>
        
<script>
// Chatbot modal handling
document.addEventListener('DOMContentLoaded', function() {
    const buttons = document.querySelectorAll('.create-chatbot');
    const modal = document.getElementById('modal');
    const modalHeader = document.getElementById('modal-header');
    const closeButtons = modal.querySelectorAll('.close-modal, .close-modal-btn');
    const createButton = document.getElementById('confirm-button');

    buttons.forEach(button => {
        button.addEventListener('click', function() {
            const chatbotType = this.getAttribute('data-chatbot-type');
            modalHeader.textContent = chatbotType;
            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
        });
    });

    closeButtons.forEach(button => {
        button.addEventListener('click', function() {
            modal.style.display = 'none';
            document.body.style.overflow = '';
        });
    });

    window.addEventListener('click', function(event) {
        if (event.target === modal) {
            modal.style.display = 'none';
            document.body.style.overflow = '';
        }
    });

    createButton.addEventListener('click', function() {
        const chatbotType = modalHeader.textContent;

        const currentChatbotCount = parseInt(document.body.getAttribute('data-chatbot-count'));
        const allowedChatbots = parseInt(document.body.getAttribute('data-allowed-chatbots'));

        if (currentChatbotCount >= allowedChatbots) {
            alert('Error: Chatbot limit reached for your plan');
            return;
        }

        const backgroundColor = document.getElementById('background-color').value;
        const textColor = document.getElementById('text-color').value;
        const styleColor = document.getElementById('style-color').value;
        const chatbotName = document.getElementById('chatbot-name').value;
        const chatbotHeading = document.getElementById('chatbot-heading').value;
        const openaiAPIkey = document.getElementById('chatbot_apikey').value;

        const body = new URLSearchParams({
            'chatbot_type': chatbotType,
            'background_color': backgroundColor,
            'text_color': textColor,
            'style_color': styleColor,
            'chatbot_name': chatbotName,
            'chatbot_heading': chatbotHeading,
            'chatbot_apikey': openaiAPIkey
        }).toString();

        fetch('createchatbot.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: body
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                alert(data.message);
                modal.style.display = 'none';
                document.body.style.overflow = '';
                window.location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while creating the chatbot.');
        });
    });
});

// Persona modal handling
document.addEventListener('DOMContentLoaded', function() {
    const personabutton = document.querySelector('.create-persona');
    const modal = document.getElementById('personamodal');
    const modalHeader = document.getElementById('persona-modal-header');
    const closeButtons = modal.querySelectorAll('.close-modal, .persona-close-modal-btn');
    const personaCreateButton = document.getElementById('persona-confirm-button');

    if (personabutton) {
        personabutton.addEventListener('click', function() {
            const moduleType = this.getAttribute('data-module-type');
            modalHeader.textContent = moduleType;
            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
        });
    }

    closeButtons.forEach(button => {
        button.addEventListener('click', function() {
            modal.style.display = 'none';
            document.body.style.overflow = '';
        });
    });

    window.addEventListener('click', function(event) {
        if (event.target === modal) {
            modal.style.display = 'none';
            document.body.style.overflow = '';
        }
    });

    personaCreateButton.addEventListener('click', function() {
        const moduleType = modalHeader.textContent;

        const currentPersonaCount = parseInt(document.body.getAttribute('data-persona-count'));
        const allowedPersonas = parseInt(document.body.getAttribute('data-allowed-personas'));

        if (currentPersonaCount >= allowedPersonas) {
            alert('Error: Persona limit reached for your plan');
            return;
        }

        const personaName = document.getElementById('persona-name').value;
        const personaHeading = document.getElementById('persona-heading').value;
        const openaiAPIkey = document.getElementById('persona_apikey').value;
        const backgroundColor = document.getElementById('persona-background-color').value;
        const textColor = document.getElementById('persona-text-color').value;
        const styleColor = document.getElementById('persona-style-color').value;

        const body = new URLSearchParams({
            'persona_type': moduleType,
            'persona_name': personaName,
            'persona_heading': personaHeading,
            'persona_apikey': openaiAPIkey,
            'persona_background_color': backgroundColor,
            'persona_text_color': textColor,
            'persona_style_color': styleColor
        }).toString();

        fetch('createpersona.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: body
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                alert(data.message);
                modal.style.display = 'none';
                document.body.style.overflow = '';
                window.location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while creating the persona.');
        });
    });
});
</script>
</body>
</html>
