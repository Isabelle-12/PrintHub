document.addEventListener("DOMContentLoaded", async () => {
    await verif();
    carregarFabricante();

    document.getElementById("formEditarFabricante").addEventListener("submit", function(e) {
        e.preventDefault();
        salvarAlteracoesFabricante();
    });
});

async function carregarFabricante() {
    const urlParams = new URLSearchParams(window.location.search);
    const id = urlParams.get("id");

    if (!id) {
        alert("ID do usuário não fornecido.");
        window.location.href = "index.php?rota=admin-usuarios";
        return;
    }
    
    try {
       const retorno = await fetch(`../app/controllers/admin/fabricante_get.php?id=${id}&t=${Date.now()}`);
        const resposta = await retorno.json(); 

        
        if (resposta.status === "ok") {
            const fab = resposta.data[0];

            document.getElementById("id").value = fab.id;
            document.getElementById("usuario_id").value = fab.usuario_id;
            document.getElementById("nome").value = fab.nome || '';
            document.getElementById("email").value = fab.email || '';
            document.getElementById("cnpj").value = fab.cnpj || '';
            document.getElementById("telefone_comercial").value = fab.telefone_comercial || '';
            document.getElementById("endereco_empresa").value = fab.endereco_empresa || '';
        } else {
            alert("Erro ao carregar usuário: " + resposta.mensagem);
        }
    } catch (err) {
        console.error(err);
        alert("Erro ao carregar dados do fabricante.");
    }
}

function salvarAlteracoesFabricante() {


    const fd = new FormData();
    
    fd.append("id", document.getElementById("id").value);
    fd.append("usuario_id", document.getElementById("usuario_id").value);
    fd.append("nome", document.getElementById("nome").value);
    fd.append("email", document.getElementById("email").value);
    fd.append("cnpj", document.getElementById("cnpj").value);
    fd.append("telefone_comercial", document.getElementById("telefone_comercial").value);
    fd.append("endereco_empresa", document.getElementById("endereco_empresa").value);
    fd.append("nova_senha", nova_senha);

    const email = document.getElementById("email").value;
    if (!email.includes("@")) {
        alert("E-mail inválido.");
        return;
    }

    fetch("../app/controllers/admin/editar_fabricante.php", { method: "POST", body: fd, credentials: 'same-origin'})
        .then(resp => resp.json())
        .then(res => {
            if (res.status === "ok") {
                alert("Fabricante atualizado com sucesso!");
                window.location.href = "index.php?rota=admin-usuarios";
            } else {
                alert("Erro ao atualizar usuário: " + res.mensagem);
            }
        })
        .catch(err => {
            console.error("Erro detectado:", err);
            alert("Erro ao carregar dados");
        });
}