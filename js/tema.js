// CONTROLO DE MODO NOTURNO REFINADO
const temaSalvo = localStorage.getItem('atomicamente-theme');
if (temaSalvo === 'dark') {
    document.documentElement.setAttribute('data-theme', 'dark');
} else {
    document.documentElement.setAttribute('data-theme', 'light');
}

function alternarModoNoturno() {
    const temaAtual = document.documentElement.getAttribute('data-theme');
    let novoTema = temaAtual === 'light' ? 'dark' : 'light';
    
    document.documentElement.setAttribute('data-theme', novoTema);
    localStorage.setItem('atomicamente-theme', novoTema);
    atualizarBotaoTema(novoTema);
}

function atualizarBotaoTema(tema) {
    const btn = document.getElementById('btn-tema-texto');
    if (btn) {
        btn.innerHTML = tema === 'dark' ? '☀️ Modo Claro' : '🌙 Modo Escuro';
    }
}

// GERENCIAMENTO DE MENUS SUSPENSOS (DROPDOWNS)
function alternarDropdown(idDropdown) {
    // Fecha outros dropdowns abertos antes de abrir o atual
    document.querySelectorAll('.dropdown-conteudo').forEach(menu => {
        if (menu.id !== idDropdown) menu.classList.remove('mostrar');
    });
    document.getElementById(idDropdown).classList.toggle('mostrar');
}

// Fecha os menus se o usuário clicar em qualquer lugar fora deles
window.onclick = function(event) {
    if (!event.target.closest('.menu-dropdown')) {
        document.querySelectorAll('.dropdown-conteudo').forEach(menu => {
            menu.classList.remove('mostrar');
        });
    }
}

document.addEventListener("DOMContentLoaded", () => {
    const temaAtual = document.documentElement.getAttribute('data-theme');
    atualizarBotaoTema(temaAtual);
});
