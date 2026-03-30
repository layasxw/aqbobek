<?php
session_start();


?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aqbobek Ultra | Smart School Portal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <div class="bg-blobs">
        <div class="blob blob-1"></div>
        <div class="blob blob-2"></div>
    </div>

    <div class="app-container">
        <aside class="sidebar">
            <div class="logo-area">
                <div class="logo-icon"><i class="fas fa-bolt"></i></div>
                <span>AQBOBEK</span>
            </div>
            
            <nav class="side-nav">
                <a href="#" class="nav-btn active" data-target="section-home"><i class="fas fa-home"></i> Дашборд</a>
                <a href="#" class="nav-btn" data-target="section-grades"><i class="fas fa-chart-line"></i> Мои Оценки</a>
                <a href="#" class="nav-btn" data-target="section-ai"><i class="fas fa-robot"></i> AI Наставник</a>
                <a href="#" class="nav-btn" data-target="section-teacher"><i class="fas fa-user-shield"></i> Учительская</a>
                <div style="height: 1px; background: rgba(255,255,255,0.1); margin: 15px 0;"></div>
                <a href="#" class="kiosk-trigger" onclick="toggleKiosk()"><i class="fas fa-tv"></i> Стенгазета</a>
            </nav>

            <div class="user-block">
                <div class="avatar"></div>
                <div class="user-info">
                    <p class="name">Арман Даулетов</p>
                    <p class="role-text">10 "А" класс</p>
                </div>
            </div>
        </aside>

        <main class="main-content">
            <header class="top-bar">
                <h1 id="pageTitle">Дашборд</h1>
                <div class="date-now">29 марта, 2026</div>
            </header>

            <section id="section-home" class="content-section active">
                <div class="sub-navigation">
                    <button class="sub-nav-btn active" data-sub="sub-stats">📈 Статистика</button>
                    <button class="sub-nav-btn" data-sub="sub-events">📅 События</button>
                </div>
                <div id="sub-stats" class="sub-section active">
                    <div class="dashboard-grid">
                        <div class="glass-card">
                            <h3>Посещаемость</h3>
                            <div class="big-number">98%</div>
                        </div>
                        <div class="glass-card">
                            <h3>Задания</h3>
                            <div class="big-number">12/14</div>
                        </div>
                    </div>
                </div>
                <div id="sub-events" class="sub-section">
                    <div class="glass-card full-width">
                        <div class="event-item">
                            <div class="event-date">30 Мар</div>
                            <div class="event-info"><strong>СОР по Физике</strong><span>10:00</span></div>
                        </div>
                    </div>
                </div>
            </section>

            <section id="section-grades" class="content-section">
                <div class="glass-card full-width">
                    <div class="subject-row">
                        <span>Алгебра</span>
                        <div class="progress-line"><div class="fill mark-5-bg" style="width: 95%;"></div></div>
                        <span class="mark mark-5">5</span>
                    </div>
                    <div class="subject-row">
                        <span>Физика</span>
                        <div class="progress-line"><div class="fill mark-4-bg" style="width: 82%;"></div></div>
                        <span class="mark mark-4">4</span>
                    </div>
                    <div class="subject-row">
                        <span>География</span>
                        <div class="progress-line"><div class="fill mark-3-bg" style="width: 60%;"></div></div>
                        <span class="mark mark-3">3</span>
                    </div>
                </div>
            </section>

            <section id="section-ai" class="content-section">
                <div class="glass-card ai-chat-container">
                    <div class="chat-area" id="chatArea">
                        <div class="msg ai">Привет! Готов разобрать сложные темы?</div>
                    </div>
                    <div class="chat-input-wrapper">
                        <input type="text" id="aiInput" placeholder="Напиши ИИ...">
                        <button id="sendBtn"><i class="fas fa-paper-plane"></i></button>
                    </div>
                </div>
            </section>

            <section id="section-teacher" class="content-section">
                <div class="glass-card risk-container">
                    <h3>Зона риска ⚠️</h3>
                    <div class="risk-row pulse-red">
                        <div class="student-info"><strong>Берик А.</strong><span>10 "Б"</span></div>
                        <div class="risk-reason">Средний балл: 2.8</div>
                    </div>
                    <div class="risk-row pulse-yellow">
                        <div class="student-info"><strong>Сауле К.</strong><span>10 "А"</span></div>
                        <div class="risk-reason">Пропуски (25%)</div>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <div id="kioskOverlay" class="kiosk-overlay">
        <button class="close-kiosk" onclick="toggleKiosk()">&times;</button>
        <h1 class="kiosk-logo">AQBOBEK NEWS</h1>
        <div class="marquee-footer">
            <div class="ticker-content">
                🚀 Срочно: Регистрация на олимпиаду IQanat продлена! • 🏆 Поздравляем победителей шахматного турнира! • 🍎 Сегодня в столовой яблочный пирог! • 🚀 
            </div>
        </div>
    </div>

    <script src="script.js"></script>
</body>
</html>