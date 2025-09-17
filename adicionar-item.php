<!DOCTYPE html>
<html lang="pt-BR">
    
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Adicionar Item - Ferramenta de Auditoria</title>
        <link rel="stylesheet" href="./styles/add-item.css">
        <link rel="stylesheet" href="./styles/style.css">
    </head>
    <body>
        <div class="container">
            <nav class="main-nav">
                <ul class="nav-list">
                    <li><a href="index.php" class="nav-link">Início</a></li>
                    <li><a href="checklist.php" class="nav-link">Checklist</a></li>
                    <li><a href="pages/relatoriosNc.php" class="nav-link">Relatórios</a></li>
                    <li><a href="nao_conformidades.php" class="nav-link">Não-Conformidades</a></li>
                </ul>
            </nav>
        </div>

        <div class="form-container">
            <div class="page-header">
                <h2 class="page-title">Adicionar Item ao Checklist</h2>
                <a href="checklist.php" class="btn-secondary">Voltar ao Checklist</a>
            </div>

            <form method="POST" action="processar-item.php" id="checklist-form" class="checklist-form">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="descricao">Descrição</label>
                        <input type="text" id="descricao" name="descricao" class="form-input" placeholder="Descrição da verificação" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="resultado">Resultado</label>
                        <select id="resultado" name="resultado" class="form-input" required>
                            <option value="">Selecione...</option>
                            <option value="Sim">Conforme</option>
                            <option value="Não">Não Conforme</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="responsavel">Responsável</label>
                        <input type="text" id="responsavel" name="responsavel" class="form-input" placeholder="Nome do responsável" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="classificacao">Classificação</label>
                        <select id="classificacao" name="classificacao" class="form-input">
                            <option value="">Selecione...</option>
                            <option value="Simples">Simples</option>
                            <option value="Média">Média</option>
                            <option value="Complexa">Complexa</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="situacao">Situação da NCF</label>
                        <select id="situacao" name="situacao" class="form-input">
                            <option value="">Selecione...</option>
                            <option value="Resolvido">Resolvido</option>
                            <option value="Não Resolvido">Não Resolvido</option>
                            <option value="Em Aberto">Em Aberto</option>
                        </select>
                    </div>
                    
                   
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="data_identificacao">Data de Identificação</label>
                        <input type="datetime-local" id="data_identificacao" name="data_identificacao" class="form-input" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="prazo">Prazo</label>
                        <input type="datetime-local" id="prazo" name="prazo" class="form-input">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="data_escalonamento">Data de Escalonamento</label>
                        <input type="datetime-local" id="data_escalonamento" name="data_escalonamento" class="form-input">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="data_conclusao">Data de Conclusão</label>
                        <input type="datetime-local" id="data_conclusao" name="data_conclusao" class="form-input">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="observacoes">Observações</label>
                        <input type="text" id="observacoes" name="observacoes" class="form-input" placeholder="Observações adicionais">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="acao_corretiva_indicada">Ação Corretiva Indicada</label>
                        <input type="text" id="acao_corretiva_indicada" name="acao_corretiva_indicada" class="form-input" placeholder="Descreva a ação corretiva">
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-primary">Adicionar ao Checklist</button>
                    <a href="checklist.php" class="btn-cancel">Cancelar</a>
                </div>
            </form>
        </div>

        <script src="js/app.js"></script>
    </body>
</html>