<?php
session_start();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Contato | Atomicamente</title>
  
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=Plus+Jakarta+Sans:wght@500;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/plataforma.css">
  
  <style>
    /* VARIÁVEIS DE TEMA (Transição Suave) */
    :root {
        --bg-gradient: radial-gradient(circle at center top, #ffffff 0%, #f3e8ff 100%);
        --text-primary: #1f2937;
        --text-secondary: #4b5563;
        --text-muted: #6b7280;
        --header-bg: rgba(255, 255, 255, 0.7);
        --header-border: rgba(147, 51, 234, 0.15);
        --card-bg: rgba(255, 255, 255, 0.65);
        --card-border: rgba(147, 51, 234, 0.15);
        --card-hover-shadow: rgba(147, 51, 234, 0.12);
        --list-border: rgba(147, 51, 234, 0.1);
        --title-gradient-1: #4c1d95;
        --title-gradient-2: #9333ea;
        --icon-wrapper-bg: rgba(168, 85, 247, 0.15);
    }

    /* MODO NOTURNO */
    body.dark-theme {
        --bg-gradient: radial-gradient(circle at center top, #1e112a 0%, #0d0614 100%);
        --text-primary: #ffffff;
        --text-secondary: rgba(255, 255, 255, 0.6);
        --text-muted: rgba(255, 255, 255, 0.4);
        --header-bg: rgba(0, 0, 0, 0.3);
        --header-border: rgba(255, 255, 255, 0.05);
        --card-bg: rgba(255, 255, 255, 0.03);
        --card-border: rgba(255, 255, 255, 0.08);
        --card-hover-shadow: rgba(0, 0, 0, 0.4);
        --list-border: rgba(255, 255, 255, 0.05);
        --title-gradient-1: #ffffff;
        --title-gradient-2: #d8b4fe;
        --icon-wrapper-bg: rgba(168, 85, 247, 0.1);
    }

    body {
        margin: 0; padding: 0; min-height: 100vh;
        font-family: 'Inter', sans-serif;
        background: var(--bg-gradient);
        color: var(--text-primary);
        overflow-x: hidden;
        transition: background 0.5s ease, color 0.5s ease;
    }

    #bg-canvas {
        position: fixed; top: 0; left: 0; width: 100%; height: 100%;
        z-index: 0; pointer-events: all;
    }

    /* Header Premium */
    .nav-header {
        position: relative; z-index: 2;
        padding: 16px 40px;
        display: flex; justify-content: space-between; align-items: center;
        background: var(--header-bg);
        backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px);
        border-bottom: 1px solid var(--header-border);
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.02);
        transition: background 0.5s ease, border-color 0.5s ease;
    }

    .header-brand {
        display: flex; align-items: center; gap: 12px; text-decoration: none;
    }

    .header-brand img {
        height: 38px; filter: drop-shadow(0 2px 4px rgba(147, 51, 234, 0.2));
    }

    .header-brand span {
        font-family: 'Plus Jakarta Sans', sans-serif; font-size: 1.25rem;
        font-weight: 800; color: #7c3aed; letter-spacing: -0.5px;
    }

    /* A Badge do Contato */
    .header-badge {
        background: linear-gradient(135deg, #7c3aed, #a855f7);
        color: white;
        font-size: 0.65rem; font-weight: 800; text-transform: uppercase;
        letter-spacing: 1px; padding: 4px 10px; border-radius: 999px;
        margin-left: 4px; box-shadow: 0 2px 8px rgba(124, 58, 237, 0.3);
    }

    .header-actions {
        display: flex; align-items: center; gap: 16px;
    }

    /* Botão Theme Toggle Premium */
    .theme-toggle-btn {
        background: var(--icon-wrapper-bg); border: 1px solid var(--card-border);
        color: #a855f7; cursor: pointer;
        width: 40px; height: 40px; border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        transition: all 0.3s ease; outline: none;
    }

    .theme-toggle-btn:hover {
        transform: scale(1.05); box-shadow: 0 4px 12px rgba(168, 85, 247, 0.2);
    }

    .theme-toggle-btn svg {
        width: 20px; height: 20px; fill: currentColor; transition: transform 0.5s ease;
    }

    /* Oculta o ícone de sol ou lua com base no tema ativo */
    body:not(.dark-theme) .icon-sun, body.dark-theme .icon-moon { display: none; }
    body.dark-theme .icon-sun { display: block; }

    .nav-btn {
        padding: 10px 20px; background: rgba(147, 51, 234, 0.1);
        color: #7c3aed; text-decoration: none; border-radius: 8px;
        font-weight: 600; font-size: 0.9rem; border: 1px solid rgba(147, 51, 234, 0.2);
        transition: all 0.3s;
    }

    .nav-btn:hover { background: #7c3aed; color: #ffffff; box-shadow: 0 4px 12px rgba(124, 58, 237, 0.3); }

    /* Container Principal */
    .contact-wrapper {
        position: relative; z-index: 2; max-width: 1100px; margin: 60px auto; padding: 0 20px;
    }

    .page-title { text-align: center; margin-bottom: 50px; }

    .page-title h1 {
        font-family: 'Plus Jakarta Sans', sans-serif; font-size: 2.8rem; font-weight: 800;
        margin: 0 0 15px 0; letter-spacing: -1px;
        background: linear-gradient(135deg, var(--title-gradient-1) 0%, var(--title-gradient-2) 100%);
        -webkit-background-clip: text; -webkit-text-fill-color: transparent;
        transition: all 0.5s ease;
    }

    .page-title p {
        font-size: 1.1rem; color: var(--text-secondary);
        max-width: 600px; margin: 0 auto; line-height: 1.6; transition: color 0.5s ease;
    }

    .contact-grid {
        display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
        gap: 30px; margin-bottom: 50px;
    }

    /* Cards */
    .team-card {
        background: var(--card-bg);
        backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px);
        border: 1px solid var(--card-border);
        border-radius: 20px; padding: 35px 30px;
        transition: transform 0.3s ease, box-shadow 0.3s ease, border-color 0.3s ease, background 0.5s ease;
        position: relative; overflow: hidden;
    }

    .team-card::before {
        content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 4px;
        background: linear-gradient(90deg, #7c3aed, #a855f7); opacity: 0; transition: opacity 0.3s;
    }

    .team-card:hover {
        transform: translateY(-5px); box-shadow: 0 20px 40px var(--card-hover-shadow);
        border-color: rgba(168, 85, 247, 0.4);
    }

    .team-card:hover::before { opacity: 1; }

    .icon-wrapper {
        width: 60px; height: 60px; background: var(--icon-wrapper-bg);
        border-radius: 14px; display: flex; align-items: center; justify-content: center;
        font-size: 1.8rem; margin-bottom: 20px; border: 1px solid rgba(168, 85, 247, 0.3);
        transition: background 0.5s ease;
    }

    .card-title {
        font-family: 'Plus Jakarta Sans', sans-serif; font-size: 1.3rem;
        font-weight: 700; margin: 0 0 5px 0; color: var(--text-primary); transition: color 0.5s ease;
    }

    .card-subtitle {
        font-size: 0.9rem; color: #a855f7; font-weight: 600; margin-bottom: 20px;
    }

    .member-list { list-style: none; padding: 0; margin: 0; }
    
    .member-item {
        display: flex; flex-direction: column; padding: 12px 0;
        border-bottom: 1px solid var(--list-border); transition: border-color 0.5s ease;
    }
    
    .member-item:last-child { border-bottom: none; padding-bottom: 0; }

    .member-name { font-weight: 600; font-size: 0.95rem; color: var(--text-primary); transition: color 0.5s ease; }

    .member-email {
        font-size: 0.85rem; color: var(--text-muted); text-decoration: none; margin-top: 4px; transition: color 0.2s;
    }
    .member-email:hover { color: #a855f7; text-decoration: underline; }
    
    .card-description {
        font-size: 0.85rem; color: var(--text-muted); margin-top: 20px; line-height: 1.5; transition: color 0.5s ease;
    }

    @media (max-width: 768px) {
        .page-title h1 { font-size: 2.2rem; }
        .contact-grid { grid-template-columns: 1fr; }
        .nav-header { padding: 15px 20px; }
        .header-brand span:not(.header-badge) { display: none; }
    }
  </style>
</head>
<body>

  <canvas id="bg-canvas"></canvas>

  <header class="nav-header">
    <a href="index.php" class="header-brand">
        <img src="assets/icone-simplificado.png" alt="Logo Atomicamente">
        <span>Atomicamente</span>
        <span class="header-badge">Contato</span>
    </a>
    
    <div class="header-actions">
        <button id="theme-btn" class="theme-toggle-btn" aria-label="Alternar Tema">
            <svg class="icon-moon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
            </svg>
            <svg class="icon-sun" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                <circle cx="12" cy="12" r="5"></circle>
                <line x1="12" y1="1" x2="12" y2="3"></line>
                <line x1="12" y1="21" x2="12" y2="23"></line>
                <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line>
                <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line>
                <line x1="1" y1="12" x2="3" y2="12"></line>
                <line x1="21" y1="12" x2="23" y2="12"></line>
                <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line>
                <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>
            </svg>
        </button>
        <a href="index.php" class="nav-btn">Início</a>
    </div>
  </header>

  <main class="contact-wrapper">
    <div class="page-title">
        <h1>Fale Conosco</h1>
        <p>Atrás de cada reação de sucesso, existe uma equipe incrível. Conheça as mentes por trás da plataforma e entre em contato.</p>
    </div>

    <div class="contact-grid">
        <div class="team-card">
            <div class="icon-wrapper">📩</div>
            <h2 class="card-title">Atendimento Geral</h2>
            <div class="card-subtitle">Dúvidas, parcerias e suporte</div>
            <ul class="member-list">
                <li class="member-item">
                    <span class="member-name">Suporte Institucional</span>
                    <a href="mailto:contato@atomicamente.net" class="member-email">contato@atomicamente.net</a>
                </li>
            </ul>
            <p class="card-description">Precisa de ajuda com a plataforma ou tem alguma dúvida comercial? Envie um e-mail e nossa equipe responderá na velocidade da luz.</p>
        </div>

        <div class="team-card">
            <div class="icon-wrapper">💻</div>
            <h2 class="card-title">Tecnologia</h2>
            <div class="card-subtitle">Desenvolvimento & Engenharia</div>
            <ul class="member-list">
                <li class="member-item">
                    <span class="member-name">Gustavo Santos</span>
                    <a href="mailto:gustavo4.santos@alunos.ifsuldeminas.edu.br" class="member-email">gustavo4.santos@alunos.ifsuldeminas.edu.br</a>
                </li>
            </ul>
            <p class="card-description">Responsável pela arquitetura, código e experiência do usuário. Encontrou algum bug ou tem sugestões de novos recursos? Fale direto com o Dev.</p>
        </div>

        <div class="team-card" style="grid-column: 1 / -1;">
            <div class="icon-wrapper">🧪</div>
            <h2 class="card-title">Conteúdo & Ciência</h2>
            <div class="card-subtitle">O time de Especialistas em Química</div>
            
            <ul class="member-list" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 15px;">
                <li class="member-item"><span class="member-name">Maria Fernanda</span><a href="mailto:maria2.cardoso@alunos.ifsuldeminas.edu.br" class="member-email">maria2.cardoso@alunos.ifsuldeminas.edu.br</a></li>
                <li class="member-item"><span class="member-name">Lívia</span><a href="mailto:lialvarenga8888@gmail.com" class="member-email">lialvarenga8888@gmail.com</a></li>
                <li class="member-item"><span class="member-name">Ana Júlia</span><a href="mailto:anajuliag903@gmail.com" class="member-email">anajuliag903@gmail.com</a></li>
                <li class="member-item"><span class="member-name">Larissa</span><a href="mailto:larissa3.carvalho@alunos.ifsuldeminas.edu.br" class="member-email">larissa3.carvalho@alunos.ifsuldeminas.edu.br</a></li>
                <li class="member-item"><span class="member-name">Ana Laura</span><a href="mailto:ana4.rosa@alunos.ifsuldeminas.edu.br" class="member-email">ana4.rosa@alunos.ifsuldeminas.edu.br</a></li>
                <li class="member-item"><span class="member-name">Rita</span><a href="mailto:ritacborges070209@gmail.com" class="member-email">ritacborges070209@gmail.com</a></li>
            </ul>
        </div>
    </div>
  </main>

  <script>
    // --- Lógica do Theme Toggle ---
    const themeBtn = document.getElementById('theme-btn');
    const body = document.body;

    // Verifica se já existe uma preferência salva
    if (localStorage.getItem('atomicamente-theme') === 'dark') {
        body.classList.add('dark-theme');
    }

    themeBtn.addEventListener('click', () => {
        body.classList.toggle('dark-theme');
        const isDark = body.classList.contains('dark-theme');
        localStorage.setItem('atomicamente-theme', isDark ? 'dark' : 'light');
    });

    // --- Motor Gráfico (Partículas e Palavras) ---
    const canvas = document.getElementById('bg-canvas');
    const ctx = canvas.getContext('2d');
    
    let particles = [];
    let floatingTexts = [];
    const mouse = { x: null, y: null, radius: 150 };
    const palavrasSuporte = ["Inovação", "Colaboração", "Ciência", "Evolução", "Tecnologia", "Educação", "Pesquisa", "Atomicamente", "Conexão", "Equipe"];

    function resizeCanvas() {
        canvas.width = window.innerWidth;
        canvas.height = window.innerHeight;
    }
    window.addEventListener('resize', resizeCanvas);
    resizeCanvas();

    window.addEventListener('mousemove', (e) => { mouse.x = e.x; mouse.y = e.y; });
    window.addEventListener('mouseout', () => { mouse.x = null; mouse.y = null; });

    class Particle {
        constructor() {
            this.x = Math.random() * canvas.width;
            this.y = Math.random() * canvas.height;
            this.size = Math.random() * 2 + 1;
            this.speedX = (Math.random() - 0.5) * 0.5;
            this.speedY = (Math.random() - 0.5) * 0.5;
        }
        update() {
            this.x += this.speedX; this.y += this.speedY;
            if (this.x > canvas.width || this.x < 0) this.speedX = -this.speedX;
            if (this.y > canvas.height || this.y < 0) this.speedY = -this.speedY;

            if (mouse.x != null && mouse.y != null) {
                let dx = this.x - mouse.x; let dy = this.y - mouse.y;
                let distance = Math.sqrt(dx * dx + dy * dy);
                if (distance < mouse.radius) {
                    let force = (mouse.radius - distance) / mouse.radius;
                    this.x += (dx / distance) * force * 1.5;
                    this.y += (dy / distance) * force * 1.5;
                }
            }
        }
        draw(isDark) {
            // Ajusta a cor com base no tema ativo
            ctx.fillStyle = isDark ? 'rgba(168, 85, 247, 0.35)' : 'rgba(124, 58, 237, 0.25)';
            ctx.beginPath(); ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2); ctx.fill();
        }
    }

    class FloatingText {
        constructor() { this.reset(); this.opacity = Math.random() * 0.2; }
        reset() {
            this.x = Math.random() * (canvas.width - 150) + 50;
            this.y = Math.random() * (canvas.height - 50) + 25;
            this.text = palavrasSuporte[Math.floor(Math.random() * palavrasSuporte.length)];
            this.speedY = -(Math.random() * 0.15 + 0.05);
            this.opacity = 0; this.fadeSpeed = Math.random() * 0.003 + 0.0015;
            this.state = 'fadeIn'; 
            this.maxOpacity = Math.random() * 0.15 + 0.05; 
            this.holdTime = Math.random() * 100 + 50; this.currentHold = 0;
        }
        update() {
            this.y += this.speedY;
            if (this.state === 'fadeIn') {
                this.opacity += this.fadeSpeed;
                if (this.opacity >= this.maxOpacity) { this.opacity = this.maxOpacity; this.state = 'hold'; }
            } else if (this.state === 'hold') {
                this.currentHold++;
                if (this.currentHold >= this.holdTime) this.state = 'fadeOut';
            } else if (this.state === 'fadeOut') {
                this.opacity -= this.fadeSpeed;
                if (this.opacity <= 0) this.reset();
            }
        }
        draw(isDark) {
            ctx.font = '500 11px "Plus Jakarta Sans", sans-serif';
            // Cor do texto de acordo com o tema
            ctx.fillStyle = isDark ? `rgba(216, 180, 254, ${this.opacity})` : `rgba(107, 33, 168, ${this.opacity})`;
            ctx.letterSpacing = "1px"; ctx.fillText(this.text, this.x, this.y);
        }
    }

    function init() {
        particles = []; floatingTexts = [];
        const numParticles = Math.floor((canvas.width * canvas.height) / 10000);
        for (let i = 0; i < numParticles; i++) particles.push(new Particle());
        const numTexts = Math.floor(canvas.width / 250); 
        for (let i = 0; i < numTexts; i++) floatingTexts.push(new FloatingText());
    }

    function animate() {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        const isDark = document.body.classList.contains('dark-theme');
        
        floatingTexts.forEach(t => { t.update(); t.draw(isDark); });
        particles.forEach(p => { p.update(); p.draw(isDark); });
        
        for (let a = 0; a < particles.length; a++) {
            for (let b = a; b < particles.length; b++) {
                let dx = particles[a].x - particles[b].x; let dy = particles[a].y - particles[b].y;
                let distance = Math.sqrt(dx * dx + dy * dy);
                if (distance < 120) {
                    let opacity = (1 - (distance / 120)) * (isDark ? 0.15 : 0.12);
                    ctx.strokeStyle = `rgba(124, 58, 237, ${opacity})`;
                    ctx.lineWidth = 0.8; ctx.beginPath();
                    ctx.moveTo(particles[a].x, particles[a].y); ctx.lineTo(particles[b].x, particles[b].y); ctx.stroke();
                }
            }
        }
        requestAnimationFrame(animate);
    }

    init(); animate();
  </script>
</body>
</html>
