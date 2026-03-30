const navLinks = document.querySelectorAll('.nav-btn');
const sections = document.querySelectorAll('.content-section');
const pageTitle = document.getElementById('pageTitle');

navLinks.forEach(link => {
    link.addEventListener('click', (e) => {
        if(!link.getAttribute('data-target')) return;
        
        e.preventDefault();
        
       
        navLinks.forEach(l => l.classList.remove('active'));
        sections.forEach(s => s.classList.remove('active'));

        
        link.classList.add('active');
        const targetId = link.getAttribute('data-target');
        document.getElementById(targetId).classList.add('active');
        
        
        pageTitle.innerText = link.innerText.trim();
    });
});


const subBtns = document.querySelectorAll('.sub-nav-btn');
const subSections = document.querySelectorAll('.sub-section');

subBtns.forEach(btn => {
    btn.addEventListener('click', () => {
        subBtns.forEach(b => b.classList.remove('active'));
        subSections.forEach(s => s.classList.remove('active'));

        btn.classList.add('active');
        const subId = btn.getAttribute('data-sub');
        document.getElementById(subId).classList.add('active');
    });
});


const sendBtn = document.getElementById('sendBtn');
const aiInput = document.getElementById('aiInput');
const chatArea = document.getElementById('chatArea');

if (sendBtn) {
    sendBtn.addEventListener('click', () => {
        const text = aiInput.value;
        if (!text) return;
        
        addMessage(text, 'user');
        aiInput.value = '';

        setTimeout(() => {
            addMessage("Я изучаю твои данные... Попробуй повторить главу 4 по физике, там твои слабые места.", "ai");
        }, 1000);
    });
}

function addMessage(text, type) {
    const msg = document.createElement('div');
    msg.className = `msg ${type}`;
    msg.innerText = text;
    chatArea.appendChild(msg);
    chatArea.scrollTop = chatArea.scrollHeight;
}




function toggleKiosk() {
    const kiosk = document.getElementById('kioskOverlay');
    if (kiosk) {
        kiosk.style.display = (kiosk.style.display === 'flex') ? 'none' : 'flex';
    }
}