import re

with open(r'd:\xammp1\htdocs\enoxx-election\employee\dashboard.php', 'r', encoding='utf-8') as f:
    text = f.read()

# 1. Top PHP Block
top_php_old = """  <?php
// Turn off error reporting to prevent HTML output
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';

// Start session for tracking
 
// Create unique slug function"""

top_php_new = """<?php
// Turn off error reporting to prevent HTML output
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';

requireLogin();
if (!isEmployee() && !isAdmin()) {
    header('Location: login.php');
    exit;
}

$employee = getEmployeeDetails($pdo, $_SESSION['user_id']);
if (!$employee) {
    header('Location: login.php');
    exit;
}
 
// Create unique slug function"""

text = text.replace(top_php_old, top_php_new)

# 2. Table Column Check
# $pdo->exec("ALTER TABLE candidates ADD COLUMN photo_hidden BOOLEAN DEFAULT TRUE");
col_old = """$pdo->exec("ALTER TABLE candidates ADD COLUMN photo_hidden BOOLEAN DEFAULT TRUE");
        }"""
col_new = """$pdo->exec("ALTER TABLE candidates ADD COLUMN photo_hidden BOOLEAN DEFAULT TRUE");
        }
        $colTx = $pdo->query("SHOW COLUMNS FROM candidates LIKE 'transaction_id'");
        if ($colTx->rowCount() == 0) {
            $pdo->exec("ALTER TABLE candidates ADD COLUMN transaction_id VARCHAR(100) NULL");
        }"""
text = text.replace(col_old, col_new)

# 3. Insert logic
# candidates (...) VALUES (...)
q_old = """photo_hidden
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'contesting', 'approved',
                0
            )"""

q_new = """photo_hidden, transaction_id
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'contesting', 'approved',
                0, ?
            )"""
text = text.replace(q_old, q_new)

q_param_old = """$data['video_message_url'] ?: null,
            $data['interview_video_url'] ?: null,
            $data['mobile_number'] ?: null
        ]);"""

q_param_new = """$data['video_message_url'] ?: null,
            $data['interview_video_url'] ?: null,
            $data['mobile_number'] ?: null,
            $data['transaction_id'] ?? null
        ]);"""
text = text.replace(q_param_old, q_param_new)

# 4. AJAX `save_candidate` data mapping
map_old = """'interview_video_url' => $_POST['interview_video_url'] ?? '',
                'mobile_number' => $_POST['mobile_number'] ?? ''
            ];"""
map_new = """'interview_video_url' => $_POST['interview_video_url'] ?? '',
                'mobile_number' => $_POST['mobile_number'] ?? '',
                'transaction_id' => trim($_POST['transaction_id'] ?? '')
            ];"""
text = text.replace(map_old, map_new)

# 5. ChatGPT API AJAX injection
ajax_catch = "} catch (Exception $e) {"
ajax_new = """} elseif ($_POST['ajax_action'] === 'translate_field') {
            $text = $_POST['value'] ?? '';
            if (empty($text)) {
                echo json_encode(['success' => false, 'message' => 'Text is empty']);
                exit;
            }
            if (!defined('OPENAI_API_KEY') || strpos(OPENAI_API_KEY, 'placeholder') !== false) {
                echo json_encode(['success' => true, 'translation' => $text . ' (AI Disabled)']);
                exit;
            }
            
            $url = 'https://api.openai.com/v1/chat/completions';
            $data = [
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    ['role' => 'system', 'content' => 'You are an English to Hindi terminology translator. Respond *only* with the direct translation of the provided text.'],
                    ['role' => 'user', 'content' => $text]
                ],
                'temperature' => 0.3
            ];
            
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Bearer ' . OPENAI_API_KEY
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            
            $response = curl_exec($ch);
            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpcode == 200) {
                $result = json_decode($response, true);
                $translation = $result['choices'][0]['message']['content'] ?? '';
                echo json_encode(['success' => true, 'translation' => trim($translation)]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Translation API request failed.']);
            }
            exit;
        } catch (Exception $e) {"""
text = text.replace(ajax_catch, ajax_new, 1)

# 6. Header display replacement
header_old = """<div class="user-avatar">AD</div>
                    <div class="user-info">
                        <div class="user-name">Admin User</div>
                        <div class="user-role">Super Admin</div>
                    </div>"""
