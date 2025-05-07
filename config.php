<?php
require_once 'db_connect.php';

$message = '';
$upload_dir = 'uploads/';

// Sicherstellen, dass das Upload-Verzeichnis existiert
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Wenn das Formular abgeschickt wurde
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'add_question') {
        $question = $_POST['question'];
        $answer_a = $_POST['answer_a'];
        $answer_b = $_POST['answer_b'];
        $answer_c = $_POST['answer_c'];
        $answer_d = $_POST['answer_d'];
        $correct_answer = $_POST['correct_answer'];
        $background_image = null;
        
        // Hintergrundbild hochladen, falls vorhanden
        if (isset($_FILES['background_image']) && $_FILES['background_image']['error'] === UPLOAD_ERR_OK) {
            $file_tmp = $_FILES['background_image']['tmp_name'];
            $file_name = basename($_FILES['background_image']['name']);
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            
            // Prüfen, ob es sich um ein gültiges Bild handelt
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
            if (in_array($file_ext, $allowed_extensions)) {
                $new_file_name = uniqid() . '.' . $file_ext;
                $upload_path = $upload_dir . $new_file_name;
                
                if (move_uploaded_file($file_tmp, $upload_path)) {
                    $background_image = $upload_path;
                } else {
                    $message = 'Fehler beim Hochladen des Bildes';
                }
            } else {
                $message = 'Nur JPG, JPEG, PNG und GIF-Dateien sind erlaubt';
            }
        }
        
        // Nächste Position ermitteln
        $positionQuery = $pdo->query("SELECT MAX(position) FROM questions");
        $nextPosition = ($positionQuery->fetchColumn() ?? 0) + 1;
        
        // Frage hinzufügen
        $query = $pdo->prepare("INSERT INTO questions (question, answer_a, answer_b, answer_c, answer_d, correct_answer, position, background_image) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        if ($query->execute([$question, $answer_a, $answer_b, $answer_c, $answer_d, $correct_answer, $nextPosition, $background_image])) {
            $message = 'Frage erfolgreich hinzugefügt';
        } else {
            $message = 'Fehler beim Hinzufügen der Frage';
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'delete_question') {
        $id = $_POST['id'];
        
        // Zuerst das Hintergrundbild ermitteln
        $imageQuery = $pdo->prepare("SELECT background_image FROM questions WHERE id = ?");
        $imageQuery->execute([$id]);
        $background_image = $imageQuery->fetchColumn();
        
        // Frage löschen
        $query = $pdo->prepare("DELETE FROM questions WHERE id = ?");
        if ($query->execute([$id])) {
            // Wenn ein Hintergrundbild existiert, dieses löschen
            if ($background_image && file_exists($background_image)) {
                unlink($background_image);
            }
            
            // Positionen neu ordnen
            $pdo->query("SET @pos := 0");
            $pdo->query("UPDATE questions SET position = (@pos := @pos + 1) ORDER BY position");
            $message = 'Frage erfolgreich gelöscht';
        } else {
            $message = 'Fehler beim Löschen der Frage';
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'reorder_questions') {
        // Position einer Frage ändern
        $id = $_POST['id'];
        $newPosition = $_POST['position'];
        
        // Aktuelle Position der Frage ermitteln
        $query = $pdo->prepare("SELECT position FROM questions WHERE id = ?");
        $query->execute([$id]);
        $currentPosition = $query->fetchColumn();
        
        // Maximale Position ermitteln
        $maxQuery = $pdo->query("SELECT COUNT(*) FROM questions");
        $maxPosition = $maxQuery->fetchColumn();
        
        if ($newPosition > 0 && $newPosition <= $maxPosition) {
            // Positionen aktualisieren
            if ($newPosition > $currentPosition) {
                $pdo->prepare("UPDATE questions SET position = position - 1 WHERE position BETWEEN ? AND ?")->execute([$currentPosition + 1, $newPosition]);
            } else {
                $pdo->prepare("UPDATE questions SET position = position + 1 WHERE position BETWEEN ? AND ?")->execute([$newPosition, $currentPosition - 1]);
            }
            
            // Neue Position setzen
            $pdo->prepare("UPDATE questions SET position = ? WHERE id = ?")->execute([$newPosition, $id]);
            $message = 'Reihenfolge aktualisiert';
        } else {
            $message = 'Ungültige Position';
        }
    }
}

// Alle Fragen laden
$questions = $pdo->query("SELECT * FROM questions ORDER BY position ASC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wer wird Millionär - Konfiguration</title>
    <link rel="stylesheet" href="config.css">
</head>
<body>
    <div class="config-container">
        <h1>Wer wird Millionär - Konfiguration</h1>
        
        <?php if ($message): ?>
            <div class="message"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <div class="tabs">
            <button class="tab active" data-tab="add">Frage hinzufügen</button>
            <button class="tab" data-tab="manage">Fragen verwalten</button>
        </div>
        
        <div class="tab-content active" id="add-tab">
            <h2>Neue Frage hinzufügen</h2>
            <form action="" method="post" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add_question">
                
                <div class="form-group">
                    <label for="question">Frage:</label>
                    <textarea id="question" name="question" required></textarea>
                </div>
                
                <div class="form-group">
                    <label for="answer_a">Antwort A:</label>
                    <input type="text" id="answer_a" name="answer_a" required>
                </div>
                
                <div class="form-group">
                    <label for="answer_b">Antwort B:</label>
                    <input type="text" id="answer_b" name="answer_b" required>
                </div>
                
                <div class="form-group">
                    <label for="answer_c">Antwort C:</label>
                    <input type="text" id="answer_c" name="answer_c" required>
                </div>
                
                <div class="form-group">
                    <label for="answer_d">Antwort D:</label>
                    <input type="text" id="answer_d" name="answer_d" required>
                </div>
                
                <div class="form-group">
                    <label for="correct_answer">Richtige Antwort:</label>
                    <select id="correct_answer" name="correct_answer" required>
                        <option value="A">A</option>
                        <option value="B">B</option>
                        <option value="C">C</option>
                        <option value="D">D</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="background_image">Hintergrundbild (optional):</label>
                    <input type="file" id="background_image" name="background_image" accept="image/*">
                    <p class="hint">Empfohlene Größe: 1200 x 600 Pixel</p>
                </div>
                
                <button type="submit">Frage hinzufügen</button>
            </form>
        </div>
        
        <div class="tab-content" id="manage-tab">
            <h2>Fragen verwalten</h2>
            <?php if (count($questions) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Pos.</th>
                            <th>Frage</th>
                            <th>Richtige Antwort</th>
                            <th>Hintergrundbild</th>
                            <th>Aktionen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($questions as $q): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($q['position']); ?></td>
                                <td><?php echo htmlspecialchars($q['question']); ?></td>
                                <td><?php echo htmlspecialchars($q['correct_answer']); ?></td>
                                <td>
                                    <?php if ($q['background_image']): ?>
                                        <img src="<?php echo htmlspecialchars($q['background_image']); ?>" alt="Hintergrundbild" class="thumbnail">
                                    <?php else: ?>
                                        <span>Kein Bild</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <form action="" method="post" class="inline-form">
                                            <input type="hidden" name="action" value="reorder_questions">
                                            <input type="hidden" name="id" value="<?php echo $q['id']; ?>">
                                            <input type="number" name="position" min="1" max="<?php echo count($questions); ?>" value="<?php echo $q['position']; ?>" class="position-input">
                                            <button type="submit" class="small-button">Ändern</button>
                                        </form>
                                        
                                        <form action="" method="post" class="inline-form delete-form">
                                            <input type="hidden" name="action" value="delete_question">
                                            <input type="hidden" name="id" value="<?php echo $q['id']; ?>">
                                            <button type="submit" class="small-button delete-button">Löschen</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>Keine Fragen vorhanden. Füge zuerst eine Frage hinzu.</p>
            <?php endif; ?>
            
            <div class="buttons">
                <a href="index.php" class="button">Zum Spiel</a>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Tab-Wechsel
            const tabs = document.querySelectorAll('.tab');
            const tabContents = document.querySelectorAll('.tab-content');
            
            tabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    const tabId = this.getAttribute('data-tab');
                    
                    // Aktive Tabs und Inhalte entfernen
                    tabs.forEach(t => t.classList.remove('active'));
                    tabContents.forEach(c => c.classList.remove('active'));
                    
                    // Aktuellen Tab und Inhalt aktivieren
                    this.classList.add('active');
                    document.getElementById(`${tabId}-tab`).classList.add('active');
                });
            });
            
            // Bestätigung beim Löschen
            const deleteForms = document.querySelectorAll('.delete-form');
            deleteForms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    if (!confirm('Möchtest du diese Frage wirklich löschen?')) {
                        e.preventDefault();
                    }
                });
            });
            
            // Bildvorschau für das Hochladen
            const imageInput = document.getElementById('background_image');
            if (imageInput) {
                imageInput.addEventListener('change', function() {
                    const preview = document.getElementById('image-preview');
                    
                    // Wenn ein Vorschaubild existiert, dieses entfernen
                    if (preview) {
                        preview.remove();
                    }
                    
                    // Wenn eine Datei ausgewählt wurde, Vorschau erstellen
                    if (this.files && this.files[0]) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            const previewDiv = document.createElement('div');
                            previewDiv.id = 'image-preview';
                            previewDiv.className = 'image-preview';
                            
                            const previewImg = document.createElement('img');
                            previewImg.src = e.target.result;
                            previewImg.alt = 'Vorschau';
                            
                            previewDiv.appendChild(previewImg);
                            imageInput.parentNode.appendChild(previewDiv);
                        }
                        reader.readAsDataURL(this.files[0]);
                    }
                });
            }
        });
    </script>
</body>
</html>