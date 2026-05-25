document.addEventListener('DOMContentLoaded', () => {
    const tabTexto = document.getElementById('tabTexto');
    const tabVideo = document.getElementById('tabVideo');
    const tabExercicios = document.getElementById('tabExercicios');

    const pTexto = document.getElementById('painelTexto');
    const pVideo = document.getElementById('painelVideo');
    const pExercicios = document.getElementById('painelExercicios');

    function resetAbas() {
        if(tabTexto) {
            tabTexto.style.color = 'var(--cinza-texto)';
            tabTexto.style.borderBottomColor = 'transparent';
            tabVideo.style.color = 'var(--cinza-texto)';
            tabVideo.style.borderBottomColor = 'transparent';
            tabExercicios.style.color = 'var(--cinza-texto)';
            tabExercicios.style.borderBottomColor = 'transparent';
            
            pTexto.style.display = 'none';
            pVideo.style.display = 'none';
            pExercicios.style.display = 'none';
        }
    }

    if (tabTexto) {
        tabTexto.addEventListener('click', () => {
            resetAbas();
            tabTexto.style.color = 'var(--roxo-base)';
            tabTexto.style.borderBottomColor = 'var(--roxo-vivo)';
            pTexto.style.display = 'block';
        });
        tabVideo.addEventListener('click', () => {
            resetAbas();
            tabVideo.style.color = 'var(--roxo-base)';
            tabVideo.style.borderBottomColor = 'var(--roxo-vivo)';
            pVideo.style.display = 'block';
        });
        tabExercicios.addEventListener('click', () => {
            resetAbas();
            tabExercicios.style.color = 'var(--roxo-base)';
            tabExercicios.style.borderBottomColor = 'var(--roxo-vivo)';
            pExercicios.style.display = 'block';
        });
    }

    // Sistema interativo de clique nos cards de alternativas
    const opcoes = document.querySelectorAll('.opcao-radio-card');
    opcoes.forEach(card => {
        card.addEventListener('click', () => {
            const radio = card.querySelector('input[type="radio"]');
            if(radio) {
                radio.checked = true;
                
                // Remove estilos anteriores do bloco da questão
                const parent = card.parentElement;
                parent.querySelectorAll('.opcao-radio-card').forEach(c => {
                    c.style.borderColor = 'var(--borda)';
                    c.style.background = '#fff';
                });

                if(card.classList.contains('correct')) {
                    card.style.borderColor = '#16a34a';
                    card.style.background = '#dcfce7';
                } else {
                    card.style.borderColor = '#dc2626';
                    card.style.background = '#fee2e2';
                }
            }
        });
    });
});