header_new = """<?php 
                    $employeeName = htmlspecialchars($employee['full_name']);
                    $employeeRole = htmlspecialchars(ucfirst(str_replace('_', ' ', $employee['role'])));
                    $initials = strtoupper(substr($employeeName, 0, 2));
                    ?>
                    <div class="user-avatar"><?php echo $initials; ?></div>
                    <div class="user-info">
                        <div class="user-name"><?php echo $employeeName; ?></div>
                        <div class="user-role"><?php echo $employeeRole; ?></div>
                    </div>"""
text = text.replace(header_old, header_new)

# 7. Form inputs (Transaction ID, AutoTranslate events)
form_name_en_old = """<input type="text" name="candidate_name_en" id="nameEn" required placeholder="Name in English">"""
form_name_en_new = """<input type="text" name="candidate_name_en" id="nameEn" required placeholder="Name in English" onchange="translateField('nameEn', 'nameHi')">
<small id="nameEnPreview" style="display:none; color: #16a34a; margin-top: 5px;"></small>"""
text = text.replace(form_name_en_old, form_name_en_new)

rel_name_old = """<input type="text" name="relation_name" id="relationName" required placeholder="Enter father/husband name">"""
rel_name_new = """<input type="text" name="relation_name" id="relationName" required placeholder="Enter father/husband name" onchange="translateField('relationName', 'relation_name_hi')">
<small id="relationNamePreview" style="display:none; color: #16a34a; margin-top: 5px;"></small>"""
text = text.replace(rel_name_old, rel_name_new)

short_notes_old = """<textarea name="short_notes_hi" id="shortNotes" rows="4" required placeholder="स्थानीय विवरण लिखें... (e.g., स्थानीय किसान, 10 वर्षों से सामाजिक कार्य में सक्रिय)"></textarea>"""
short_notes_new = """<textarea name="short_notes_hi" id="shortNotes" rows="4" required placeholder="Enter notes in English... we will auto-translate to Hindi." onchange="translateField('shortNotes', 'shortNotes')"></textarea>
<small id="shortNotesPreview" style="display:none; color: #16a34a; margin-top: 5px;"></small>"""
text = text.replace(short_notes_old, short_notes_new)

biosec = """<!-- Bio Section -->"""
tx_html = """<!-- Verification -->
                        <div style="margin-bottom: 25px;">
                            <h3 style="color: var(--dark); display: flex; align-items: center; gap: 10px; margin-bottom: 20px;">
                                <i class="fas fa-check-double" style="color: var(--primary);"></i>
                                Verification
                            </h3>
                            <div class="form-group">
                                <label><i class="fas fa-receipt"></i> Transaction ID *</label>
                                <input type="text" name="transaction_id" id="transactionId" required placeholder="Enter Transaction ID / Reference Number">
                                <small style="color: #64748b; margin-top: 5px; display: block;">Adding a valid Transaction ID officially verifies this registration.</small>
                            </div>
                        </div>

                        <!-- Bio Section -->"""
text = text.replace(biosec, tx_html)

# 8. Javascript functions
js_translate = """// Translate a single field via ChatGPT API
function translateField(sourceId, targetId) {
    const source = document.getElementById(sourceId);
    const target = document.getElementById(targetId);
    const preview = document.getElementById(sourceId + 'Preview');
    
    if (!source || !source.value.trim()) return;
    
    if (preview) {
        preview.style.display = 'block';
        preview.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Translating...';
    }
    
    const formData = new URLSearchParams();
    formData.append('ajax_action', 'translate_field');
    formData.append('value', source.value);
    
    fetch('index.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: formData.toString()
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (target) {
                target.value = data.translation;
            } else if (sourceId === targetId) {
                source.value = data.translation;
            }
            if (preview) {
                preview.innerHTML = '<i class="fas fa-check"></i> Translated to Hindi';
                setTimeout(() => preview.style.display = 'none', 3000);
            }
        } else {
            if (preview) {
                preview.innerHTML = '<i class="fas fa-times" style="color: red;"></i> ' + data.message;
            }
        }
    })
    .catch(error => {
        if (preview) preview.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Error connecting to AI';
    });
}
"""

text = text.replace("</script>", js_translate + "\n</script>")

with open(r'd:\xammp1\htdocs\enoxx-election\employee\dashboard.php', 'w', encoding='utf-8') as f:
    f.write(text)

print("done")
