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
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    
    return `
        <h2>Cadastrar Família</h2>
        <form id="form-familia" method="POST" action="familias.php?action=store">
            <input type="hidden" name="csrf" value="${csrfToken}">
            
            <div class="formulario-grupo">
                <label for="nome">Nome da Família *</label>
                <input type="text" id="nome" name="nome" required>
            </div>
            
            <div class="formulario-grupo">
                <label for="email">E-mail *</label>
                <input type="email" id="email" name="email" required>
            </div>
            
            <div class="formulario-grupo">
                <label for="telefone">Telefone</label>
                <input type="text" id="telefone" name="telefone">
            </div>
            
            <div class="formulario-grupo">
                <label for="endereco">Endereço</label>
                <textarea id="endereco" name="endereco" rows="3"></textarea>
            </div>
            
            <div class="formulario-grupo">
                <label for="num_membros">Número de Membros *</label>
                <input type="number" id="num_membros" name="num_membros" min="1" required>
            </div>
            
            <div class="formulario-grupo">
                <label for="situacao">Situação *</label>
                <select id="situacao" name="situacao" required>
                    <option value="">— Selecione —</option>
                    <option value="baixa_renda">Baixa renda</option>
                    <option value="vulnerabilidade">Vulnerabilidade</option>
                    <option value="outra">Outra</option>
                </select>
            </div>
            
            <div class="formulario-grupo">
                <label for="renda_mensal">Renda Mensal (opcional)</label>
                <input type="number" id="renda_mensal" name="renda_mensal" step="0.01" min="0">
            </div>
            
            <button type="submit" class="botao-enviar">Cadastrar Família</button>
        </form>
        
        <div style="margin-top: 1rem; text-align: center;">
            <a href="familias.php?action=list" style="color: #4CAF50; text-decoration: none;">
                → Gerenciar famílias cadastradas
            </a>
        </div>
    `;
}

function formularioDoacao() {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    
    return `
        <h2>Registrar Doação</h2>
        <form id="form-doacao" method="POST" action="doacoes.php?action=store">
            <input type="hidden" name="csrf" value="${csrfToken}">
            
            <div class="formulario-grupo">
                <label for="doador_nome">Nome do Doador *</label>
                <input type="text" id="doador_nome" name="doador_nome" required>
            </div>
            
            <div class="formulario-grupo">
                <label for="doador_email">E-mail do Doador *</label>
                <input type="email" id="doador_email" name="doador_email" required>
            </div>
            
            <div class="formulario-grupo">
                <label for="data">Data da Doação *</label>
                <input type="date" id="data" name="data" value="${new Date().toISOString().split('T')[0]}" required>
            </div>
            
            <div class="formulario-grupo">
                <label for="observacoes">Observações (local de retirada)</label>
                <input type="text" id="observacoes" name="observacoes">
            </div>
            
            <div class="formulario-grupo">
                <label for="alimento_nome">Nome do Alimento *</label>
                <input type="text" id="alimento_nome" name="alimento_nome" required>
            </div>
            
            <div class="formulario-grupo">
                <label for="quantidade">Quantidade *</label>
                <input type="number" id="quantidade" name="quantidade" min="1" required>
            </div>
            
            <div class="formulario-grupo">
                <label for="validade">Data de Validade</label>
                <input type="date" id="validade" name="validade">
            </div>
            
            <div class="formulario-grupo">
                <label for="categoria">Categoria *</label>
                <select id="categoria" name="categoria" required>
                    <option value="">— Selecione —</option>
                    <option value="fruta">Fruta</option>
                    <option value="verdura">Verdura</option>
                    <option value="legume">Legume</option>
                    <option value="outro">Outro</option>
                </select>
            </div>
            
            <button type="submit" class="botao-enviar">Registrar Doação</button>
        </form>
        
        <div style="margin-top: 1rem; text-align: center;">
            <a href="doacoes.php?action=list" style="color: #4CAF50; text-decoration: none;">
                → Gerenciar doações cadastradas
            </a>
        </div>
    `;
}

btnCadastrarFamilia.addEventListener('click', () => {
    abrirModal(formularioFamilia());
});

btnRegistrarDoacao.addEventListener('click', () => {
    abrirModal(formularioDoacao());
});

fecharModal.addEventListener('click', fecharModalHandler);

modalContainer.addEventListener('click', (e) => {
    if (e.target === modalContainer) {
        fecharModalHandler();
    }
});

// Fechar modal com ESC
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        fecharModalHandler();
    }
});