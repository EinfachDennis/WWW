<?php
require_once 'db_connect.php';

// Aktuelle Fragenposition ermitteln
$positionQuery = $pdo->query("SELECT position FROM questions ORDER BY position ASC LIMIT 1");
$currentPosition = $positionQuery->fetchColumn();

if (!$currentPosition) {
    echo json_encode(['success' => false, 'message' => 'Keine Fragen gefunden']);
    exit;
}

// Nächste Fragenposition ermitteln
$nextPosition = $currentPosition + 1;

// Prüfen, ob eine nächste Frage existiert
$query = $pdo->prepare("SELECT COUNT(*) FROM questions WHERE position = :position");
$query->execute(['position' => $nextPosition]);
$exists = $query->fetchColumn() > 0;

if ($exists) {
    // Aktuelle Frage löschen oder an das Ende verschieben
    $maxPositionQuery = $pdo->query("SELECT MAX(position) FROM questions");
    $maxPosition = $maxPositionQuery->fetchColumn();
    
    // Aktuelle Frage an das Ende verschieben
    $pdo->prepare("UPDATE questions SET position = :new_position WHERE position = :current_position")
        ->execute(['new_position' => $maxPosition + 1, 'current_position' => $currentPosition]);
    
    echo json_encode(['success' => true]);
} else {
    // Falls keine nächste Frage existiert, zur ersten Frage zurückkehren
    echo json_encode(['success' => true, 'message' => 'Zurück zur ersten Frage']);
}
?>