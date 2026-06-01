// 1. Verificar se o utilizador já tem uma preferência guardada
const temaSalvo = localStorage.getItem('atomicamente-theme');

if (temaSalvo === 'dark') {
    document.documentElement.setAttribute('data-theme', 'dark');
} else {
    document.documentElement.setAttribute('data-theme', 'light');
}

// 2. Função para alternar o tema (Disparada pelo botão do menu)
function alternarModoNoturno() {
    const temaAtual = document.documentElement.getAttribute('data-theme');
    let novoTema = 'light';
    
    if (temaAtual === 'light') {
        novoTema = 'dark';
    }
    
    document.documentElement.setAttribute('data-theme', novoTema);
    localStorage.setItem('atomicamente-theme', novoTema);
    
    // Atualizar o ícone do botão dinamicamente se necessário
    atualizarBotaoTema(novoTema);
}

function atualizarBotaoTema(tema) {
    const btn = document.getElementById('btn-tema');
    if (btn) {
        btn.innerHTML = tema === 'dark' ? '☀️ Modo Claro' : '🌙 Modo Escuro';
    }
}

// Inicializar o texto do botão assim que a página carregar
document.addEventListener("DOMContentLoaded", () => {
    const temaAtual = document.documentElement.getAttribute('data-theme');
    atualizarBotaoTema(temaAtual);
});
