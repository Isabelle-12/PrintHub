async function verif() {
    const resp = await fetch("/config/verificar.php");
    const dados = await resp.json();

    if (!dados.logado) {
        alert("Você precisa estar logado!");
        window.location.href = "/index.php?rota=login";
    } else {
        console.log("Usuário logado:", dados.email);
    }
}

// nao sei se deixo no dom ja que nao está no header, mas deixarei
window.addEventListener('DOMContentLoaded', () => {
    verif();

});








