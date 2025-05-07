document.addEventListener('DOMContentLoaded', function() {
    // Variablen initialisieren
    let currentQuestionId = null;
    let correctAnswer = null;
    let client = null;
    let votes = {
        A: 0,
        B: 0,
        C: 0,
        D: 0
    };
    
    // DOM-Elemente auswählen
    const answerElements = document.querySelectorAll('.answer');
    const nextButton = document.getElementById('next-question');
    const twitchButton = document.getElementById('twitch-connect');
    const twitchChannelInput = document.getElementById('twitch-channel');
    const messageElement = document.getElementById('message');
    
    // Debug-Modus aktivieren
    const DEBUG = true;
    
    // Erste Frage laden
    fetchQuestion();
    
    // Event-Listener für die Antworten
    answerElements.forEach(answer => {
        answer.addEventListener('click', function() {
            const selectedAnswer = this.getAttribute('data-answer');
            checkAnswer(selectedAnswer);
        });
    });
    
    // Event-Listener für den "Nächste Frage"-Button
    nextButton.addEventListener('click', function() {
        // AJAX-Request für die nächste Frage
        fetch('next_question.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    fetchQuestion();
                } else {
                    showMessage(data.message || 'Fehler beim Laden der nächsten Frage');
                }
            })
            .catch(error => {
                console.error('Fehler beim Laden der nächsten Frage:', error);
                showMessage('Fehler beim Laden der nächsten Frage');
            });
    });
    
    // Event-Listener für den "Twitch verbinden"-Button
    twitchButton.addEventListener('click', function() {
        const channelName = twitchChannelInput.value.trim();
        if (channelName) {
            connectToTwitch(channelName);
        } else {
            showMessage('Bitte gib einen Twitch-Kanalnamen ein');
        }
    });
    
    // Funktion, um eine Frage vom Server zu laden
    function fetchQuestion() {
        fetch('get_question.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateQuestion(data.question);
                    resetAnswers();
                    resetVotes();
                } else {
                    showMessage('Keine Fragen gefunden');
                }
            })
            .catch(error => {
                console.error('Fehler beim Laden der Frage:', error);
                showMessage('Fehler beim Laden der Frage');
            });
    }
    
    // Funktion, um die Frage auf der Seite zu aktualisieren
    function updateQuestion(question) {
        document.getElementById('question').textContent = question.question;
        
        // Antworten aktualisieren
        document.querySelector('#answer-a .answer-text').textContent = question.answer_a;
        document.querySelector('#answer-b .answer-text').textContent = question.answer_b;
        document.querySelector('#answer-c .answer-text').textContent = question.answer_c;
        document.querySelector('#answer-d .answer-text').textContent = question.answer_d;
        
        // Hintergrundbild setzen, falls vorhanden
        const questionContainer = document.querySelector('.question-container');
        if (question.background_image) {
            questionContainer.style.backgroundImage = `url('${question.background_image}')`;
            // Hintergrund für bessere Lesbarkeit hinzufügen
            document.getElementById('question').style.backgroundColor = 'rgba(10, 17, 56, 0.7)';
            document.getElementById('question').style.padding = '10px';
            document.getElementById('question').style.borderRadius = '5px';
        } else {
            questionContainer.style.backgroundImage = 'none';
            document.getElementById('question').style.backgroundColor = 'transparent';
            document.getElementById('question').style.padding = '0';
        }
        
        currentQuestionId = question.id;
        correctAnswer = question.correct_answer;
    }
    
    // Funktion, um die Antwort zu prüfen
    function checkAnswer(selectedAnswer) {
        // Alle Antworten deaktivieren
        answerElements.forEach(answer => {
            answer.style.pointerEvents = 'none';
        });
        
        const correctElement = document.getElementById(`answer-${correctAnswer.toLowerCase()}`);
        
        if (selectedAnswer === correctAnswer) {
            // Richtige Antwort ausgewählt - wird grün markiert und bleibt so
            correctElement.classList.add('correct');
            showMessage('Richtige Antwort!');
            
            // Nach 2 Sekunden zur nächsten Frage
            setTimeout(() => {
                fetchQuestion();
            }, 2000);
        } else {
            // Falsche Antwort ausgewählt - wird temporär rot
            const selectedElement = document.getElementById(`answer-${selectedAnswer.toLowerCase()}`);
            selectedElement.classList.add('wrong');
            
            // Die richtige Antwort trotzdem grün markieren
            correctElement.classList.add('correct');
            
            showMessage('Falsche Antwort!');
            
            // Nach 2 Sekunden nur die falsche Antwort zurücksetzen
            setTimeout(() => {
                selectedElement.classList.remove('wrong');
                
                // Reaktiviere alle Antworten außer der korrekten (grünen)
                answerElements.forEach(answer => {
                    if (!answer.classList.contains('correct')) {
                        answer.style.pointerEvents = 'auto';
                    }
                });
            }, 2000);
        }
    }
    
    // Funktion, um die Antworten zurückzusetzen
    function resetAnswers() {
        answerElements.forEach(answer => {
            answer.classList.remove('selected', 'correct', 'wrong');
            answer.style.pointerEvents = 'auto';
        });
    }
    
    // Funktion, um die Votes zurückzusetzen
    function resetVotes() {
        votes = { A: 0, B: 0, C: 0, D: 0 };
        updateVoteDisplay();
    }
    
    // Funktion, um die Votes anzuzeigen
    function updateVoteDisplay() {
        document.getElementById('vote-a').textContent = votes.A;
        document.getElementById('vote-b').textContent = votes.B;
        document.getElementById('vote-c').textContent = votes.C;
        document.getElementById('vote-d').textContent = votes.D;
    }
    
    // Funktion, um eine Nachricht anzuzeigen
    function showMessage(text) {
        messageElement.textContent = text;
        messageElement.style.display = 'block';
        
        setTimeout(() => {
            messageElement.style.display = 'none';
        }, 3000);
    }
    
    // Funktion, um mit Twitch zu verbinden
    function connectToTwitch(channel) {
        if (DEBUG) console.log('Versuche, mit Twitch-Kanal zu verbinden:', channel);
        
        try {
            // Prüfen, ob tmi definiert ist
            if (typeof tmi === 'undefined') {
                showMessage('Twitch-API nicht geladen. Seite neu laden und erneut versuchen.');
                console.error('TMI.js ist nicht geladen.');
                return;
            }
            
            // Bestehende Verbindung trennen, falls vorhanden
            if (client) {
                if (DEBUG) console.log('Bestehende Verbindung getrennt');
                client.disconnect();
                client = null;
            }
            
            // Neue Verbindung herstellen
            if (DEBUG) console.log('Erstelle neuen Twitch-Client');
            client = new tmi.Client({
                options: { debug: true },
                connection: {
                    secure: true,
                    reconnect: true
                },
                channels: [channel]
            });
            
            if (DEBUG) console.log('Client erstellt, verbinde...');
            
            // Event-Listener registrieren bevor die Verbindung hergestellt wird
            client.on('connected', (addr, port) => {
                if (DEBUG) console.log('Mit Twitch verbunden!', addr, port);
                showMessage(`Mit Twitch-Kanal ${channel} verbunden`);
                twitchButton.textContent = 'Twitch verbunden';
                twitchButton.disabled = true;
            });
            
            client.on('disconnected', (reason) => {
                if (DEBUG) console.log('Verbindung getrennt:', reason);
                twitchButton.textContent = 'Twitch verbinden';
                twitchButton.disabled = false;
                showMessage('Twitch-Verbindung getrennt');
            });
            
            client.on('message', (channel, tags, message, self) => {
                if (DEBUG) console.log('Nachricht erhalten:', message);
                
                // Command !streamer überprüfen
                const voteMatch = message.match(/^!streamer\s+(.+)$/i);
                if (voteMatch) {
                    const voteText = voteMatch[1].trim();
                    if (DEBUG) console.log('Vote erkannt:', voteText);
                    processVote(voteText);
                }
            });
            
            // Verbindung herstellen
            client.connect()
                .catch(err => {
                    showMessage('Fehler beim Verbinden mit Twitch: ' + err.message);
                    console.error('Twitch-Verbindungsfehler:', err);
                    twitchButton.disabled = false;
                });
            
        } catch (error) {
            showMessage('Fehler bei der Twitch-Integration: ' + error.message);
            console.error('Fehler bei der Twitch-Integration:', error);
            twitchButton.disabled = false;
        }
    }
    
    // Funktion, um einen Vote zu verarbeiten
    function processVote(voteText) {
        if (DEBUG) console.log('Verarbeite Vote:', voteText);
        
        // Direkter Buchstabe (A, B, C, D oder a, b, c, d)
        if (voteText.length === 1) {
            const letterUpper = voteText.toUpperCase();
            if (['A', 'B', 'C', 'D'].includes(letterUpper)) {
                votes[letterUpper]++;
                updateVoteDisplay();
                if (DEBUG) console.log('Vote für', letterUpper, 'gezählt');
                return;
            }
        }
        
        // Textvergleich mit Antworten
        const voteTextLower = voteText.toLowerCase();
        const answers = {
            'A': document.querySelector('#answer-a .answer-text').textContent.toLowerCase(),
            'B': document.querySelector('#answer-b .answer-text').textContent.toLowerCase(),
            'C': document.querySelector('#answer-c .answer-text').textContent.toLowerCase(),
            'D': document.querySelector('#answer-d .answer-text').textContent.toLowerCase()
        };
        
        for (const [letter, text] of Object.entries(answers)) {
            if (voteTextLower === text) {
                votes[letter]++;
                updateVoteDisplay();
                if (DEBUG) console.log('Vote für', letter, 'gezählt');
                return;
            }
        }
        
        if (DEBUG) console.log('Vote nicht zugeordnet:', voteText);
    }
});