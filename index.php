<?php
require_once 'db_connect.php';

// Hole die aktuelle Frage
$query = $pdo->query("SELECT * FROM questions ORDER BY position ASC LIMIT 1");
$question = $query->fetch();

// Wenn keine Frage existiert, zeige Konfigurationsseite
if (!$question) {
    header("Location: config.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wer wird Millionär</title>
    <link rel="stylesheet" href="style.css">
    <!-- TMI.js direkt mit einem Script-Tag einbinden -->
    <script src="tmi.min.js"></script>
</head>
<body>
    <div class="game-container">
        <div class="logo">Wer wird Millionär?</div>
        
        <div class="question-container">
            <div class="question" id="question"><?php echo htmlspecialchars($question['question']); ?></div>
        </div>
        
        <div class="answers-container">
            <div class="answer" id="answer-a" data-answer="A">
                <div class="answer-letter">A</div>
                <div class="answer-text"><?php echo htmlspecialchars($question['answer_a']); ?></div>
                <div class="vote-count" id="vote-a">0</div>
            </div>
            <div class="answer" id="answer-b" data-answer="B">
                <div class="answer-letter">B</div>
                <div class="answer-text"><?php echo htmlspecialchars($question['answer_b']); ?></div>
                <div class="vote-count" id="vote-b">0</div>
            </div>
            <div class="answer" id="answer-c" data-answer="C">
                <div class="answer-letter">C</div>
                <div class="answer-text"><?php echo htmlspecialchars($question['answer_c']); ?></div>
                <div class="vote-count" id="vote-c">0</div>
            </div>
            <div class="answer" id="answer-d" data-answer="D">
                <div class="answer-letter">D</div>
                <div class="answer-text"><?php echo htmlspecialchars($question['answer_d']); ?></div>
                <div class="vote-count" id="vote-d">0</div>
            </div>
        </div>
        
        <div class="controls">
            <button id="next-question">Nächste Frage</button>
            <button id="twitch-connect">Twitch verbinden</button>
            <input type="text" id="twitch-channel" placeholder="Twitch Kanal">
        </div>
    </div>

    <div class="message" id="message"></div>

    <script src="script.js"></script>
</body>
</html>