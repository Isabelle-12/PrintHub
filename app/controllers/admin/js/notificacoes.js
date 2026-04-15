
async function carregarPedidosExpirados() {
    const container = document.getElementById('pedidos-atrasados');
    if (!container) return;
    container.innerHTML = '<p class="text-muted">Carregando...</p>';

    const resposta = await fetch('../app/controllers/admin/listar_pedidos_expirados.php');
    const retorno = await resposta.json();

    if (retorno.status === 'ok' && retorno.data.length > 0) {
        let itens = '';
        for (let i = 0; i < retorno.data.length; i++) {
            let p = retorno.data[i];
            itens += `
                <div class="d-flex justify-content-between align-items-center border rounded p-2 mb-2">
                    <div>
                        <strong>Pedido #${p.id}</strong> &mdash; ${p.maker_nome}
                        <br><small class="text-muted"> Cliente: ${p.cliente_nome} (${p.cliente_email})
                         &bull; Maker: ${p.maker_nome} (${p.maker_email})
                         &bull; Prazo: ${p.prazo_pedido} &bull; Status: ${p.status}       
                         </small>
                    </div>  
                    <button class="btn btn-sm btn-primary" onclick="notificarAtraso(${p.id})">
                        Notificar
                    </button>
                </div>
            `;
        }
        container.innerHTML = itens;
    } else {
        container.innerHTML = '<p class="text-success">Nenhum pedido com prazo expirado.</p>';
    }
}

async function notificarAtraso(pedidoId) {
    const resposta = await fetch('../app/controllers/admin/enviar_notificacao_atraso.php', {
        method: 'POST',
        body: new URLSearchParams({ pedido_id: pedidoId })
    });
    const retorno = await resposta.json();

    if (retorno.status === 'ok') {
        alert('Notificações enviadas para cliente e fabricante.');
        carregarNotificacoesEnviadas();
    } else {
        alert(retorno.mensagem);
    }
}


// document.getElementById('form-manutencao').addEventListener('submit', async function(event) {
//     event.preventDefault();

//         const titulo      = document.getElementById('titulo').value;
//         const mensagem    = document.getElementById('mensagem').value;
//         const data_inicio = document.getElementById('data_inicio').value;
//         const data_fim    = document.getElementById('data_fim').value;

//         const resposta = await fetch('../app/controllers/admin/agendar_manutencao.php', {
//             method: 'POST',
//             body:   new URLSearchParams({
//                 titulo:      titulo,
//                 mensagem:    mensagem,
//                 data_inicio: data_inicio,
//                 data_fim:    data_fim
//             })
//         });
//         const retorno = await resposta.json();

//         alert(retorno.mensagem);

//     if (retorno.status === 'ok') {
//         document.getElementById('form-manutencao').reset();
//     }
// });


async function carregarNotificacoesEnviadas() {
    const container = document.getElementById('notificacoes-enviadas');
    if (!container) return;

    container.innerHTML = '<p class="text-muted">Carregando...</p>';

    const resposta = await fetch('../app/controllers/admin/listar_notificacoes_enviadas.php');
    const retorno = await resposta.json();

    if (retorno.status === 'ok' && retorno.data.length > 0) {
        let itens = '';
        for (let i = 0; i < retorno.data.length; i++) {
            let n = retorno.data[i];
            let btnRetratar = n.retratada == 0
                ? '<button class="btn btn-sm btn-warning" onclick="retratar(' + n.id + ')">Retratar</button>'
                : '<span class="badge bg-secondary">Retratada</span>';

            itens += `
                <div class="d-flex justify-content-between align-items-center border rounded p-2 mb-2">
                    <div>
                        <strong>${n.titulo || n.tipo}</strong>
                        <br><small class="text-muted">${n.data_envio} &bull; ${n.email_destino || '—'}</small>
                        <br><small>${n.mensagem}</small>
                    </div>
                    <div>${btnRetratar}</div>
                </div>
            `;
        }
        container.innerHTML = itens;
    } else {
        container.innerHTML = '<p class="text-muted">Nenhuma notificação enviada.</p>';
    }
}

async function retratar(id) {
    if (!confirm('Confirma a retratação desta notificação?')) return;

    const resposta = await fetch('../app/controllers/admin/retratar_notificacao.php', {
        method: 'POST',
        body: new URLSearchParams({ id: id })
    });
    const retorno = await resposta.json();

    alert(retorno.mensagem);

    if (retorno.status === 'ok') {
        carregarNotificacoesEnviadas();
    }
}


async function carregarAnunciosGlobais() {
    const container = document.getElementById('banner-anuncios');
    if (!container) return;

    const resposta = await fetch('../app/controllers/admin/listar_anuncios_ativos.php');
    const retorno = await resposta.json();

    if (retorno.status === 'ok' && retorno.data.length > 0) {
        let a = retorno.data[0];
        document.getElementById('banner-titulo').textContent = a.titulo;
        document.getElementById('banner-mensagem').textContent = a.mensagem;
        document.getElementById('banner-periodo').textContent = a.data_inicio + ' até ' + a.data_fim;
        container.classList.remove('d-none');
    }
}

