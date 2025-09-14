<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adicionar Item - Ferramenta de Auditoria</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <div class="container">
        <nav class="main-nav">
            <ul class="nav-list">
                <li><a href="index.php" class="nav-link">Início</a></li>
                <li><a href="checklist.php" class="nav-link">Checklist</a></li>
                <li><a href="pages/relatorios.php" class="nav-link">Relatórios</a></li>
                <li><a href="pages/envio_comunicacao.php" class="nav-link">Envio e Comunicação</a></li>
            </ul>
        </nav>
    </div>

    <div class="form-container">
        <div class="page-header">
            <h2 class="page-title">Adicionar Item ao Checklist</h2>
            <a href="checklist.php" class="btn-secondary">Voltar ao Checklist</a>
        </div>

        <form id="checklist-form" class="checklist-form">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="descricao">Descrição</label>
                    <input type="text" id="descricao" class="form-input" placeholder="Descrição da verificação" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="resultado">Resultado</label>
                    <select id="resultado" class="form-input" required>
                        <option value="">Selecione...</option>
                        <option value="Conforme">Conforme</option>
                        <option value="Não Conforme">Não Conforme</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="responsavel">Responsável</label>
                    <input type="text" id="responsavel" class="form-input" placeholder="Nome do responsável" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="classificacao">Classificação da NCF</label>
                    <select id="classificacao" class="form-input" required>
                        <option value="">Selecione...</option>
                        <option value="Crítica">Crítica</option>
                        <option value="Maior">Maior</option>
                        <option value="Menor">Menor</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="situacao">Situação da NCF</label>
                    <select id="situacao" class="form-input" required>
                        <option value="">Selecione...</option>
                        <option value="Pendente">Pendente</option>
                        <option value="Em Andamento">Em Andamento</option>
                        <option value="Concluído">Concluído</option>
                        <option value="Vencido">Vencido</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="dataIdentificacao">Data de Identificação</label>
                    <input type="date" id="dataIdentificacao" class="form-input" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="prazo">Prazo</label>
                    <input type="date" id="prazo" class="form-input" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="dataEscalonamento">Data de Escalonamento</label>
                    <input type="date" id="dataEscalonamento" class="form-input">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="dataConclusao">Data de Conclusão</label>
                    <input type="date" id="dataConclusao" class="form-input">
                </div>

                <div class="form-group">
                    <label class="form-label" for="observacoes">Observações</label>
                    <input type="text" id="observacoes" class="form-input" placeholder="Observações adicionais">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="acaoCorretiva">Ação Corretiva Indicada</label>
                <input type="text" id="acaoCorretiva" class="form-input" placeholder="Descreva a ação corretiva" required>
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