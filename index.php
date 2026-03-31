<?php
session_start();

if (isset($_SESSION['id']) && isset($_SESSION['role'])) {
    switch ($_SESSION['role']) {
        case 'student': header("Location: student/dashboard.php"); exit();
        case 'teacher': header("Location: teacher/dashboard.php"); exit();
        case 'parent':  header("Location: parent/dashboard.php");  exit();
        case 'admin':   header("Location: admin/dashboard.php");   exit();
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aqbobek — Умный школьный портал</title>
    <link rel="stylesheet" href="https:cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https:fonts.googleapis.com/css2?family=Unbounded:wght@400;700;900&family=Mulish:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #6c5ce7;
            --secondary: #e84393;
            --accent: #00e676;
            --bg: #080618;
            --glass: rgba(255,255,255,0.06);
            --border: rgba(255,255,255,0.12);
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        html, body {
            height: 100%;
            overflow-x: hidden;
        }

        body {
            font-family: 'Mulish', sans-serif;
            background: var(--bg);
            color: #fff;
            min-height: 100vh;
        }

        /* BG */
        .bg-layer {
            position: fixed; inset: 0; z-index: 0;
            background:
                radial-gradient(ellipse 70% 60% at 15% 20%, rgba(108,92,231,0.3) 0%, transparent 60%),
                radial-gradient(ellipse 50% 60% at 85% 75%, rgba(232,67,147,0.25) 0%, transparent 60%),
                radial-gradient(ellipse 40% 40% at 50% 50%, rgba(0,230,118,0.04) 0%, transparent 60%);
            pointer-events: none;
        }

        /* GRID PATTERN */
        .bg-grid {
            position: fixed; inset: 0; z-index: 0; pointer-events: none;
            background-image:
                linear-gradient(rgba(255,255,255,0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,0.03) 1px, transparent 1px);
            background-size: 60px 60px;
        }

        /* HEADER */
        header {
            position: fixed; top: 0; left: 0; right: 0;
            z-index: 100;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 20px 60px;
            background: rgba(8,6,24,0.7);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--border);
        }

        .logo {
            font-family: 'Unbounded', sans-serif;
            font-size: 1.5rem;
            font-weight: 900;
            background: linear-gradient(90deg, #fff 0%, var(--primary) 50%, var(--secondary) 100%);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
        }

        .logo-icon {
            width: 38px; height: 38px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1rem; color: #fff;
            -webkit-text-fill-color: #fff;
            flex-shrink: 0;
        }

        .header-nav {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .btn-outline {
            padding: 10px 28px;
            border: 1px solid var(--border);
            border-radius: 12px;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 600;
            font-family: 'Mulish', sans-serif;
            transition: all 0.25s;
            background: transparent;
        }
        .btn-outline:hover {
            border-color: rgba(255,255,255,0.4);
            color: #fff;
            background: rgba(255,255,255,0.06);
        }

        .btn-filled {
            padding: 10px 28px;
            border: none;
            border-radius: 12px;
            color: #fff;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 700;
            font-family: 'Mulish', sans-serif;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            transition: all 0.25s;
            box-shadow: 0 4px 20px rgba(108,92,231,0.4);
        }
        .btn-filled:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(108,92,231,0.6);
        }

        /* HERO */
        .hero {
            position: relative;
            z-index: 1;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 120px 40px 80px;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(0,230,118,0.1);
            border: 1px solid rgba(0,230,118,0.3);
            border-radius: 20px;
            padding: 8px 20px;
            font-size: 0.82rem;
            font-weight: 600;
            color: var(--accent);
            margin-bottom: 32px;
            animation: fadeUp 0.6s ease both;
        }
        .badge-dot {
            width: 7px; height: 7px;
            border-radius: 50%;
            background: var(--accent);
            animation: blink 1.5s infinite;
        }
        @keyframes blink { 0%,100%{opacity:1} 50%{opacity:0.3} }

        .hero-title {
            font-family: 'Unbounded', sans-serif;
            font-size: clamp(2.8rem, 6vw, 5.5rem);
            font-weight: 900;
            line-height: 1.05;
            letter-spacing: -2px;
            margin-bottom: 28px;
            animation: fadeUp 0.6s 0.1s ease both;
        }

        .hero-title .line-1 { display: block; color: #fff; }
        .hero-title .line-2 {
            display: block;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero-sub {
            font-size: 1.1rem;
            font-weight: 300;
            color: rgba(255,255,255,0.55);
            max-width: 560px;
            line-height: 1.7;
            margin-bottom: 48px;
            animation: fadeUp 0.6s 0.2s ease both;
        }

        .hero-actions {
            display: flex;
            gap: 16px;
            justify-content: center;
            flex-wrap: wrap;
            animation: fadeUp 0.6s 0.3s ease both;
        }

        .btn-hero-primary {
            padding: 16px 40px;
            border: none;
            border-radius: 16px;
            color: #fff;
            text-decoration: none;
            font-size: 1rem;
            font-weight: 700;
            font-family: 'Mulish', sans-serif;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            box-shadow: 0 8px 30px rgba(108,92,231,0.5);
            transition: all 0.25s;
            display: flex; align-items: center; gap: 10px;
        }
        .btn-hero-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 14px 40px rgba(108,92,231,0.7);
        }

        .btn-hero-secondary {
            padding: 16px 40px;
            border: 1px solid var(--border);
            border-radius: 16px;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            font-size: 1rem;
            font-weight: 600;
            font-family: 'Mulish', sans-serif;
            background: rgba(255,255,255,0.05);
            transition: all 0.25s;
            display: flex; align-items: center; gap: 10px;
        }
        .btn-hero-secondary:hover {
            border-color: rgba(255,255,255,0.3);
            background: rgba(255,255,255,0.1);
            color: #fff;
        }

        /* FEATURES */
        .features {
            position: relative; z-index: 1;
            padding: 80px 60px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .section-label {
            text-align: center;
            font-size: 0.8rem;
            font-weight: 700;
            letter-spacing: 3px;
            text-transform: uppercase;
            color: var(--primary);
            margin-bottom: 16px;
        }

        .section-title {
            font-family: 'Unbounded', sans-serif;
            font-size: clamp(1.6rem, 3vw, 2.4rem);
            font-weight: 800;
            text-align: center;
            margin-bottom: 60px;
            letter-spacing: -1px;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 24px;
        }

        .feature-card {
            background: var(--glass);
            border: 1px solid var(--border);
            border-radius: 24px;
            padding: 32px;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }
        .feature-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 2px;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            opacity: 0;
            transition: opacity 0.3s;
        }
        .feature-card:hover { transform: translateY(-6px); border-color: rgba(255,255,255,0.2); }
        .feature-card:hover::before { opacity: 1; }

        .feature-icon {
            width: 52px; height: 52px;
            border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.4rem;
            margin-bottom: 20px;
        }
        .icon-purple { background: rgba(108,92,231,0.2); }
        .icon-pink   { background: rgba(232,67,147,0.2); }
        .icon-green  { background: rgba(0,230,118,0.15); }
        .icon-blue   { background: rgba(0,180,255,0.15); }

        .feature-title {
            font-family: 'Unbounded', sans-serif;
            font-size: 1rem;
            font-weight: 700;
            margin-bottom: 12px;
        }
        .feature-desc {
            font-size: 0.9rem;
            color: rgba(255,255,255,0.5);
            line-height: 1.6;
        }

        /* ROLES */
        .roles {
            position: relative; z-index: 1;
            padding: 40px 60px 100px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .roles-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
        }

        .role-card {
            background: var(--glass);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 28px;
            text-align: center;
            transition: all 0.3s;
            text-decoration: none;
            color: inherit;
            display: block;
        }
        .role-card:hover {
            transform: translateY(-4px);
            background: rgba(255,255,255,0.1);
        }
        .role-emoji { font-size: 2.5rem; margin-bottom: 14px; display: block; }
        .role-name {
            font-family: 'Unbounded', sans-serif;
            font-size: 0.95rem;
            font-weight: 700;
            margin-bottom: 8px;
        }
        .role-desc { font-size: 0.82rem; color: rgba(255,255,255,0.45); line-height: 1.5; }

        /* FOOTER */
        footer {
            position: relative; z-index: 1;
            text-align: center;
            padding: 30px;
            border-top: 1px solid var(--border);
            color: rgba(255,255,255,0.3);
            font-size: 0.82rem;
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(24px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* SCROLLBAR */
        * { scrollbar-width: none; }
        *::-webkit-scrollbar { display: none; }
    </style>
</head>
<body>

<div class="bg-layer"></div>
<div class="bg-grid"></div>

<!-- HEADER -->
<header>
    <a href="index.php" class="logo">
        <div class="logo-icon"><i class="fas fa-bolt"></i></div>
        AQBOBEK
    </a>
    <nav class="header-nav">
        <a href="public/login.php" class="btn-outline">Войти</a>
        <a href="public/register.php" class="btn-filled">Регистрация</a>
    </nav>
</header>

<!-- HERO -->
<section class="hero">
    <div class="hero-badge">
        <div class="badge-dot"></div>
        Умный школьный портал нового поколения
    </div>
    <h1 class="hero-title">
        <span class="line-1">Образование,</span>
        <span class="line-2">усиленное AI</span>
    </h1>
    <p class="hero-sub">
        Единая платформа для учеников, учителей, родителей и администрации.
        Предиктивная аналитика, граф знаний, умное расписание.
    </p>
    <div class="hero-actions">
        <a href="public/register.php" class="btn-hero-primary">
            <i class="fas fa-rocket"></i>
            Начать бесплатно
        </a>
        <a href="public/login.php" class="btn-hero-secondary">
            <i class="fas fa-sign-in-alt"></i>
            Войти в систему
        </a>
    </div>
</section>

<!-- FEATURES -->
<section class="features">
    <div class="section-label">Возможности</div>
    <h2 class="section-title">Всё что нужно школе</h2>
    <div class="features-grid">
        <div class="feature-card">
            <div class="feature-icon icon-purple">🤖</div>
            <div class="feature-title">AI-наставник</div>
            <div class="feature-desc">Персональный ИИ анализирует пробелы в знаниях и предсказывает результат следующего СОЧ с вероятностью провала.</div>
        </div>
        <div class="feature-card">
            <div class="feature-icon icon-pink">📊</div>
            <div class="feature-title">Граф знаний</div>
            <div class="feature-desc">Визуальная карта освоенных тем. Сразу видно что хорошо, что в процессе, а где пробел.</div>
        </div>
        <div class="feature-card">
            <div class="feature-icon icon-green">🏆</div>
            <div class="feature-title">Геймификация</div>
            <div class="feature-desc">XP, уровни, ачивки и лидерборд класса. Учёба становится мотивирующей игрой.</div>
        </div>
        <div class="feature-card">
            <div class="feature-icon icon-blue">📅</div>
            <div class="feature-title">Умное расписание</div>
            <div class="feature-desc">Автогенерация без конфликтов. Если учитель заболел — расписание перестраивается автоматически.</div>
        </div>
    </div>
</section>

<!-- ROLES -->
<section class="roles">
    <div class="section-label">Роли</div>
    <h2 class="section-title">Для каждого участника</h2>
    <div class="roles-grid">
        <a href="public/login.php" class="role-card">
            <span class="role-emoji">👨‍🎓</span>
            <div class="role-name">Ученик</div>
            <div class="role-desc">Оценки, AI-наставник, портфолио, рейтинг, граф знаний</div>
        </a>
        <a href="public/login.php" class="role-card">
            <span class="role-emoji">👨‍🏫</span>
            <div class="role-name">Учитель</div>
            <div class="role-desc">Журнал оценок, зона риска, AI-отчёт за 1 клик</div>
        </a>
        <a href="public/login.php" class="role-card">
            <span class="role-emoji">👨‍👩‍👦</span>
            <div class="role-name">Родитель</div>
            <div class="role-desc">Дашборд ребёнка, AI-выжимка за неделю, события</div>
        </a>
        <a href="public/login.php" class="role-card">
            <span class="role-emoji">🛡️</span>
            <div class="role-name">Администратор</div>
            <div class="role-desc">Глобальный радар, управление расписанием, публикации</div>
        </a>
    </div>
</section>

<footer>
    © 2026 Aqbobek Lyceum — Умный школьный портал
</footer>

</body>
</html>