async function carregarMinhasNotificacoes() {
    const container = document.getElementById('minhas-notificacoes');
    if (!container) return;

    container.innerHTML = '<p class="text-muted">Carregando...</p>';

    const resposta = await fetch('../app/controllers/admin/carregar_notif.php');
    const retorno = await resposta.json();

    if (retorno.status === 'ok' && retorno.data.length > 0) {
        let itens = '';
        for (let i = 0; i < retorno.data.length; i++) {
            let n = retorno.data[i];
            itens += `
                <div class="alert alert-warning mb-2" role="alert">
                    <strong>${n.titulo || n.tipo}</strong>
                    <br><small class="text-muted">${n.data_envio}</small>
                    <br>${n.mensagem}
                </div>
            `;
        }
        container.innerHTML = itens;
        console.log("ADMIN carregando");
    } else {
        container.innerHTML = '<p class="text-success">Nenhuma notificação pendente.</p>';
    }
}
async function carregarMinhasNotificacoesUsuario() {
    // 1. Verifica se o container existe na tela
    const container = document.getElementById('minhas-notificacoes');
    if (!container) {
        console.warn("Aviso: Elemento 'minhas-notificacoes' não encontrado no HTML.");
        return;
    }

    try {
        const resposta = await fetch('../app/controllers/usuario/php/carregar_minhas_notificacoes.php');
        const retorno = await resposta.json();

        console.log("Dados recebidos do PHP:", retorno);

        // 2. Verifica se o status é 'ok' e se o array 'data' tem conteúdo
        if (retorno.status === 'ok' && Array.isArray(retorno.data) && retorno.data.length > 0) {
            let listaHTML = '';

            retorno.data.forEach(notificacao => {
                // Define uma cor diferente se for redefinição de senha
                const corAlerta = notificacao.titulo.includes('Redefinição') ? 'alert-danger' : 'alert-warning';

                listaHTML += `
                        <div class="alert ${corAlerta} mb-2 shadow-sm" role="alert" style="border-left: 5px solid darkred;">
                            <div class="d-flex justify-content-between">
                                <strong><i class="bi bi-exclamation-triangle-fill me-2"></i>${notificacao.titulo}</strong>
                                <small class="text-muted">${notificacao.data_envio}</small>
                            </div>
                            <p class="mb-0 mt-1" style="font-size: 0.9rem;">${notificacao.mensagem}</p>
                        </div>
                    `;
            });

            // 3. Substitui o texto "Nenhuma notificação" pela lista real
            container.innerHTML = listaHTML;
            console.log("USUARIO carregando");
        } else {
            container.innerHTML = '<p class="text-muted">Nenhuma notificação encontrada para sua conta.</p>';
        }

    } catch (erro) {
        console.error("Erro ao processar notificações:", erro);
        container.innerHTML = '<p class="text-danger">Erro ao carregar notificações.</p>';
    }
}


async function carregarPrazoAtual() {
    const input = document.getElementById('input-dias-prazo');
    if (!input) return;

    const resposta = await fetch('../app/controllers/admin/config_prazo.php', {
        method: 'POST',
        body: new URLSearchParams({ apenas_consulta: 1 })
    });
    const retorno = await resposta.json();

    if (retorno.status === 'ok') {
        input.value = retorno.data.dias_prazo;
    }

}

async function salvarPrazo() {
    const dias = document.getElementById('input-dias-prazo').value;
    const msg = document.getElementById('msg-prazo');

    const resposta = await fetch('../app/controllers/admin/config_prazo.php', {
        method: 'POST',
        body: new URLSearchParams({ dias_prazo: dias })
    });
    const retorno = await resposta.json();

    msg.innerHTML = `<div class="alert ${retorno.status === 'ok' ? 'alert-success' : 'alert-danger'} py-1">${retorno.mensagem}</div>`;
}

async function verificarUsuarioTipoNotif() {
    const resp = await fetch("../config/verificar.php");
    const dados = await resp.json();

    if (!dados.logado) {
        alert("Você precisa estar logado!");
        window.location.href = "index.php?rota=login";
        return;
    }

    // 👇 só decide qual função chamar
    if (dados.tipos === "ADMIN") {
        carregarMinhasNotificacoes();
    } else {
        carregarMinhasNotificacoesUsuario();
    }
}

// DOM CONTENT

document.addEventListener('DOMContentLoaded', function () {
    verificarUsuarioTipoNotif();
    carregarPedidosExpirados();
    carregarNotificacoesEnviadas();
    carregarAnunciosGlobais();


    const formManutencao = document.getElementById('form-manutencao');
    if (formManutencao) {
        formManutencao.addEventListener('submit', async function (event) {
            event.preventDefault();

            const titulo = document.getElementById('titulo').value;
            const mensagem = document.getElementById('mensagem').value;
            const data_inicio = document.getElementById('data_inicio').value;
            const data_fim = document.getElementById('data_fim').value;

            const resposta = await fetch('../app/controllers/admin/agendar_manutencao.php', {
                method: 'POST',
                body: new URLSearchParams({
                    titulo: titulo,
                    mensagem: mensagem,
                    data_inicio: data_inicio,
                    data_fim: data_fim
                })
            });
            const retorno = await resposta.json();

            alert(retorno.mensagem);

            if (retorno.status === 'ok') {
                formManutencao.reset();
            }
        });
    }


    // // DOMContentLoaded (só inicialização)
    // document.addEventListener('DOMContentLoaded', function() {
    // });


    // Delegação de evento (global, só uma vez)
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('#btn-salvar-prazo');
        if (btn) {
            salvarPrazo();
        }
    });
    // // No final do seu notificacoes.js
    // window.addEventListener('load', function () {
    //     // Dá um fôlego de 200ms para outros scripts terminarem de "limpar" a tela
    //     setTimeout(carregarMinhasNotificacoesUsuario, 200);
    // });
});

