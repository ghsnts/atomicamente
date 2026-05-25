document.addEventListener('DOMContentLoaded', () => {
    const phInput = document.getElementById('ph');
    const valorPh = document.getElementById('valorPh');
    const resultadoPh = document.getElementById('resultadoPh');

    if (phInput && valorPh && resultadoPh) {
        phInput.addEventListener('input', (e) => {
            const v = parseFloat(e.target.value).toFixed(1);
            valorPh.textContent = v;
            
            if (v < 7.0) {
                resultadoPh.textContent = `pH ${v} — Solução Ácida (Alta concentração de iões Hidrónio H₃O⁺).`;
                resultadoPh.style.color = '#ef4444';
                resultadoPh.style.backgroundColor = '#fef2f2';
            } else if (v == 7.0) {
                resultadoPh.textContent = `pH ${v} — Solução Neutra (Equilíbrio Perfeito de Autoionização).`;
                resultadoPh.style.color = '#10b981';
                resultadoPh.style.backgroundColor = '#f0fdf4';
            } else {
                resultadoPh.textContent = `pH ${v} — Solução Básica/Alcalina (Excesso de iões Hidróxido OH⁻).`;
                resultadoPh.style.color = '#3b82f6';
                resultadoPh.style.backgroundColor = '#eff6ff';
            }
        });
    }
});