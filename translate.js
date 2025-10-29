// Simulated API keys (replace with actual keys in production)
const API_KEYS = [
    "AIzaSyA_l_kFyjQHFpvXn8TQ9eLpHQyKP0lvBJ4",
    "AIzaSyAuADWZN2ygnj4-9yEnVCNw2Y7tIawxJIw",
    "AIzaSyCp4QXnKidUxcZRxg9GVuI47k7SBKe0DSA",
    "AIzaSyCjWQdyvmq-TF1urF8-i_WhJPXJ-zpN8so",
    "AIzaSyCz6Tj9J82pRd8KPIFA6_aWNCjdZhe9zoU",
    "AIzaSyBaYs8OByKc4OIFCvz-UwwN8Nv0v5sdQF0",
    "AIzaSyC5qqQmsjTaVF3bDssg9SuXYFnt6wBTKvE",
    "AIzaSyCIH7T6iLXvw_GpJQQk-kLR2gxHHvsXJo8",
    "AIzaSyA5RseDXECOyN_iyjwHpCNzSb50m-vBSgo",
    "AIzaSyCuFt1znCO4usaecoPq-0I6ll6_IPm0XeE",
    "AIzaSyBW7vS4PqSYBuKBBAW8MeRzkDSeSQcruWc",
    "AIzaSyDkOFmGN0l5-rYzFFklYISPF8oa5GM4nic",
    "AIzaSyCAQqvcYBmNVNid9eVI9ovggMaN5wR7ugY",
    "AIzaSyAXjLAdTz_q9b7z2RlJ8Gyx-7NPQzNDKEg",
    "AIzaSyCeIN3NKWpOXI8cHmrFxueryriQhjhpJdI",
    "AIzaSyCqjg4-9Y-doHxHwGL9aNuZAocx6STUMNI",
    "AIzaSyCJsxjCu6vjyC2c1WviFBX5n2WWeGXn4nM",
    "AIzaSyAc8oFERCiAk2UaWkQmew1lIQRh_5yYVLI",
    "AIzaSyBH1r-l-aOKjMRbLFHr3XBVh9KjLb-kDjY",
    "AIzaSyDk49UB_el1s1ixJV4j9aS2kfZXl7s0h6w",
    "AIzaSyBYmtWX_LuP79T7OSj-OnjGXoRJgqnvrzk",
    "AIzaSyADE5FGBKr_YlObjQpGahtblufeebS5cgU",
    "AIzaSyAX5jTaHFHDrLkP7X6a42tKipK29zK7QqE",
    "AIzaSyCc4KDgGdatssWs97c9JFQrNDpTDDbelN4",
    "AIzaSyBcCKVD0ZebGikqedHJWXioN6Nq-xLFkwA",
    "AIzaSyDqtbnBtGNh97ylF_RPRxU9I7xXw2rTIDg",
    "AIzaSyABMvJI0-ovV6s2k9pTOPWrHZuzO9dtnDQ"

];


function getRandomApiKey() {
    const randomKey = Math.floor(Math.random() * API_KEYS.length);
    return API_KEYS[randomKey];
}

function cleanTranslationResult(text) {
    text = text.trim();
    const unwantedPhrases = [
        'Bản dịch là:',
        'Dịch sang tiếng việt:',
        'Dịch sang tiếng thái:',
        'Kết quả dịch:',
        'Đây là bản dịch:',
        'Câu trả lời:',
        'Dịch thuật:'
    ];

    for (const phrase of unwantedPhrases) {
        text = text.replace(new RegExp(phrase, 'i'), '');
    }

    text = text.replace(/\s+/g, ' ').trim();
    return text;
}

class ThaiVietnameseTranslator {
    constructor() {
        this.initElements();
        this.initEventListeners();
        this.initSpeechRecognition();
        this.currentDirection = 'thai-to-vietnamese';
        this.isRecording = false;
    }

