<?php
session_start();

$host = 'localhost';
$port = '3307'; // Porta correta
$dbname = 'ferramenta_auditoria';
$username = 'root';
$password = 'root';

function validar_datetime($valor) {
    if (empty($valor)) {
        return null;
    }

    $valor = str_replace("T", " ", trim($valor)) . ":00";

    // Verifica se é uma data válida
    $d = DateTime::createFromFormat('Y-m-d H:i:s', $valor);
    if ($d && $d->format('Y-m-d H:i:s') === $valor) {
        return $valor;
    }

    return null; // retorna NULL se inválido
}

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8";
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if (!isset($_GET['id'])) {
        die("ID do item não informado.");
    }

    $id = intval($_GET['id']);

    $stmt = $pdo->prepare("SELECT * FROM checklist WHERE id = ?");
    $stmt->execute([$id]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$item) {
        die("Item não encontrado.");
    }

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $descricao = $_POST['descricao'];
        $resultado = $_POST['resultado'];
        $responsavel = $_POST['responsavel'];
        $classificacao = $_POST['classificacao'];
        $observacoes = $_POST['observacoes'];
        $acao_corretiva_indicada = $_POST['acao_corretiva_indicada'];

        $data_identificacao = validar_datetime($_POST['data_identificacao']);
        $prazo = validar_datetime($_POST['prazo']);
        $data_escalonamento = validar_datetime($_POST['data_escalonamento']);
        $data_conclusao = validar_datetime($_POST['data_conclusao']);

        $sql = "UPDATE checklist 
                SET descricao=?, resultado=?, responsavel=?, classificacao=?, 
                    data_identificacao=?, prazo=?, data_escalonamento=?, data_conclusao=?, 
                    observacoes=?, acao_corretiva_indicada=? 
                WHERE id=?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $descricao, $resultado, $responsavel, $classificacao,
            $data_identificacao, $prazo, $data_escalonamento, $data_conclusao,
            $observacoes, $acao_corretiva_indicada, $id
        ]);

        echo "<script>alert('Item atualizado com sucesso!'); window.location.href='checklist.php';</script>";
        exit;
    }

} catch (PDOException $e) {
    die("Erro de conexão: " . $e->getMessage() . "<br>Verifique se o MySQL está rodando na porta 3307");
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Editar Item - Ferramenta de Auditoria</title>
        <link rel="stylesheet" href="./styles/add-item.css">
    </head>
    <body>
        <div class="container">
            <nav class="main-nav">
                <ul class="nav-list">
                    <li><a href="index.php" class="nav-link">Início</a></li>
                    <li><a href="checklist.php" class="nav-link">Checklist</a></li>
                    <li><a href="pages/relatoriosNc.php" class="nav-link">Relatórios</a></li>
                    <li><a href="pages/envio_comunicacao.php" class="nav-link">Envio e Comunicação</a></li>
                </ul>
            </nav>
        </div>

        <div class="form-container">
            <div class="page-header">
                <h2 class="page-title">Editar Item #<?php echo htmlspecialchars($item['id']); ?></h2>
                <a href="checklist.php" class="btn-secondary">Voltar ao Checklist</a>
            </div>

            <form method="POST" class="checklist-form">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="descricao">Descrição</label>
                        <input type="text" id="descricao" name="descricao" class="form-input" 
                               value="<?php echo htmlspecialchars($item['descricao']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="resultado">Resultado</label>
                        <select id="resultado" name="resultado" class="form-input" required>
                            <option value="">Selecione...</option>
                            <option value="Sim" <?php if($item['resultado']=="Sim") echo "selected"; ?>>Conforme</option>
                            <option value="Não" <?php if($item['resultado']=="Não") echo "selected"; ?>>Não Conforme</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="responsavel">Responsável</label>
                        <input type="text" id="responsavel" name="responsavel" class="form-input" 
                               value="<?php echo htmlspecialchars($item['responsavel']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="classificacao">Classificação</label>
                        <select id="classificacao" name="classificacao" class="form-input">
                            <option value="">Selecione...</option>
                            <option value="Simples" <?php if($item['classificacao']=="Simples") echo "selected"; ?>>Simples</option>
                            <option value="Média" <?php if($item['classificacao']=="Média") echo "selected"; ?>>Média</option>
                            <option value="Complexa" <?php if($item['classificacao']=="Complexa") echo "selected"; ?>>Complexa</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="data_identificacao">Data de Identificação</label>
                        <input type="datetime-local" id="data_identificacao" name="data_identificacao" class="form-input"
                               value="<?php echo $item['data_identificacao'] ? date('Y-m-d\TH:i', strtotime($item['data_identificacao'])) : ''; ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="prazo">Prazo</label>
                        <input type="datetime-local" id="prazo" name="prazo" class="form-input"
                               value="<?php echo $item['prazo'] ? date('Y-m-d\TH:i', strtotime($item['prazo'])) : ''; ?>" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="data_escalonamento">Data de Escalonamento</label>
                        <input type="datetime-local" id="data_escalonamento" name="data_escalonamento" class="form-input"
                               value="<?php echo $item['data_escalonamento'] ? date('Y-m-d\TH:i', strtotime($item['data_escalonamento'])) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="data_conclusao">Data de Conclusão</label>
                        <input type="datetime-local" id="data_conclusao" name="data_conclusao" class="form-input"
                               value="<?php echo $item['data_conclusao'] ? date('Y-m-d\TH:i', strtotime($item['data_conclusao'])) : ''; ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="observacoes">Observações</label>
                        <input type="text" id="observacoes" name="observacoes" class="form-input"
                               value="<?php echo htmlspecialchars($item['observacoes']); ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="acao_corretiva_indicada">Ação Corretiva Indicada</label>
                        <input type="text" id="acao_corretiva_indicada" name="acao_corretiva_indicada" class="form-input"
                               value="<?php echo htmlspecialchars($item['acao_corretiva_indicada']); ?>" required>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-primary">Salvar Alterações</button>
                    <a href="checklist.php" class="btn-cancel">Cancelar</a>
                </div>
            </form>
        </div>
    </body>
</html>