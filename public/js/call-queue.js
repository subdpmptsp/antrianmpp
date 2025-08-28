const soundQueue = [];
let isSpeaking = false;

async function processQueue() {
    if (isSpeaking || soundQueue.length === 0) return;

    isSpeaking = true;

    const message = soundQueue.shift(); // ambil pesan pertama
    await playQueueSound(message);

    isSpeaking = false;
    processQueue(); // proses berikutnya jika ada
}


async function playQueueSound(message) {
    const openingSound = new Audio('../sounds/opening.mp3');
    const closingSound = new Audio('../sounds/closing.mp3');

    // Tunggu opening selesai
    await new Promise((resolve) => {
        openingSound.addEventListener('ended', resolve);
        openingSound.play();
    });

    // Text-to-speech
    const voices = await getVoices();
    const idVoices = voices.filter(v => v.lang.includes("id"));
    const idWomanVoice = idVoices[idVoices.length - 1];

    await new Promise((resolve) => {
        const speech = new SpeechSynthesisUtterance(message);
        speech.voice = idWomanVoice;
        speech.rate = 0.7;

        speech.onend = () => {
            closingSound.play();
            closingSound.addEventListener('ended', resolve); // tunggu penutup selesai
        };

        window.speechSynthesis.speak(speech);
    });
}



function getVoices() {
    return new Promise((resolve, reject) => {
        id = setInterval(() => {
            const voices = window.speechSynthesis.getVoices()
            if (voices.length) {
                resolve(voices)
                clearInterval(id)
            }
        }, 10)
    })
}

document.addEventListener('livewire:initialized', () => {
    Livewire.on('queue-called', (message) => {
        soundQueue.push(message);
        processQueue();
    });
});