    initElements() {
        this.inputText = document.getElementById('input-text');
        this.outputText = document.getElementById('translation-result');
        this.translateBtn = document.getElementById('translate-btn');
        this.clearBtn = document.getElementById('clear-btn');
        this.swapBtn = document.getElementById('swap-btn');
        this.copyBtn = document.getElementById('copy-btn');
        this.charCount = document.getElementById('char-count');
        this.thaiOption = document.getElementById('thai-option');
        this.vietnameseOption = document.getElementById('vietnamese-option');
        this.inputTitle = document.getElementById('input-title');
        this.outputTitle = document.getElementById('output-title');
        this.loading = document.getElementById('loading');
        this.toast = document.getElementById('toast');
        this.toastMessage = document.getElementById('toast-message');
        this.voiceBtn = document.getElementById('voice-btn');
        this.voiceStatus = document.getElementById('voice-status');
        this.voiceWarning = document.getElementById('voice-warning');
    }

    initSpeechRecognition() {
        if (!('webkitSpeechRecognition' in window) && !('SpeechRecognition' in window)) {
            this.voiceWarning.classList.add('show');
            this.voiceBtn.disabled = true;
            this.voiceBtn.style.opacity = '0.5';
            return;
        }

        const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
        this.recognition = new SpeechRecognition();
        this.recognition.continuous = false;
        this.recognition.interimResults = true;
        this.recognition.maxAlternatives = 1;
        this.lastProcessedResultIndex = 0;

        this.recognition.onresult = (event) => {
            let finalTranscript = '';
            let interimTranscript = '';

            for (let i = event.resultIndex; i < event.results.length; i++) {
                const transcript = event.results[i][0].transcript;
                if (event.results[i].isFinal) {
                    finalTranscript += transcript;
                } else {
                    interimTranscript += transcript;
                }
            }

            if (finalTranscript) {
                const cursorPos = this.inputText.selectionStart;
                const currentValue = this.inputText.value;
                const beforeCursor = currentValue.substring(0, cursorPos);
                const afterCursor = currentValue.substring(cursorPos);
                this.inputText.value = beforeCursor + finalTranscript + afterCursor;
                const newCursorPos = cursorPos + finalTranscript.length;
                this.inputText.setSelectionRange(newCursorPos, newCursorPos);
                this.updateCharCount();
                this.autoTranslate();
            }

            if (interimTranscript) {
                this.updateVoiceStatus(`Đang nghe: "${interimTranscript}"`, true);
            }
        };

        this.recognition.onerror = (event) => {
            console.error('Speech recognition error:', event.error);
            this.stopRecording();
            let errorMessage = 'Lỗi nhận dạng giọng nói';
            switch (event.error) {
                case 'no-speech':
                    errorMessage = 'Không nhận được giọng nói';
                    break;
                case 'audio-capture':
                    errorMessage = 'Không thể truy cập microphone';
                    break;
                case 'not-allowed':
                    errorMessage = 'Quyền truy cập microphone bị từ chối';
                    break;
                case 'network':
                    errorMessage = 'Lỗi kết nối mạng';
                    break;
            }
            this.showToast(errorMessage, 'error');
        };

        this.recognition.onend = () => {
            this.stopRecording();
        };

        this.recognition.onstart = () => {
            this.updateVoiceStatus('Đang nghe...', true);
            this.lastProcessedResultIndex = 0;
        };
    }

    initEventListeners() {
        this.inputText.addEventListener('input', () => {
            this.updateCharCount();
            this.autoTranslate();
        });

        this.translateBtn.addEventListener('click', () => {
            this.translateText();
        });

        this.clearBtn.addEventListener('click', () => {
            this.clearAll();
        });

        this.swapBtn.addEventListener('click', () => {
            this.swapLanguages();
        });

        this.copyBtn.addEventListener('click', () => {
            this.copyToClipboard();
        });

        this.thaiOption.addEventListener('click', () => {
            this.setDirection('thai-to-vietnamese');
        });

        this.vietnameseOption.addEventListener('click', () => {
            this.setDirection('vietnamese-to-thai');
        });

        this.voiceBtn.addEventListener('click', () => {
            this.toggleRecording();
        });

        this.inputText.addEventListener('keydown', (e) => {
            if (e.ctrlKey && e.key === 'Enter') {
                this.translateText();
            }
        });

        document.addEventListener('keydown', (e) => {
            if (e.code === 'Space' && e.ctrlKey && !this.isRecording) {
                e.preventDefault();
                this.startRecording();
            }
        });

        document.addEventListener('keyup', (e) => {
            if (e.code === 'Space' && e.ctrlKey && this.isRecording) {
                e.preventDefault();
                this.stopRecording();
            }
        });
    }

