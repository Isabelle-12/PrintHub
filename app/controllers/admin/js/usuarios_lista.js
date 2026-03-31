document.addEventListener("DOMContentLoaded", () => {
    buscar();
    buscarFabricantes();
    buscarAdministrador();
});

//BUSCAR FUNÇÕES VISUALIZAR TODOS OS TIPOS DE USUARIO
async function buscar() {
    const retorno = await fetch("../app/controllers/admin/usuario_get.php");
    const resposta = await retorno.json();

    
    if (resposta.status === "ok") {
        preencherTabela(resposta.data);
    } else {
        document.getElementById("listaUsuarios").innerHTML = '<tr><td colspan="4">Nenhum usuário encontrado</td></tr>';
    }
}

async function buscarFabricantes() {
    const retorno = await fetch("../app/controllers/admin/fabricante_get.php");
    const resposta = await retorno.json();

    if (resposta.status === "ok") {
        preencherTabelaFabricante(resposta.data);
    } else {
        document.getElementById("listaFabricantes").innerHTML = '<tr><td colspan="4">Nenhum fabricante encontrado</td></tr>';
    }
    
}

async function buscarAdministrador() {
    const retorno = await fetch("../app/controllers/admin/administrador_get.php");
    const resposta = await retorno.json();

    if (resposta.status === "ok") {
        preencherTabelaAdministrador(resposta.data);
    } else {
        document.getElementById("listaAdinistradores").innerHTML = '<tr><td colspan="4">Nenhum adimistrador encontrado</td></tr>';
    }
    
}


//FUNÇÃO PARA EXCLUIR POR ID
async function excluirUsuario(id) {
    const confirmacao = confirm("Deseja realmente excluir este usuário?");
    if (!confirmacao) return;

    const response = await fetch(`../app/controllers/admin/usuario_excluir.php?id=${id}`);
    const resultado = await response.json();
    alert(resultado.mensagem);

    // Atualiza todas as tabelas
    buscar();
    buscarFabricantes();
    buscarAdministrador();
}


//PREENCHER TABELAS MANIPULAÇÃO DE FUNÇÕES
function preencherTabela(lista) {
    let html = "";

    lista.forEach(usuario => {
        html += `
            <tr>
                <td>${usuario.id}</td>
                <td>${usuario.nome}</td>
                <td>${usuario.email}</td>
                <td>
                    <button class="btn btn-primary btn-sm" onclick='verPerfil(${JSON.stringify(usuario)})' data-bs-toggle="modal" data-bs-target="#modalPerfil">Ver</button>
                    <button class="btn btn-warning btn-sm">Editar</button>
                    <button class="btn btn-danger btn-sm" onclick='excluirUsuario (${JSON.stringify(usuario.id)})'>Excluir</button>
                </td>
            </tr>
        `;
    });

    document.getElementById("listaUsuarios").innerHTML = html;
}

function preencherTabelaFabricante(lista) {
    let html = "";

    lista.forEach(fabricante => {
        html += `
            <tr>
                <td>${fabricante.id}</td>
                <td>${fabricante.nome}</td>
                <td>${fabricante.cnpj }</td>
                <td>
                    <button class="btn btn-primary btn-sm" onclick='verPerfilFabricante(${JSON.stringify(fabricante)})' data-bs-toggle="modal" data-bs-target="#modalPerfil">Ver</button>
                    <button class="btn btn-warning btn-sm">Editar</button>
                    <button class="btn btn-danger btn-sm" onclick='excluirUsuario (${JSON.stringify(fabricante.id)})'>Excluir</button>
                </td>
                </td>
            </tr>
        `;
    });

    document.getElementById("listaFabricantes").innerHTML = html;
}


function preencherTabelaAdministrador(lista) {
    let html = "";

    lista.forEach(administrador => {
        html += `
            <tr>
                <td>${administrador.id}</td>
                <td>${administrador.nome}</td>
                <td>${administrador.email }</td>
                <td>
                    <button class="btn btn-primary btn-sm" onclick='verPerfilAdministrador(${JSON.stringify(administrador)})' data-bs-toggle="modal" data-bs-target="#modalPerfil">Ver</button>
                    <button class="btn btn-warning btn-sm">Editar</button>
                    <button class="btn btn-danger btn-sm" onclick='excluirUsuario (${JSON.stringify(administrador.id)})'>Excluir</button>
                </td>
            </tr>
        `;
    });

    document.getElementById("listaAdinistradores").innerHTML = html;
}

//VER PERFIS VERIFICAR TODAS AS INFORMAÇÕES CADASTRADAS DE CADA TIPO DE USUARIO
function verPerfil(usuario) {
    document.getElementById("conteudoModal").innerHTML = `
        <p><strong>Nome:</strong> ${usuario.nome}</p>
        <p><strong>Email:</strong> ${usuario.email}</p>
        <p><strong>Senha:</strong> ${usuario.senha}</p>
        <p><strong>Telefone:</strong> ${usuario.telefone}</p>
        <p><strong>CEP:</strong> ${usuario.cep}</p>
        <p><strong>Cidade:</strong> ${usuario.cidade}</p>
        <p><strong>Estado:</strong> ${usuario.estado}</p>
        <p><strong>Endereço:</strong> ${usuario.endereco}</p>
        <p><strong>Status:</strong> ${usuario.status}</p>
        <p><strong>Data de Cadastro:</strong> ${usuario.data_cadastro}</p>
    `;
}

function verPerfilFabricante(fabricante) {
    document.getElementById("conteudoModal").innerHTML = `
        <p><strong>Nome:</strong> ${fabricante.nome}</p>
        <p><strong>Email:</strong> ${fabricante.email}</p>
        <p><strong>CNPJ:</strong> ${fabricante.cnpj}</p>
        <p><strong>Telefone Comercial:</strong> ${fabricante.telefone_comercial}</p>
        <p><strong>Endereço da Empresa:</strong> ${fabricante.endereco_empresa}</p>
        <p><strong>Data de Aprovação:</strong> ${fabricante.data_aprovacao}</p>
    `;
}

function verPerfilAdministrador(administrador) {
    document.getElementById("conteudoModal").innerHTML = `
        <p><strong>Nome:</strong> ${administrador.nome}</p>
        <p><strong>Email:</strong> ${administrador.email}</p>
        <p><strong>Senha:</strong> ${administrador.senha}</p>
        <p><strong>Telefone:</strong> ${administrador.telefone}</p>
        <p><strong>CEP:</strong> ${administrador.cep}</p>
        <p><strong>Cidade:</strong> ${administrador.cidade}</p>
        <p><strong>Estado:</strong> ${administrador.estado}</p>
        <p><strong>Endereço:</strong> ${administrador.endereco}</p>
        <p><strong>Status:</strong> ${administrador.status}</p>
        <p><strong>Data de Cadastro:</strong> ${administrador.data_cadastro}</p>
    `;
}