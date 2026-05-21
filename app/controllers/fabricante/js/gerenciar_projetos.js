let todosPedidos  = [];
let filtroAtual   = 'TODOS';
let pedidoAtualId = null;

const STATUS_LABEL = {
    AGUARDANDO_CONFIRMACAO: 'Aguardando',
    ARQUIVO_VALIDADO:       'Arquivo Validado',
    ACEITO:                 'Aceito',
    EM_PRODUCAO:            'Em Produção',
    CONCLUIDO:              'Concluído',
    ENTREGUE:               'Entregue',
    NEGADO:                 'Negado',
    CANCELADO:              'Cancelado'
};

const STATUS_CLASS = {
    AGUARDANDO_CONFIRMACAO: 'st-aguardando',
    ARQUIVO_VALIDADO:       'st-aceito',
    ACEITO:                 'st-aceito',
    EM_PRODUCAO:            'st-producao',
    CONCLUIDO:              'st-concluido',
    ENTREGUE:               'st-entregue',
    NEGADO:                 'st-negado',
    CANCELADO:              'st-cancelado'
};

// ── Transições permitidas por status atual ───────────────────────────────────
// Cada status só pode avançar para os status listados abaixo
const TRANSICOES = {
    AGUARDANDO_CONFIRMACAO: ['ACEITO', 'NEGADO'],
    ARQUIVO_VALIDADO:       ['ACEITO', 'NEGADO'],
    ACEITO:                 ['EM_PRODUCAO', 'NEGADO'],
    EM_PRODUCAO:            ['CONCLUIDO'],
    CONCLUIDO:              ['ENTREGUE'],
    ENTREGUE:               [],   // status final positivo
    NEGADO:                 [],   // status final negativo
    CANCELADO:              []    // status final
};

// ── Filtros ──────────────────────────────────────────────────────────────────
document.querySelectorAll('.btn-filtro').forEach(btn => {
    btn.addEventListener('click', function () {
        document.querySelector('.btn-filtro.ativo')?.classList.remove('ativo');
        this.classList.add('ativo');
        filtroAtual = this.dataset.status;
        renderCards();
    });
});

// ── Carrega todos os pedidos do fabricante ───────────────────────────────────
async function carregarPedidos() {
    const container = document.getElementById('cardsGerenciar');
    container.innerHTML = `<div class="estado-loading"><div class="spinner-custom"></div><span>Carregando pedidos...</span></div>`;

    try {
        const resp  = await fetch('../app/controllers/fabricante/php/listar_todos_pedidos.php');
        const dados = await resp.json();

        if (dados.status === 'nok') {
            container.innerHTML = `<div class="estado-vazio"><i class="bi bi-exclamation-circle"></i><span>${dados.mensagem}</span></div>`;
            return;
        }

        todosPedidos = dados.data || [];
        renderCards();

    } catch (e) {
        console.error(e);
        container.innerHTML = `<div class="estado-vazio"><i class="bi bi-wifi-off"></i><span>Erro de conexão ao carregar os pedidos.</span></div>`;
    }
}

