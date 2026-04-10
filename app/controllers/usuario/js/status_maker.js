async function verificarStatusMaker() {
            const resp  = await fetch('../config/verificar.php');
            const dados = await resp.json();
            const card  = document.getElementById('card-virar-maker');
            const corpo = document.getElementById('card-maker-corpo');

            if (!card || !corpo) return;

            if (dados.tipos !== 'CLIENTE') return;

            const status = dados.status_fabricante;

            if (status === 'NAO_SOLICITADO' || status === 'REJEITADO') {
                corpo.innerHTML = `
                    <p class="text-muted">Cadastre suas impressoras e materiais e comece a receber pedidos de impressão 3D!</p>
                    ${status === 'REJEITADO'
                        ? '<div class="alert alert-danger py-2 mb-3"><i class="bi bi-x-circle me-1"></i>Sua solicitação anterior foi <strong>recusada</strong>. Você pode tentar novamente.</div>'
                        : ''}
                    <a href="index.php?rota=solicitar-maker" class="btn btn-primary">
                        <i class="bi bi-send me-1"></i> Solicitar ser Maker
                    </a>`;
                card.style.display = 'block';

            } else if (status === 'PENDENTE') {
                corpo.innerHTML = `
                    <div class="alert alert-warning py-2 mb-0">
                        <i class="bi bi-clock me-1"></i>
                        Sua solicitação de Maker está <strong>em análise</strong>.
                        Aguarde a resposta dos administradores.
                    </div>`;
                card.style.display = 'block';

            } else if (status === 'APROVADO') {
                card.style.display = 'none';
            }
        }

        window.addEventListener('DOMContentLoaded', verificarStatusMaker);