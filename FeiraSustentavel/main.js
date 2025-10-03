const btnCadastrarFamilia = document.getElementById('btn-cadastrar-familia');
const btnRegistrarDoacao = document.getElementById('btn-registrar-doacao');
const modalContainer = document.getElementById('modal-container');
const fecharModal = document.getElementById('fechar-modal');
const conteudoModal = document.getElementById('conteudo-modal');

function abrirModal(conteudo) {
    conteudoModal.innerHTML = conteudo;
    modalContainer.style.display = 'flex';
}

function fecharModalHandler() {
    modalContainer.style.display = 'none';
}

function formularioFamilia() {
    return `
        <h2>Cadastrar Família</h2>
        <form id="form-familia">
            <div class="formulario-grupo">
                <label for="nome-familia">Nome da Família:</label>
                <input type="text" id="nome-familia" required>
            </div>
            <div class="formulario-grupo">
                <label for="telefone">Telefone para Contato:</label>
                <input type="tel" id="telefone" required>
            </div>
            <div class="formulario-grupo">
                <label for="endereco">Endereço:</label>
                <input type="text" id="endereco" required>
            </div>
            <div class="formulario-grupo">
                <label for="membros">Número de Membros:</label>
                <input type="number" id="membros" min="1" required>
            </div>
            <button type="submit" class="botao-enviar">Enviar Cadastro</button>
        </form>
    `;
}

function formularioDoacao() {
    return `
        <h2>Registrar Doação</h2>
        <form id="form-doacao">
            <div class="formulario-grupo">
                <label for="tipo-alimento">Tipo de Alimento:</label>
                <input type="text" id="tipo-alimento" required>
            </div>
            <div class="formulario-grupo">
                <label for="quantidade">Quantidade (kg ou unid.):</label>
                <input type="text" id="quantidade" required>
            </div>
            <div class="formulario-grupo">
                <label for="validade">Data de Validade:</label>
                <input type="date" id="validade" required>
            </div>
            <div class="formulario-grupo">
                <label for="local-retirada">Local de Retirada:</label>
                <input type="text" id="local-retirada" required>
            </div>
            <button type="submit" class="botao-enviar">Registrar Doação</button>
        </form>
    `;
}

btnCadastrarFamilia.addEventListener('click', () => {
    abrirModal(formularioFamilia());
    
    document.getElementById('form-familia')?.addEventListener('submit', (e) => {
        e.preventDefault();
        alert('Cadastro de família enviado com sucesso!');
        fecharModalHandler();
    });
});

btnRegistrarDoacao.addEventListener('click', () => {
    abrirModal(formularioDoacao());
    
    document.getElementById('form-doacao')?.addEventListener('submit', (e) => {
        e.preventDefault();
        alert('Doação registrada com sucesso!');
        fecharModalHandler();
    });
});

fecharModal.addEventListener('click', fecharModalHandler);

modalContainer.addEventListener('click', (e) => {
    if (e.target === modalContainer) {
        fecharModalHandler();
    }
});