    toggleRecording() {
        if (this.isRecording) {
            this.stopRecording();
        } else {
            this.startRecording();
        }
    }

    startRecording() {
        if (!this.recognition) {
            this.showToast('Trình duyệt không hỗ trợ nhận dạng giọng nói', 'error');
            return;
        }

        try {
            this.recognition.lang = this.currentDirection === 'thai-to-vietnamese' ? 'th-TH' : 'vi-VN';
            this.lastProcessedResultIndex = 0;
            this.recognition.start();
            this.isRecording = true;
            this.voiceBtn.classList.add('recording');
            this.voiceBtn.innerHTML = '<i class="fas fa-stop"></i>';
        } catch (error) {
            console.error('Error starting recognition:', error);
            this.showToast('Không thể khởi động nhận dạng giọng nói', 'error');
        }
    }

    stopRecording() {
        if (this.recognition && this.isRecording) {
            this.recognition.stop();
        }
        this.isRecording = false;
        this.voiceBtn.classList.remove('recording', 'processing');
        this.voiceBtn.innerHTML = '<i class="fas fa-microphone"></i>';
        this.updateVoiceStatus('Nhấn để nói', false);
    }

    updateVoiceStatus(message, show) {
        this.voiceStatus.textContent = message;
        this.voiceStatus.classList[show ? 'add' : 'remove']('show');
    }

    updateCharCount() {
        const count = this.inputText.value.length;
        this.charCount.textContent = `${count}/5000`;
        this.charCount.style.color = count > 4500 ? '#dc3545' : count > 4000 ? '#fd7e14' : '#6c757d';
    }

    setDirection(direction) {
        this.currentDirection = direction;
        this.thaiOption.classList.toggle('active', direction === 'thai-to-vietnamese');
        this.vietnameseOption.classList.toggle('active', direction === 'vietnamese-to-thai');
        this.inputText.placeholder = direction === 'thai-to-vietnamese'
            ? 'พิมพ์ข้อความที่คุณต้องการแปลที่นี่... หรือ nhấn mic để nói'
            : 'Nhập văn bản cần dịch sang Tiếng Thái... hoặc nhấn mic để nói';
        this.inputTitle.textContent = direction === 'thai-to-vietnamese'
            ? 'Nhập văn bản Tiếng Thái'
            : 'Nhập văn bản Tiếng Việt';
        this.outputTitle.textContent = direction === 'thai-to-vietnamese'
            ? 'Bản dịch Tiếng Việt'
            : 'Bản dịch Tiếng Thái';
        this.clearAll();
    }

    swapLanguages() {
        const newDirection = this.currentDirection === 'thai-to-vietnamese'
            ? 'vietnamese-to-thai'
            : 'thai-to-vietnamese';
        const inputValue = this.inputText.value;
        const outputValue = this.outputText.textContent;
        this.setDirection(newDirection);
        if (outputValue && outputValue !== 'Bản dịch sẽ xuất hiện ở đây...') {
            this.inputText.value = outputValue;
            this.updateCharCount();
            this.autoTranslate();
        }
    }

    showLoading() {
        this.loading.style.display = 'block';
        this.outputText.style.opacity = '0.5';
        this.translateBtn.disabled = true;
    }

    hideLoading() {
        this.loading.style.display = 'none';
        this.outputText.style.opacity = '1';
        this.translateBtn.disabled = false;
    }