// ── Renderiza os cards de acordo com o filtro ────────────────────────────────
function renderCards() {
    const container = document.getElementById('cardsGerenciar');
    const lista = filtroAtual === 'TODOS'
        ? todosPedidos
        : todosPedidos.filter(p => p.status === filtroAtual);

    if (lista.length === 0) {
        container.innerHTML = `
            <div class="estado-vazio">
                <i class="bi bi-inbox"></i>
                <span>Nenhum pedido encontrado para este filtro.</span>
            </div>`;
        return;
    }

    container.innerHTML = lista.map(pedido => {
        const valor   = parseFloat(pedido.valor_total || 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
        const data    = new Date(pedido.data_solicitacao).toLocaleDateString('pt-BR');
        const stLabel = STATUS_LABEL[pedido.status] || pedido.status;
        const stClass = STATUS_CLASS[pedido.status] || '';

        // Miniatura da foto de capa (se existir)
        const capaHtml = pedido.capa_path
            ? `<img src="../${pedido.capa_path}" alt="Foto de referência"
                    style="width:100%;height:120px;object-fit:cover;border-radius:8px 8px 0 0;display:block;"
                    onerror="this.style.display='none'">`
            : '';

        // Ícone de arquivo 3D
        const temArquivo = pedido.arquivo_3d && pedido.arquivo_3d.trim() !== '';

        return `
        <div class="projeto-card">
            ${capaHtml}
            <div class="card-topo">
                <span class="card-id">Pedido #${pedido.id}</span>
                <span class="badge-status ${stClass}">${stLabel}</span>
            </div>
            <div class="card-conteudo">
                <div class="card-titulo">${pedido.nome_projeto}</div>
                <div class="info-item"><i class="bi bi-person"></i><span><strong>Cliente:</strong> ${pedido.cliente_nome}</span></div>
                <div class="info-item"><i class="bi bi-palette"></i><span><strong>Partes:</strong> ${pedido.total_partes || 0} parte(s)</span></div>
                <div class="info-item"><i class="bi bi-calendar3"></i><span><strong>Solicitado em:</strong> ${data}</span></div>
                ${temArquivo ? `<div class="info-item"><i class="bi bi-file-earmark-code" style="color:var(--rm)"></i><span style="color:var(--rm);font-size:.8rem;">Arquivo 3D anexado</span></div>` : ''}
            </div>
            <div class="card-acoes">
                <button class="btn-acao btn-ver" onclick="abrirModal(${pedido.id})">
                    <i class="bi bi-eye"></i> Ver / Atualizar
                </button>
            </div>
        </div>`;
    }).join('');
}

// ── Abre o modal com detalhes completos ──────────────────────────────────────
async function abrirModal(id) {
    pedidoAtualId = id;

    const pedido = todosPedidos.find(p => p.id == id);
    if (!pedido) return;

    // ── Cabeçalho
    document.getElementById('mg-id').textContent            = `#${pedido.id}`;
    document.getElementById('mg-nome-projeto').textContent  = pedido.nome_projeto;
    document.getElementById('mg-cliente').textContent       = pedido.cliente_nome;
    document.getElementById('mg-cliente-email').textContent = pedido.cliente_email || '—';
    document.getElementById('mg-cliente-tel').textContent   = pedido.cliente_telefone || '—';
    document.getElementById('mg-qtd').textContent           = pedido.quantidade || '1';
    document.getElementById('mg-valor').textContent         = parseFloat(pedido.valor_total || 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
    document.getElementById('mg-endereco').textContent      = pedido.endereco_entrega || '—';
    document.getElementById('mg-descricao').textContent     = pedido.descricao || '—';
    document.getElementById('mg-observacao').value          = '';

    // Prazo
    const prazoEl = document.getElementById('mg-prazo');
    if (pedido.prazo_pedido) {
        const d = new Date(pedido.prazo_pedido);
        prazoEl.textContent = d.toLocaleDateString('pt-BR') + ' ' + d.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
    } else {
        prazoEl.textContent = 'Não definido';
    }

    // Motivo de recusa
    const recusaEl  = document.getElementById('mg-recusa-wrap');
    const recusaMsg = document.getElementById('mg-motivo-recusa');
    if (pedido.motivo_recusa) {
        recusaEl.style.display  = 'block';
        recusaMsg.textContent   = pedido.motivo_recusa;
    } else {
        recusaEl.style.display  = 'none';
    }

    // ── Foto de referência (capa)
    const capaWrap = document.getElementById('mg-capa-wrap');
    const capaImg  = document.getElementById('mg-capa-img');
    if (pedido.capa_path) {
        capaImg.src           = `../${pedido.capa_path}`;
        capaWrap.style.display = 'block';
    } else {
        capaWrap.style.display = 'none';
    }

    // ── Arquivo 3D para download
    const arqWrap = document.getElementById('mg-arquivo-wrap');
    const arqLink = document.getElementById('mg-arquivo-link');
    if (pedido.arquivo_3d && pedido.arquivo_3d.trim() !== '') {
        const nomeArq  = pedido.arquivo_3d.split('/').pop();
        arqLink.href   = `../${pedido.arquivo_3d}`;
        arqLink.download = nomeArq;
        arqLink.querySelector('.mg-arq-nome').textContent   = nomeArq;
        arqLink.querySelector('.mg-arq-format').textContent = pedido.formato || '';
        arqWrap.style.display = 'block';
    } else {
        arqWrap.style.display = 'none';
    }

    // ── Volume / Peso estimado
    const volEl = document.getElementById('mg-volume');
    const pesEl = document.getElementById('mg-peso');
    volEl.textContent = pedido.volume_estimado_cm3 ? `${parseFloat(pedido.volume_estimado_cm3).toFixed(2)} cm³` : '—';
    pesEl.textContent = pedido.peso_estimado_gramas ? `${parseFloat(pedido.peso_estimado_gramas).toFixed(2)} g` : '—';

    // ── Select de status — só opções permitidas para o status atual
    atualizarSelectStatus(pedido.status);

    // ── Partes do pedido
    carregarPartes(id);

    // ── Histórico
    carregarHistorico(id);

    new bootstrap.Modal(document.getElementById('modalGerenciar')).show();
}

// ── Monta o select de status com apenas as transições válidas ────────────────
function atualizarSelectStatus(statusAtual) {
    const select   = document.getElementById('mg-novo-status');
    const proximos = TRANSICOES[statusAtual] || [];
    const secaoStatus = document.getElementById('mg-secao-status');

    // Se não há transições possíveis, esconde a seção de atualização
    if (proximos.length === 0) {
        secaoStatus.style.display = 'none';
        return;
    }
    secaoStatus.style.display = 'block';

    // Limpa e preenche somente com opções válidas
    select.innerHTML = proximos.map(s =>
        `<option value="${s}">${STATUS_LABEL[s] || s}</option>`
    ).join('');

    // Pré-seleciona a primeira opção disponível
    select.value = proximos[0];
}

// ── Carrega as partes do pedido ──────────────────────────────────────────────
async function carregarPartes(id) {
    const el = document.getElementById('mg-partes');
    el.innerHTML = '<p class="text-muted" style="font-size:.85rem">Carregando...</p>';

    try {
        const resp  = await fetch(`../app/controllers/fabricante/php/partes_pedido.php?id=${id}`);
        const dados = await resp.json();

        if (!dados.data || dados.data.length === 0) {
            el.innerHTML = '<p class="text-muted" style="font-size:.85rem">Nenhuma parte cadastrada.</p>';
            return;
        }

        el.innerHTML = dados.data.map(p => `
            <div style="background:var(--roc);border-radius:8px;padding:10px 14px;margin-bottom:8px;border:1px solid var(--cb);">
                <div style="font-weight:700;font-size:.9rem;color:var(--pp);margin-bottom:4px;">
                    <i class="bi bi-layers me-1" style="color:var(--rc)"></i>${p.nome}
                    <span style="font-weight:400;color:var(--pm);font-size:.8rem;margin-left:8px;">× ${p.quantidade}</span>
                </div>
                <div style="display:flex;gap:16px;flex-wrap:wrap;font-size:.82rem;color:var(--pt);">
                    <span><i class="bi bi-palette me-1" style="color:var(--rc)"></i><strong>Material:</strong> ${p.material}</span>
                    <span><i class="bi bi-circle-fill me-1" style="color:var(--rc);font-size:.6rem"></i><strong>Cor:</strong> ${p.cor}</span>
                    ${p.descricao ? `<span style="width:100%;color:var(--pm)">${p.descricao}</span>` : ''}
                </div>
            </div>`).join('');

    } catch (e) {
        el.innerHTML = '<p class="text-muted" style="font-size:.85rem">Erro ao carregar partes.</p>';
    }
}

// ── Carrega histórico de status ──────────────────────────────────────────────
async function carregarHistorico(id) {
    const el = document.getElementById('mg-historico');
    el.innerHTML = '<p class="text-muted" style="font-size:.85rem">Carregando...</p>';

    try {
        const resp  = await fetch(`../app/controllers/fabricante/php/historico_pedido.php?id=${id}`);
        const dados = await resp.json();

        if (!dados.data || dados.data.length === 0) {
            el.innerHTML = '<p class="text-muted" style="font-size:.85rem">Nenhuma atualização registrada ainda.</p>';
            return;
        }

        el.innerHTML = dados.data.map(h => {
            const anterior = h.status_anterior ? `${STATUS_LABEL[h.status_anterior] || h.status_anterior} → ` : '';
            const novo     = STATUS_LABEL[h.status_novo] || h.status_novo;
            const data     = new Date(h.data_hora).toLocaleString('pt-BR');
            const obs      = h.observacao ? `<br><span style="color:var(--pm)">${h.observacao}</span>` : '';
            return `
            <div class="historico-item">
                <div class="hist-dot"></div>
                <div>
                    <div class="hist-info">${anterior}<strong>${novo}</strong>${obs}</div>
                    <div class="hist-data">${data}</div>
                </div>
            </div>`;
        }).join('');

    } catch (e) {
        el.innerHTML = '<p class="text-muted" style="font-size:.85rem">Erro ao carregar histórico.</p>';
    }
}

// ── Salva o novo status ──────────────────────────────────────────────────────
async function salvarStatus() {
    const novoStatus = document.getElementById('mg-novo-status').value;
    const observacao = document.getElementById('mg-observacao').value.trim();
    const btnSalvar  = document.getElementById('btn-salvar-status');

    if (!pedidoAtualId || !novoStatus) return;

    // Valida a transição no frontend também
    const pedido    = todosPedidos.find(p => p.id == pedidoAtualId);
    const permitidos = TRANSICOES[pedido?.status] || [];
    if (!permitidos.includes(novoStatus)) {
        mostrarToast('Transição de status não permitida.', true);
        return;
    }

    btnSalvar.disabled = true;
    btnSalvar.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Salvando...';

    try {
        const resp  = await fetch('../app/controllers/fabricante/php/atualizar_status_pedido.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: pedidoAtualId, status: novoStatus, observacao })
        });
        const dados = await resp.json();

        if (dados.status === 'ok') {
            const idx = todosPedidos.findIndex(p => p.id == pedidoAtualId);
            if (idx >= 0) todosPedidos[idx].status = novoStatus;

            bootstrap.Modal.getInstance(document.getElementById('modalGerenciar')).hide();
            renderCards();
            mostrarToast('Status atualizado com sucesso!', false);
        } else {
            mostrarToast(dados.mensagem || 'Erro ao atualizar.', true);
        }

    } catch (e) {
        mostrarToast('Erro de conexão.', true);
    } finally {
        btnSalvar.disabled = false;
        btnSalvar.innerHTML = '<i class="bi bi-check-lg me-1"></i>Salvar Status';
    }
}

// ── Toast de feedback ────────────────────────────────────────────────────────
function mostrarToast(msg, erro = false) {
    const toast = document.getElementById('toastGerenciar');
    const icon  = document.getElementById('toast-icon');
    document.getElementById('toast-msg').textContent = msg;
    toast.classList.toggle('erro', erro);
    icon.className = erro ? 'bi bi-x-circle-fill' : 'bi bi-check-circle-fill';
    toast.classList.add('show');
    setTimeout(() => toast.classList.remove('show'), 3500);
}

// ── Init ─────────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', carregarPedidos);