document.addEventListener("DOMContentLoaded", () => {
    document.getElementById("entrar").addEventListener("click", login);
});

async function login(){
    var email = document.getElementById("email").value;
    var senha = document.getElementById("senha").value;

    if(!email || !senha){
        alert("Preencha todos os campos!");
        return;
    }

    const fd = new FormData();
    fd.append("email", email);
    fd.append("senha", senha);

    try {
        const retorno = await fetch("../../app/controllers/usuario/php/login.php", {
            method: "POST",
            body: fd
        });

        // Tenta ler como JSON direto
        const resposta = await retorno.json();
        console.log("RESPOSTA:", resposta);

        if(resposta.status == "ok"){
            // Redireciona baseado no tipo de perfil (opcional, mas útil)
            window.location.href = "usuario_painel.html"; 
        } else {
            alert(resposta.mensagem);
        }
    } catch (error) {
        console.error("Erro na requisição:", error);
        alert("Erro ao conectar com o servidor.");
    }
}