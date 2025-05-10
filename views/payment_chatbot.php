<?php
// Set page title
$page_title = "Payment Assistant";

// Generate content
$content = <<<HTML
<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">
                    <i class="fas fa-robot me-2"></i>
                    Payment Assistant
                </h5>
            </div>
            <div class="card-body">
                <!-- Chat Messages -->
                <div id="chatMessages" class="mb-3" style="height: 400px; overflow-y: auto;">
                    <div class="message system">
                        <div class="message-content">
                            <i class="fas fa-robot text-primary me-2"></i>
                            Hello! I'm your payment assistant. How can I help you today?
                        </div>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="quick-actions mb-3">
                    <button class="btn btn-outline-primary btn-sm me-2" onclick="askQuestion('What payments are due?')">
                        <i class="fas fa-calendar me-1"></i>
                        Due Payments
                    </button>
                    <button class="btn btn-outline-primary btn-sm me-2" onclick="askQuestion('How can I make a payment?')">
                        <i class="fas fa-money-bill me-1"></i>
                        Make Payment
                    </button>
                    <button class="btn btn-outline-primary btn-sm me-2" onclick="askQuestion('Show my payment history')">
                        <i class="fas fa-history me-1"></i>
                        Payment History
                    </button>
                    <button class="btn btn-outline-primary btn-sm" onclick="askQuestion('What are my payment options?')">
                        <i class="fas fa-credit-card me-1"></i>
                        Payment Options
                    </button>
                </div>
                
                <!-- Chat Input -->
                <div class="input-group">
                    <input type="text" id="userInput" class="form-control" placeholder="Type your question here..." 
                           onkeypress="if(event.key === 'Enter') askQuestion(this.value)">
                    <button class="btn btn-primary" onclick="askQuestion(document.getElementById('userInput').value)">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
                
                <!-- Voice Input -->
                <div class="text-center mt-2">
                    <button class="btn btn-link" onclick="startVoiceInput()">
                        <i class="fas fa-microphone"></i>
                        Click to speak
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.message {
    margin-bottom: 1rem;
    display: flex;
    flex-direction: column;
}

.message.user {
    align-items: flex-end;
}

.message-content {
    padding: 0.75rem 1rem;
    border-radius: 1rem;
    max-width: 80%;
}

.message.system .message-content {
    background-color: #f8f9fa;
    border: 1px solid #dee2e6;
}

.message.user .message-content {
    background-color: #007bff;
    color: white;
}

.quick-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.typing-indicator {
    display: inline-block;
    padding: 0.5rem 1rem;
    background-color: #f8f9fa;
    border-radius: 1rem;
}

.typing-indicator span {
    display: inline-block;
    width: 0.5rem;
    height: 0.5rem;
    background-color: #6c757d;
    border-radius: 50%;
    margin: 0 0.1rem;
    animation: typing 1s infinite;
}

.typing-indicator span:nth-child(2) {
    animation-delay: 0.2s;
}

.typing-indicator span:nth-child(3) {
    animation-delay: 0.4s;
}

@keyframes typing {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-0.5rem); }
}
</style>

<script>
let isRecording = false;
let mediaRecorder = null;
let audioChunks = [];

async function askQuestion(question) {
    if (!question.trim()) return;
    
    const userInput = document.getElementById('userInput');
    userInput.value = '';
    
    // Add user message
    addMessage(question, 'user');
    
    // Show typing indicator
    const typingIndicator = document.createElement('div');
    typingIndicator.className = 'message system';
    typingIndicator.innerHTML = `
        <div class="message-content typing-indicator">
            <span></span>
            <span></span>
            <span></span>
        </div>
    `;
    document.getElementById('chatMessages').appendChild(typingIndicator);
    
    try {
        // Get AI response
        const response = await fetch('api/chatbot_response.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ question })
        });
        
        const data = await response.json();
        
        // Remove typing indicator
        typingIndicator.remove();
        
        // Add AI response
        addMessage(data.response, 'system');
        
        // If there are actions, add them
        if (data.actions) {
            addActions(data.actions);
        }
        
    } catch (error) {
        console.error('Error:', error);
        typingIndicator.remove();
        addMessage('Sorry, I encountered an error. Please try again.', 'system');
    }
}

function addMessage(text, type) {
    const messageDiv = document.createElement('div');
    messageDiv.className = `message ${type}`;
    messageDiv.innerHTML = `
        <div class="message-content">
            ${type === 'system' ? '<i class="fas fa-robot text-primary me-2"></i>' : ''}
            ${text}
        </div>
    `;
    
    const chatMessages = document.getElementById('chatMessages');
    chatMessages.appendChild(messageDiv);
    chatMessages.scrollTop = chatMessages.scrollHeight;
}

function addActions(actions) {
    const actionsDiv = document.createElement('div');
    actionsDiv.className = 'message system';
    actionsDiv.innerHTML = `
        <div class="message-content">
            <div class="quick-actions">
                ${actions.map(action => `
                    <button class="btn btn-outline-primary btn-sm me-2" onclick="${action.onclick}">
                        <i class="${action.icon} me-1"></i>
                        ${action.text}
                    </button>
                `).join('')}
            </div>
        </div>
    `;
    
    const chatMessages = document.getElementById('chatMessages');
    chatMessages.appendChild(actionsDiv);
    chatMessages.scrollTop = chatMessages.scrollHeight;
}

async function startVoiceInput() {
    if (!isRecording) {
        try {
            const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
            mediaRecorder = new MediaRecorder(stream);
            
            mediaRecorder.ondataavailable = (event) => {
                audioChunks.push(event.data);
            };
            
            mediaRecorder.onstop = async () => {
                const audioBlob = new Blob(audioChunks, { type: 'audio/wav' });
                const formData = new FormData();
                formData.append('audio', audioBlob);
                
                try {
                    const response = await fetch('api/speech_to_text.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const data = await response.json();
                    if (data.text) {
                        askQuestion(data.text);
                    }
                } catch (error) {
                    console.error('Error:', error);
                    addMessage('Sorry, I couldn\'t understand your voice input. Please try typing instead.', 'system');
                }
                
                audioChunks = [];
            };
            
            mediaRecorder.start();
            isRecording = true;
            
            // Update button
            const button = document.querySelector('.btn-link');
            button.innerHTML = '<i class="fas fa-stop"></i> Click to stop';
            button.classList.add('text-danger');
            
        } catch (error) {
            console.error('Error:', error);
            addMessage('Sorry, I couldn\'t access your microphone. Please type your question instead.', 'system');
        }
    } else {
        mediaRecorder.stop();
        isRecording = false;
        
        // Update button
        const button = document.querySelector('.btn-link');
        button.innerHTML = '<i class="fas fa-microphone"></i> Click to speak';
        button.classList.remove('text-danger');
    }
}
</script>
HTML;
?> 