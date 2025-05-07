<?php
require_once 'db_connect.php';

// Aktuelle Fragenposition ermitteln
$positionQuery = $pdo->query("SELECT position FROM questions ORDER BY position ASC LIMIT 1");
$currentPosition = $positionQuery->fetchColumn();

if (!$currentPosition) {
    echo json_encode(['success' => false, 'message' => 'Keine Fragen gefunden']);
    exit;
}

// Frage abrufen
$query = $pdo->prepare("SELECT * FROM questions WHERE position = :position");
$query->execute(['position' => $currentPosition]);
$question = $query->fetch();

if ($question) {
    echo json_encode(['success' => true, 'question' => $question]);
} else {
    echo json_encode(['success' => false, 'message' => 'Frage nicht gefunden']);
}
?>