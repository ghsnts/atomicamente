<?php
session_start();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sobre Nós | Atomicamente</title>
  
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=Plus+Jakarta+Sans:wght@500;700;800&display=swap" rel="stylesheet">
  
  <style>
    /* VARIÁVEIS DE TEMA */
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
        --title-gradient-1: #4c1d95;
        --title-gradient-2: #9333ea;
        --icon-wrapper-bg: rgba(168, 85, 247, 0.15);
        --accent-glow: rgba(147, 51, 234, 0.2);
        --glass-panel: rgba(255, 255, 255, 0.4);
        --film-track-bg: rgba(147, 51, 234, 0.08);
    }

    body.dark-theme {
        --bg-gradient: radial-gradient(circle at center top, #1e112a 0%, #0d0614 100%);
        --text-primary: #ffffff;
        --text-secondary: rgba(255, 255, 255, 0.7);
        --text-muted: rgba(255, 255, 255, 0.5);
        --header-bg: rgba(0, 0, 0, 0.3);
        --header-border: rgba(255, 255, 255, 0.05);
        --card-bg: rgba(255, 255, 255, 0.03);
        --card-border: rgba(255, 255, 255, 0.08);
        --card-hover-shadow: rgba(0, 0, 0, 0.4);
        --title-gradient-1: #ffffff;
        --title-gradient-2: #d8b4fe;
        --icon-wrapper-bg: rgba(168, 85, 247, 0.1);
        --accent-glow: rgba(168, 85, 247, 0.3);
        --glass-panel: rgba(0, 0, 0, 0.2);
        --film-track-bg: rgba(0, 0, 0, 0.3);
    }

    body {
        margin: 0; padding: 0; min-height: 100vh;
        font-family: 'Inter', sans-serif;
        background: var(--bg-gradient);
        color: var(--text-primary);
        overflow-x: hidden;
        transition: background 0.5s ease, color 0.5s ease;
    }

    #bg-canvas { position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 0; pointer-events: none; }

    /* Header Premium */
    .nav-header {
        position: fixed; top: 0; left: 0; width: 100%; z-index: 100;
        padding: 16px 40px; box-sizing: border-box;
        display: flex; justify-content: space-between; align-items: center;
        background: var(--header-bg); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px);
        border-bottom: 1px solid var(--header-border);
        transition: background 0.5s ease, border-color 0.5s ease;
    }

    .header-brand { display: flex; align-items: center; gap: 12px; text-decoration: none; }
    .header-brand img { height: 38px; filter: drop-shadow(0 2px 4px rgba(147, 51, 234, 0.2)); }
    .header-brand span { font-family: 'Plus Jakarta Sans', sans-serif; font-size: 1.25rem; font-weight: 800; color: #7c3aed; letter-spacing: -0.5px; }

    .header-badge {
        background: linear-gradient(135deg, #7c3aed, #a855f7); color: white;
        font-size: 0.65rem; font-weight: 800; text-transform: uppercase;
        letter-spacing: 1px; padding: 4px 10px; border-radius: 999px; margin-left: 4px;
    }

    .header-actions { display: flex; align-items: center; gap: 16px; }

    .theme-toggle-btn {
        background: var(--icon-wrapper-bg); border: 1px solid var(--card-border);
        color: #a855f7; cursor: pointer; width: 40px; height: 40px; border-radius: 12px;
        display: flex; align-items: center; justify-content: center; transition: all 0.3s ease;
    }
    .theme-toggle-btn:hover { transform: scale(1.05); }
    .theme-toggle-btn svg { width: 20px; height: 20px; fill: currentColor; }
    body:not(.dark-theme) .icon-sun, body.dark-theme .icon-moon { display: none; }
    body.dark-theme .icon-sun { display: block; }

    .nav-btn {
        padding: 10px 20px; background: rgba(147, 51, 234, 0.1); color: #7c3aed;
        text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 0.9rem;
        border: 1px solid rgba(147, 51, 234, 0.2); transition: all 0.3s;
    }
    .nav-btn:hover { background: #7c3aed; color: #ffffff; }

    /* Main Content */
    .about-wrapper { position: relative; z-index: 2; max-width: 1200px; margin: 120px auto 100px; padding: 0 20px; }

    .section-header { text-align: center; margin-bottom: 60px; }
    .section-header h1 {
        font-family: 'Plus Jakarta Sans', sans-serif; font-size: 3.5rem; font-weight: 800;
        margin: 0 0 15px 0; letter-spacing: -1.5px; line-height: 1.1;
        background: linear-gradient(135deg, var(--title-gradient-1) 0%, var(--title-gradient-2) 100%);
        -webkit-background-clip: text; -webkit-text-fill-color: transparent;
    }
    .section-header p { font-size: 1.2rem; color: var(--text-secondary); max-width: 700px; margin: 0 auto; line-height: 1.6; }

    /* Bento Box Story Grid */
    .story-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 100px; }
    .story-card {
        background: var(--card-bg); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);
        border: 1px solid var(--card-border); border-radius: 24px; padding: 40px;
        transition: all 0.4s ease; position: relative; overflow: hidden;
    }
    .story-card.full-width { grid-column: 1 / -1; }
    .story-card:hover { transform: translateY(-5px); box-shadow: 0 20px 40px var(--card-hover-shadow); border-color: rgba(168, 85, 247, 0.4); }
    
    .icon-svg {
        width: 60px; height: 60px; background: var(--icon-wrapper-bg);
        border-radius: 16px; display: flex; align-items: center; justify-content: center;
        margin-bottom: 25px; border: 1px solid rgba(168, 85, 247, 0.3); color: #a855f7;
    }
    .icon-svg svg { width: 32px; height: 32px; fill: currentColor; }

    .story-title { font-family: 'Plus Jakarta Sans', sans-serif; font-size: 1.5rem; font-weight: 800; margin: 0 0 15px 0; color: var(--text-primary); }
    .story-text p { font-size: 1.05rem; color: var(--text-secondary); line-height: 1.8; margin-top: 0; margin-bottom: 15px; }
    .story-text p:last-child { margin-bottom: 0; }
    .highlight { color: #a855f7; font-weight: 600; }

    /* FILMSTRIP GALLERY (NOVA FEATURE) */
    .gallery-section { margin-bottom: 100px; }
    
    .film-wrapper {
        position: relative;
        margin: 0 -20px; /* Expande um pouco para fora do padding */
        padding: 40px 0;
        background: var(--film-track-bg);
        border-top: 1px solid var(--card-border);
        border-bottom: 1px solid var(--card-border);
        backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px);
        overflow: hidden;
        display: flex;
        box-shadow: inset 0 0 30px var(--card-hover-shadow);
        
        /* A MÁGICA ACONTECE AQUI: Efeito de fade nas bordas direita e esquerda */
        -webkit-mask-image: linear-gradient(to right, transparent, black 15%, black 85%, transparent);
        mask-image: linear-gradient(to right, transparent, black 15%, black 85%, transparent);
    }

    /* As "Perfurações" do Filme */
    .film-wrapper::before, .film-wrapper::after {
        content: ''; position: absolute; left: 0; right: 0; height: 14px;
        background-image: repeating-linear-gradient(to right, 
            transparent 0, transparent 12px, 
            var(--card-bg) 12px, var(--card-bg) 24px);
        z-index: 10; opacity: 0.6;
    }
    .film-wrapper::before { top: 8px; }
    .film-wrapper::after { bottom: 8px; }

    .film-track {
        display: flex; width: max-content;
        /* A animação roda continuamente e pausa no hover */
        animation: scrollFilm 40s linear infinite;
    }

    .film-wrapper:hover .film-track { animation-play-state: paused; }

    .film-strip-segment {
        display: flex; gap: 24px; padding-right: 24px; /* O padding iguala o gap para o loop perfeito */
    }

    @keyframes scrollFilm {
        0% { transform: translateX(0); }
        100% { transform: translateX(-50%); } /* Como temos dois segmentos iguais, 50% é o recomeço exato */
    }

    .film-frame {
        width: 320px; height: 220px; flex-shrink: 0;
        background: var(--glass-panel);
        border: 2px solid var(--card-border);
        border-radius: 12px; padding: 6px;
        position: relative; overflow: hidden;
        transition: all 0.4s ease;
        box-shadow: 0 10px 20px rgba(0,0,0,0.1);
    }

    .film-frame:hover {
        border-color: #a855f7; transform: scale(1.03); box-shadow: 0 15px 30px var(--accent-glow);
    }

    .film-frame img {
        width: 100%; height: 100%; object-fit: cover;
        border-radius: 6px;
    }
    
    .film-overlay {
        position: absolute; inset: 6px; /* Respeita o padding interno do filme */
        background: linear-gradient(to top, rgba(0,0,0,0.85) 0%, transparent 60%);
        color: white; font-weight: 600; font-size: 1rem; border-radius: 6px;
        display: flex; align-items: flex-end; padding: 20px;
        opacity: 0; transition: opacity 0.3s ease;
    }
    
    .film-frame:hover .film-overlay { opacity: 1; }

    /* Perfis da Equipe: Layout 3-3-1 Horizontal Card */
    .team-section h2 { text-align: center; font-family: 'Plus Jakarta Sans', sans-serif; font-size: 2.5rem; margin-bottom: 40px; }
    
    .team-grid { 
        display: flex; 
        flex-wrap: wrap; 
        justify-content: center;
        gap: 20px; 
    }
    
    .profile-card {
        width: calc(33.333% - 14px); /* Mantém 3 por linha exatos no Desktop */
        min-width: 320px;
        background: var(--glass-panel); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px);
        border: 1px solid var(--card-border); border-radius: 20px;
        padding: 22px 18px; text-align: left; transition: all 0.3s ease;
        display: flex; flex-direction: row; align-items: center; gap: 16px;
    }
    .profile-card:hover { transform: translateY(-5px); border-color: #a855f7; box-shadow: 0 15px 30px var(--card-hover-shadow); }

    .profile-image-wrapper { position: relative; width: 75px; height: 75px; flex-shrink: 0; }
    .profile-image-wrapper::before {
        content: ''; position: absolute; inset: -3px; border-radius: 50%;
        background: linear-gradient(135deg, #7c3aed, #d8b4fe); z-index: 0;
        animation: spin 4s linear infinite; opacity: 0.5;
    }
    @keyframes spin { 100% { transform: rotate(360deg); } }
    
    .profile-image-wrapper img {
        position: relative; z-index: 1; width: 100%; height: 100%;
        border-radius: 50%; object-fit: cover; border: 2px solid var(--card-bg);
    }

    .profile-content { display: flex; flex-direction: column; justify-content: center; }

    .profile-name { font-family: 'Plus Jakarta Sans', sans-serif; font-size: 1.1rem; font-weight: 700; margin: 0 0 2px 0; }
    .profile-role { font-size: 0.75rem; font-weight: 700; color: #a855f7; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px; }
    .profile-quote { font-size: 0.85rem; color: var(--text-secondary); font-style: italic; line-height: 1.4; }
    .profile-quote::before, .profile-quote::after { content: '"'; color: #d8b4fe; font-size: 1rem; font-family: serif; }

    /* Responsividade */
    @media (max-width: 1080px) {
        .profile-card { width: calc(50% - 10px); } /* 2 por linha no tablet para não amassar */
    }
    
    @media (max-width: 700px) {
        .section-header h1 { font-size: 2.5rem; }
        .story-grid { grid-template-columns: 1fr; }
        
        .profile-card { 
            width: 100%; 
            flex-direction: column; /* Volta para vertical só no celular */
            text-align: center; 
        } 
        
        .nav-header { padding: 15px 20px; }
        .header-brand span:not(.header-badge) { display: none; }
        
        .film-frame { width: 280px; height: 190px; } /* Menor no mobile */
    }
  </style>
</head>
<body>

  <canvas id="bg-canvas"></canvas>

  <header class="nav-header">
    <a href="index.php" class="header-brand">
        <img src="assets/icone-simplificado.png" alt="Logo">
        <span>Atomicamente</span>
        <span class="header-badge">Sobre</span>
    </a>
    <div class="header-actions">
        <button id="theme-btn" class="theme-toggle-btn" aria-label="Alternar Tema">
            <svg class="icon-moon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>
            <svg class="icon-sun" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line></svg>
        </button>
        <a href="index.php" class="nav-btn">Início</a>
    </div>
  </header>

  <main class="about-wrapper">
    
    <div class="section-header">
        <h1>Da Ideia ao Átomo</h1>
        <p>Muito mais que uma plataforma. Conheça a jornada, nossos laboratórios, o propósito e as mentes que estão transformando o jeito de aprender Química.</p>
    </div>

    <!-- SEÇÃO 1: BENTO BOX STORY -->
    <div class="story-grid">
        <div class="story-card full-width">
            <div class="icon-svg">
                <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm0-14c-3.31 0-6 2.69-6 6s2.69 6 6 6 6-2.69 6-6-2.69-6-6-6zm0 10c-2.21 0-4-1.79-4-4s1.79-4 4-4 4 1.79 4 4-1.79 4-4 4zm0-6c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"/></svg>
            </div>
            <h2 class="story-title">A Faísca Inicial</h2>
            <div class="story-text">
                <p>Tudo começou nos corredores e laboratórios do <span class="highlight">IFSULDEMINAS</span>. Acompanhando a dificuldade real dos alunos com a Química, nos perguntamos: <em>"Como transformar o invisível em algo fascinante e fácil de entender?"</em></p>
                <p>O que era uma conversa abstrata ganhou linhas de código e rigor científico. Juntamos a paixão pela educação, o domínio técnico da informática e a profundidade da química para criar um ambiente onde o aluno não se sinta apenas estudando, mas explorando.</p>
            </div>
        </div>

        <div class="story-card">
            <div class="icon-svg">
                <svg viewBox="0 0 24 24"><path d="M19.35 15.3l-5.3-7.53V3h1.95V1H8v2h2v4.77L4.65 15.3C4.24 15.89 4 16.63 4 17.5 4 19.99 6.01 22 8.5 22h7c2.49 0 4.5-2.01 4.5-4.5 0-.87-.24-1.61-.65-2.2zM15.5 20h-7C7.12 20 6 18.88 6 17.5c0-.52.16-1 .44-1.4l5.56-7.9V3h0v5.2l5.56 7.9c.28.4.44.88.44 1.4 0 1.38-1.12 2.5-2.5 2.5z"/><path d="M13 14c-.55 0-1-.45-1-1s.45-1 1-1 1 .45 1 1-.45 1-1 1z"/></svg>
            </div>
            <h2 class="story-title">A Fórmula da Motivação</h2>
            <div class="story-text">
                <p>Nós não criamos apenas uma plataforma EdTech; nós digitalizamos o apoio que gostaríamos de ter tido. Ver um conceito complexo "clicar" na mente de um estudante é o que nos move.</p>
            </div>
        </div>

        <div class="story-card">
            <div class="icon-svg">
                <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm5 11h-4v4h-2v-4H7v-2h4V7h2v4h4v2z"/></svg>
            </div>
            <h2 class="story-title">De Estudantes, Para Estudantes</h2>
            <div class="story-text">
                <p>Sabemos onde dói. Conhecemos as noites em claro para o vestibular e os desafios do Ensino Médio. Desenhamos o Atomicamente para ser aquele colega genial que te explica a matéria de forma simples, 24/7.</p>
            </div>
        </div>
    </div>

    <!-- SEÇÃO 2: NOSSO HABITAT (FILMSTRIP GALLERY) -->
    <div class="section-header">
        <h1>Nosso Habitat</h1>
        <p>Um vislumbre dos laboratórios, do campus e de onde a magia do conhecimento é testada e programada todos os dias.</p>
    </div>
    
    <div class="gallery-section">
        <div class="film-wrapper">
            <div class="film-track">
                
                <!-- Primeiro Segmento (As 8 fotos) -->
                <div class="film-strip-segment">
                    <div class="film-frame"><img src="assets/foto1.png" alt="O início de tudo"><div class="film-overlay">A Base Tecnológica</div></div>
                    <div class="film-frame"><img src="assets/foto2.png" alt="Momentos"><div class="film-overlay">Foco Total</div></div>
                    <div class="film-frame"><img src="assets/foto3.png" alt="O Campus"><div class="film-overlay">IF</div></div>
                    <div class="film-frame"><img src="assets/foto4.png" alt="Detalhe"><div class="film-overlay">A Pesquisa</div></div>
                    <div class="film-frame"><img src="assets/foto5.jpg" alt="Ana, Anna, Larissa e Maria na Feira de 2025"><div class="film-overlay">Feira de 2025</div></div>
                    <div class="film-frame"><img src="assets/foto6.png" alt="Equipe em Ação"><div class="film-overlay">O Time em Ação</div></div>
                    <div class="film-frame"><img src="assets/foto7.png" alt="Sinergia"><div class="film-overlay">Sinergia</div></div>
                    <div class="film-frame"><img src="assets/foto8.png" alt="Detalhe Final"><div class="film-overlay">Atomicamente</div></div>
                </div>

                <!-- Segundo Segmento (Cópia exata para criar o loop perfeito) -->
                <div class="film-strip-segment">
                    <div class="film-frame"><img src="assets/foto1.png" alt="O início de tudo"><div class="film-overlay">A Base Tecnológica</div></div>
                    <div class="film-frame"><img src="assets/foto2.png" alt="Momentos"><div class="film-overlay">Foco Total</div></div>
                    <div class="film-frame"><img src="assets/foto3.png" alt="O Campus"><div class="film-overlay">O Nosso IF</div></div>
                    <div class="film-frame"><img src="assets/foto4.png" alt="Detalhe"><div class="film-overlay">A Pesquisa</div></div>
                    <div class="film-frame"><img src="assets/foto5.jpg" alt="Ana, Anna, Larissa e Maria na Feira de 2025"><div class="film-overlay">Feira de 2025</div></div>
                    <div class="film-frame"><img src="assets/foto6.png" alt="Equipe em Ação"><div class="film-overlay">O Time em Ação</div></div>
                    <div class="film-frame"><img src="assets/foto7.png" alt="Sinergia"><div class="film-overlay">Sinergia</div></div>
                    <div class="film-frame"><img src="assets/foto8.png" alt="Detalhe Final"><div class="film-overlay">Atomicamente</div></div>
                </div>

            </div>
        </div>
    </div>

    <!-- SEÇÃO 3: QUEM FAZ ACONTECER -->
    <div class="team-section">
        <div class="section-header">
            <h1>A Equipe Atomicamente</h1>
            <p>Os elétrons e os núcleos que mantêm essa molécula unida.</p>
        </div>

        <div class="team-grid">
            
            <!-- Linha 1: Maria Fernanda, Lívia, Ana Júlia -->
            <div class="profile-card">
                <div class="profile-image-wrapper"><img src="assets/maria-fernanda.jpg" alt="Maria Fernanda"></div>
                <div class="profile-content">
                    <h3 class="profile-name">Maria Fernanda</h3>
                    <span class="profile-role">Estudante de Química</span>
                    <div class="profile-quote">Mensagem da Maria Fernanda...</div>
                </div>
            </div>

            <div class="profile-card">
                <div class="profile-image-wrapper"><img src="assets/livia.jpg" alt="Lívia"></div>
                <div class="profile-content">
                    <h3 class="profile-name">Lívia</h3>
                    <span class="profile-role">Estudante de Química</span>
                    <div class="profile-quote">Mensagem da Lívia...</div>
                </div>
            </div>

            <div class="profile-card">
                <div class="profile-image-wrapper"><img src="assets/ana-julia.jpg" alt="Ana Júlia"></div>
                <div class="profile-content">
                    <h3 class="profile-name">Ana Júlia</h3>
                    <span class="profile-role">Estudante de Química</span>
                    <div class="profile-quote">Mensagem da Ana Júlia...</div>
                </div>
            </div>

            <!-- Linha 2: Larissa, Anna Laura, Rita -->
            <div class="profile-card">
                <div class="profile-image-wrapper"><img src="assets/larissa.jpg" alt="Larissa"></div>
                <div class="profile-content">
                    <h3 class="profile-name">Larissa</h3>
                    <span class="profile-role">Estudante de Química</span>
                    <div class="profile-quote">Mensagem da Larissa...</div>
                </div>
            </div>

            <div class="profile-card">
                <div class="profile-image-wrapper"><img src="assets/anna-laura.jpg" alt="Anna Laura"></div>
                <div class="profile-content">
                    <h3 class="profile-name">Anna Laura</h3>
                    <span class="profile-role">Estudante de Química</span>
                    <div class="profile-quote">Mensagem da Anna Laura...</div>
                </div>
            </div>

            <div class="profile-card">
                <div class="profile-image-wrapper"><img src="assets/rita.jpg" alt="Rita"></div>
                <div class="profile-content">
                    <h3 class="profile-name">Rita</h3>
                    <span class="profile-role">Estudante de Química</span>
                    <div class="profile-quote">Mensagem da Rita...</div>
                </div>
            </div>

            <!-- Linha 3: Gustavo -->
            <div class="profile-card">
                <div class="profile-image-wrapper"><img src="assets/gustavo.jpg" alt="Gustavo Santos"></div>
                <div class="profile-content">
                    <h3 class="profile-name">Gustavo Santos</h3>
                    <span class="profile-role">Estudante de Informática</span>
                    <div class="profile-quote">É incrível dar vida a ideias através do código, e essa ideia merecia ganhar uma casa!</div>
                </div>
            </div>

        </div>
    </div>

  </main>

  <script>
    // Gerenciamento de Tema
    const themeBtn = document.getElementById('theme-btn');
    const body = document.body;

    if (localStorage.getItem('atomicamente-theme') === 'dark') { body.classList.add('dark-theme'); }

    themeBtn.addEventListener('click', () => {
        body.classList.toggle('dark-theme');
        localStorage.setItem('atomicamente-theme', body.classList.contains('dark-theme') ? 'dark' : 'light');
    });

    // Fundo Interativo Premium com Palavras
    const canvas = document.getElementById('bg-canvas');
    const ctx = canvas.getContext('2d');
    
    let particles = [];
    let floatingTexts = [];
    const mouse = { x: null, y: null, radius: 150 };
    const palavrasSobre = ["Sonho", "Propósito", "Educação", "Transformação", "Tecnologia", "IFSULDEMINAS", "Química", "Legado", "Inspiração", "Atomicamente"];

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
            this.size = Math.random() * 2 + 0.5;
            this.speedX = (Math.random() - 0.5) * 0.3;
            this.speedY = -(Math.random() * 0.3 + 0.1);
        }
        update() {
            this.x += this.speedX; this.y += this.speedY;
            if (this.x > canvas.width || this.x < 0) this.speedX = -this.speedX;
            if (this.y < 0) { this.y = canvas.height; this.x = Math.random() * canvas.width; }
            
            if (mouse.x != null && mouse.y != null) {
                let dx = this.x - mouse.x; let dy = this.y - mouse.y;
                let distance = Math.sqrt(dx * dx + dy * dy);
                if (distance < mouse.radius) {
                    let force = (mouse.radius - distance) / mouse.radius;
                    this.x += (dx / distance) * force * 1.2;
                    this.y += (dy / distance) * force * 1.2;
                }
            }
        }
        draw(isDark) {
            ctx.fillStyle = isDark ? 'rgba(168, 85, 247, 0.4)' : 'rgba(124, 58, 237, 0.3)';
            ctx.beginPath(); ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2); ctx.fill();
        }
    }

    class FloatingText {
        constructor() { this.reset(); this.opacity = Math.random() * 0.2; }
        reset() {
            this.x = Math.random() * (canvas.width - 200) + 50;
            this.y = Math.random() * (canvas.height - 100) + 50;
            this.text = palavrasSobre[Math.floor(Math.random() * palavrasSobre.length)];
            this.speedY = -(Math.random() * 0.15 + 0.05);
            this.opacity = 0; this.fadeSpeed = Math.random() * 0.002 + 0.001;
            this.state = 'fadeIn'; 
            this.maxOpacity = Math.random() * 0.15 + 0.05; 
            this.holdTime = Math.random() * 150 + 80; this.currentHold = 0;
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
            ctx.font = '500 13px "Plus Jakarta Sans", sans-serif';
            ctx.fillStyle = isDark ? `rgba(216, 180, 254, ${this.opacity})` : `rgba(107, 33, 168, ${this.opacity})`;
            ctx.letterSpacing = "2px"; 
            ctx.fillText(this.text, this.x, this.y);
        }
    }

    function init() {
        particles = []; floatingTexts = [];
        const numParticles = Math.floor((canvas.width * canvas.height) / 12000);
        for (let i = 0; i < numParticles; i++) particles.push(new Particle());
        
        const numTexts = Math.floor(canvas.width / 220); 
        for (let i = 0; i < numTexts; i++) floatingTexts.push(new FloatingText());
    }

    function animate() {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        const isDark = document.body.classList.contains('dark-theme');
        
        floatingTexts.forEach(t => { t.update(); t.draw(isDark); });
        particles.forEach(p => { p.update(); p.draw(isDark); });
        
        requestAnimationFrame(animate);
    }

    init(); animate();
  </script>
</body>
</html>
