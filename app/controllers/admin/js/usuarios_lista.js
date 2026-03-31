document.addEventListener("DOMContentLoaded", () => {
    buscar();
});

async function buscar() {
    const retorno = await fetch("../app/controllers/admin/usuario_get.php");
    const resposta = await retorno.json();

    if (resposta.status === "ok") {
        preencherTabela(resposta.data);
    } else {
        document.getElementById("listaUsuarios").innerHTML = '<tr><td colspan="4">Nenhum usuário encontrado</td></tr>';
    }
}

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
                    <button class="btn btn-danger btn-sm">Excluir</button>
                </td>
            </tr>
        `;
    });

    document.getElementById("listaUsuarios").innerHTML = html;
}

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
