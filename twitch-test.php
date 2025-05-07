<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Twitch-Integration Tester</title>
    <script src="https://cdn.jsdelivr.net/npm/tmi.js@1.8.5/dist/tmi.min.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            padding: 0;
            background-color: #f0f0f0;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #6441a5;
        }
        .log {
            height: 300px;
            overflow-y: auto;
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
        }
        .log-entry {
            margin: 5px 0;
            padding: 5px;
            border-bottom: 1px solid #eee;
        }
        .error {
            background-color: #ffeded;
            color: #d00;
        }
        .info {
            background-color: #edfff5;
            color: #080;
        }
        button, input {
            padding: 10px;
            margin: 5px 0;
        }
        button {
            background-color: #6441a5;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        button:hover {
            background-color: #7d5bbe;
        }
        input {
            width: 300px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Twitch-Integration Tester</h1>
        
        <div>
            <h2>TMI.js Status</h2>
            <div id="tmi-status">Prüfe TMI.js...</div>
        </div>
        
        <div>
            <h2>Verbindungs-Test</h2>
            <input type="text" id="channel-name" placeholder="Twitch-Kanal (ohne @)">
            <button id="connect-btn">Verbinden</button>
            <button id="disconnect-btn" disabled>Trennen</button>
        </div>
        
        <div>
            <h2>Log</h2>
            <div class="log" id="log"></div>
        </div>
        
        <div>
            <h2>Debug-Info</h2>
            <div id="debug-info"></div>
        </div>
        
        <div>
            <button id="back-btn">Zurück zur Hauptseite</button>
        </div>
    </div>
    
    <script>
        // Funktion zum Loggen
        function log(message, type = 'normal') {
            const logElement = document.getElementById('log');
            const entry = document.createElement('div');
            entry.className = 'log-entry ' + type;
            entry.textContent = `[${new Date().toLocaleTimeString()}] ${message}`;
            logElement.appendChild(entry);
            logElement.scrollTop = logElement.scrollHeight;
        }
        
        // TMI.js überprüfen
        window.addEventListener('DOMContentLoaded', function() {
            const statusElement = document.getElementById('tmi-status');
            
            if (typeof tmi === 'undefined') {
                statusElement.textContent = 'TMI.js ist nicht geladen!';
                statusElement.style.color = 'red';
                log('TMI.js konnte nicht geladen werden.', 'error');
            } else {
                statusElement.textContent = 'TMI.js ist korrekt geladen.';
                statusElement.style.color = 'green';
                log('TMI.js erfolgreich geladen.', 'info');
                document.getElementById('debug-info').textContent = 'TMI.js Version: ' + (tmi.version || 'Unbekannt');
            }
        });
        
        // Globale Variable für den Twitch-Client
        let client = null;
        
        // Connect-Button
        document.getElementById('connect-btn').addEventListener('click', function() {
            const channelName = document.getElementById('channel-name').value.trim();
            
            if (!channelName) {
                log('Bitte einen Kanal-Namen eingeben.', 'error');
                return;
            }
            
            if (typeof tmi === 'undefined') {
                log('TMI.js ist nicht verfügbar. Verbindung nicht möglich.', 'error');
                return;
            }
            
            log(`Versuche, mit Kanal ${channelName} zu verbinden...`);
            
            // Bestehende Verbindung trennen
            if (client) {
                client.disconnect();
            }
            
            // Neue Verbindung
            client = new tmi.Client({
                connection: {
                    secure: true,
                    reconnect: true
                },
                channels: [channelName]
            });
            
            // Verbindung herstellen
            client.connect()
                .then(() => {
                    log(`Erfolgreich mit Kanal ${channelName} verbunden.`, 'info');
                    document.getElementById('connect-btn').disabled = true;
                    document.getElementById('disconnect-btn').disabled = false;
                    
                    // Debug-Info
                    document.getElementById('debug-info').textContent = 
                        'Verbunden mit: ' + channelName + '\n' +
                        'Client-Status: ' + (client.readyState() || 'Unbekannt');
                })
                .catch(err => {
                    log(`Fehler beim Verbinden: ${err.message}`, 'error');
                    console.error('Verbindungsfehler:', err);
                });
            
            // Event-Listener
            client.on('connecting', () => {
                log('Verbindung wird hergestellt...');
            });
            
            client.on('connected', () => {
                log('Verbindung hergestellt!', 'info');
            });
            
            client.on('disconnected', () => {
                log('Verbindung getrennt.');
                document.getElementById('connect-btn').disabled = false;
                document.getElementById('disconnect-btn').disabled = true;
            });
            
            client.on('message', (channel, tags, message, self) => {
                log(`Nachricht von ${tags.username}: ${message}`);
                
                // !streamer-Kommando prüfen
                const voteMatch = message.match(/^!streamer\s+(.+)$/i);
                if (voteMatch) {
                    const voteText = voteMatch[1].trim();
                    log(`Vote erkannt: "${voteText}"`, 'info');
                }
            });
        });
        
        // Disconnect-Button
        document.getElementById('disconnect-btn').addEventListener('click', function() {
            if (client) {
                log('Trenne Verbindung...');
                client.disconnect()
                    .then(() => {
                        log('Verbindung getrennt.', 'info');
                    })
                    .catch(err => {
                        log(`Fehler beim Trennen: ${err.message}`, 'error');
                    });
            }
        });
        
        // Zurück-Button
        document.getElementById('back-btn').addEventListener('click', function() {
            window.location.href = 'index.php';
        });
    </script>
</body>
</html>