    async translateText() {
    const text = this.inputText.value.trim();
    if (!text) {
        this.outputText.textContent = 'Vui lòng nhập văn bản cần dịch...';
        return;
    }
    if (text.length > 5000) {
        this.outputText.textContent = 'Văn bản quá dài. Tối đa 5000 ký tự.';
        this.showToast('Văn bản quá dài. Tối đa 5000 ký tự.', 'error');
        return;
    }

    this.showLoading();
    
    // Lấy API key ngẫu nhiên và lưu lại để hiển thị khi có lỗi
    const selectedApiKey = getRandomApiKey();
    const apiKeyIndex = API_KEYS.indexOf(selectedApiKey);
    
    try {
        const prompt = this.currentDirection === 'thai-to-vietnamese'
            ? `Bạn là chuyên gia dịch thuật tiếng thái, bạn hãy dịch giúp tôi đoạn văn sau sang tiếng việt giúp tôi, tôi làm việc trong lĩnh vực đòi nợ vay tín dụng, yêu cầu giữ nguyên ngôi xưng hô và văn phong. Chỉ trả về bản dịch, không cần giải thích thêm. Đoạn văn là: ${text}`
            : `Bạn là chuyên gia dịch thuật tiếng thái, bạn hãy dịch giúp tôi đoạn văn tiếng việt sau sang tiếng thái giúp tôi, tôi làm việc trong lĩnh vực đòi nợ vay tín dụng, yêu cầu giữ nguyên ngôi xưng hô và văn phong. Chỉ trả về bản dịch tiếng thái, không cần giải thích thêm. Đoạn văn tiếng việt là: ${text}`;

        const response = await fetch(`https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=${selectedApiKey}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                contents: [{
                    parts: [{ text: prompt }]
                }]
            })
        });

        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}));
            const errorMessage = errorData.error?.message || response.statusText;
            throw new Error(`HTTP ${response.status}: ${errorMessage} | API Key #${apiKeyIndex + 1} (${selectedApiKey.substring(0, 20)}...)`);
        }

        const data = await response.json();
        if (data.candidates && data.candidates[0].content.parts[0].text) {
            const translation = cleanTranslationResult(data.candidates[0].content.parts[0].text);
            if (translation.includes('Lỗi')) {
                throw new Error(translation);
            }
            this.outputText.textContent = translation;
        } else {
            throw new Error(`Không lấy được nội dung dịch | API Key #${apiKeyIndex + 1}`);
        }
    } catch (error) {
        console.error('Translation error:', error);
        
        // Hiển thị thông báo lỗi chi tiết
        const errorMsg = error.message || 'Lỗi không xác định';
        this.outputText.textContent = `❌ Lỗi dịch thuật:\n${errorMsg}`;
        this.showToast(`Lỗi: ${errorMsg}`, 'error');
    } finally {
        this.hideLoading();
    }
}
    autoTranslate() {
        clearTimeout(this.autoTranslateTimeout);
        this.autoTranslateTimeout = setTimeout(() => {
            if (this.inputText.value.trim().length > 2) {
                this.translateText();
            }
        }, 1500);
    }

    clearAll() {
        this.inputText.value = '';
        this.outputText.textContent = 'Bản dịch sẽ xuất hiện ở đây...';
        this.updateCharCount();
        clearTimeout(this.autoTranslateTimeout);
        this.stopRecording();
    }

    async copyToClipboard() {
        const text = this.outputText.textContent;
        if (!text || text === 'Bản dịch sẽ xuất hiện ở đây...') {
            return;
        }

        try {
            await navigator.clipboard.writeText(text);
            this.showToast('Đã sao chép vào clipboard!');
        } catch (err) {
            const textArea = document.createElement('textarea');
            textArea.value = text;
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
            this.showToast('Đã sao chép vào clipboard!');
        }
    }

    showToast(message, type = 'success') {
        this.toastMessage.textContent = message;
        this.toast.className = 'toast show';
        if (type === 'error') {
            this.toast.classList.add('error');
        }
        setTimeout(() => {
            this.toast.classList.remove('show');
            setTimeout(() => {
                this.toast.classList.remove('error');
            }, 300);
        }, 3000);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    new ThaiVietnameseTranslator();
});