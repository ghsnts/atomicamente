document.addEventListener('DOMContentLoaded', () => {
    // --- CONTROLO DAS ABAS ---
    const tabTexto = document.getElementById('tabTexto');
    const tabVideo = document.getElementById('tabVideo');
    const tabExercicios = document.getElementById('tabExercicios');

    const pTexto = document.getElementById('painelTexto');
    const pVideo = document.getElementById('painelVideo');
    const pExercicios = document.getElementById('painelExercicios');

    function resetAbas() {
        if(tabTexto) {
            [tabTexto, tabVideo, tabExercicios].forEach(tab => {
                tab.style.color = 'var(--cinza-texto)';
                tab.style.borderBottomColor = 'transparent';
            });
            [pTexto, pVideo, pExercicios].forEach(painel => painel.style.display = 'none');
        }
    }

    if (tabTexto) {
        tabTexto.addEventListener('click', () => { resetAbas(); tabTexto.style.color = 'var(--roxo-base)'; tabTexto.style.borderBottomColor = 'var(--roxo-vivo)'; pTexto.style.display = 'block'; });
        tabVideo.addEventListener('click', () => { resetAbas(); tabVideo.style.color = 'var(--roxo-base)'; tabVideo.style.borderBottomColor = 'var(--roxo-vivo)'; pVideo.style.display = 'block'; });
        tabExercicios.addEventListener('click', () => { resetAbas(); tabExercicios.style.color = 'var(--roxo-base)'; tabExercicios.style.borderBottomColor = 'var(--roxo-vivo)'; pExercicios.style.display = 'block'; });
    }

    // --- SUBMISSÃO DE EXERCÍCIOS EM TEMPO REAL (AJAX/FETCH) ---
    const cardsOpcao = document.querySelectorAll('.opcao-radio-card');
    
    cardsOpcao.forEach(card => {
        card.addEventListener('click', async () => {
            const radio = card.querySelector('input[type="radio"]');
            if (!radio || radio.disabled) return; // Evita duplo clique ou responder duas vezes

            radio.checked = true;
            
            const questaoId = card.getAttribute('data-questao');
            const alternativaId = card.getAttribute('data-id');
            const eCorreta = card.classList.contains('correct');

            // Desativa todas as opções desta questão para a aluna não mudar a resposta
            const containerPai = card.closest('.questao-container');
            containerPai.querySelectorAll('input[type="radio"]').forEach(r => r.disabled = true);

            // Aplica o feedback visual imediato
            containerPai.querySelectorAll('.opcao-radio-card').forEach(c => {
                c.style.borderColor = 'var(--borda)';
                c.style.background = '#fff';
            });

            if (eCorreta) {
                card.style.borderColor = '#16a34a';
                card.style.background = '#dcfce7';
            } else {
                card.style.borderColor = '#dc2626';
                card.style.background = '#fee2e2';
                // Mostra discretamente qual era a correta
                const correta = containerPai.querySelector('.correct');
                if(correta) correta.style.borderColor = '#16a34a';
            }

            // Envia a resposta para o servidor em segundo plano
            try {
                await fetch('salvar_resposta.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        questao_id: questaoId,
                        alternativa_id: alternativaId,
                        foi_correta: eCorreta
                    })
                });
            } catch (error) {
                console.error("Erro ao sincronizar resposta com o MySQL:", error);
            }
        });
    });
});
