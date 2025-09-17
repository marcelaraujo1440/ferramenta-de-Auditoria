<?php
session_start();

// Configurações do banco de dados
$host = 'localhost';
$port = 3307;
$dbname = 'ferramenta_auditoria';
$username = 'root';
$password = 'root';

$pdo = null;
$conexao_erro = false;

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8";
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    $conexao_erro = true;
    $erro_mensagem = $e->getMessage();
}

// Buscar NC pelo ID
$nc_id = intval($_GET['nc_id'] ?? 0);
$nc = null;
$todas_ncs = [];

if (!$conexao_erro) {
    try {
        // Buscar todas as NCs para o seletor
        $stmt = $pdo->prepare("SELECT id, titulo, status, responsavel FROM nao_conformidades ORDER BY data_criacao DESC");
        $stmt->execute();
        $todas_ncs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Se foi especificado um ID, buscar a NC específica
        if ($nc_id > 0) {
            $stmt = $pdo->prepare("SELECT * FROM nao_conformidades WHERE id = ?");
            $stmt->execute([$nc_id]);
            $nc = $stmt->fetch(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
        $erro_mensagem = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitar Resolução - Não-Conformidade</title>
    <link rel="stylesheet" href="styles/style.css">
    <link rel="stylesheet" href="styles/add-item.css">
    <style>
        .solicitacao-container {
            max-width: 800px;
            margin: 30px auto;
            padding: 0;
        }
        
        .page-header {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            text-align: center;
        }
        
        .page-title {
            color: #333;
            font-size: 2rem;
            margin-bottom: 10px;
        }
        
        .form-container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .nc-info {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 25px;
            border-radius: 8px;
            margin-bottom: 30px;
            border-left: 4px solid #007bff;
        }
        
        .nc-info h3 {
            margin: 0 0 15px 0;
            color: #007bff;
            font-size: 1.3rem;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
            font-size: 0.95rem;
        }
        
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e9ecef;
            border-radius: 6px;
            font-family: inherit;
            font-size: 0.95rem;
            box-sizing: border-box;
            transition: border-color 0.3s ease;
        }
        
        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }
        
        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        .form-group small {
            display: block;
            margin-top: 5px;
            color: #6c757d;
            font-size: 0.85rem;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 6px;
            margin-bottom: 25px;
            border: 1px solid transparent;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
        }
        
        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
            border-color: #bee5eb;
        }
        
        .form-actions {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
            margin-top: 40px;
            padding-top: 25px;
            border-top: 1px solid #e9ecef;
        }
        
        .btn-primary, .btn-secondary {
            padding: 12px 25px;
            border: none;
            border-radius: 6px;
            font-size: 0.95rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: #007bff;
            color: white;
        }
        
        .btn-primary:hover {
            background: #0056b3;
            transform: translateY(-1px);
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #545b62;
            transform: translateY(-1px);
        }
        
        .preview-section {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 20px;
            border-radius: 6px;
            margin-top: 25px;
            border-left: 4px solid #28a745;
        }
        
        .preview-section h4 {
            margin: 0 0 10px 0;
            color: #28a745;
        }
        
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .solicitacao-container {
                margin: 15px;
            }
            
            .page-header,
            .form-container {
                padding: 20px;
            }
            
            .form-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <nav class="main-nav">
            <ul class="nav-list">
                <li><a href="index.php" class="nav-link">Início</a></li>
                <li><a href="checklist.php" class="nav-link">Checklist</a></li>
                <li><a href="pages/relatoriosNc.php" class="nav-link">Relatórios</a></li>
                <li><a href="nao_conformidades.php" class="nav-link">Não-Conformidades</a></li>
                <li><a href="pages/envio_comunicacao.php" class="nav-link">Envio e Comunicação</a></li>
            </ul>
        </nav>
    </div>

    <div class="solicitacao-container">
        <div class="page-header">
            <h1 class="page-title">📬 Solicitar Resolução de Não-Conformidade</h1>
            <p style="color: #666; margin-top: 10px;">Envie uma solicitação formal de resolução com orientações detalhadas</p>
        </div>
        
        <?php if ($conexao_erro): ?>
            <div class="form-container">
                <div class="alert alert-error">
                    <strong>Erro de Conexão:</strong> <?= htmlspecialchars($erro_mensagem) ?>
                </div>
                <div class="form-actions">
                    <a href="nao_conformidades.php" class="btn-secondary">← Voltar</a>
                </div>
            </div>
        <?php elseif (!$nc && empty($todas_ncs)): ?>
            <div class="form-container">
                <div class="alert alert-error">
                    <strong>Erro:</strong> Nenhuma não-conformidade encontrada no sistema.
                </div>
                <div class="form-actions">
                    <a href="nao_conformidades.php" class="btn-secondary">← Voltar às Não-Conformidades</a>
                </div>
            </div>
        <?php elseif (!$nc): ?>
            <!-- Seletor de NC quando não foi especificada -->
            <div class="form-container">
                <div class="alert alert-info">
                    <strong>💡 Selecione uma Não-Conformidade:</strong><br>
                    Escolha a não-conformidade para a qual deseja enviar uma solicitação de resolução.
                </div>
                
                <form method="GET" style="margin-bottom: 30px;">
                    <div class="form-group">
                        <label for="nc_select">Não-Conformidade *</label>
                        <select id="nc_select" name="nc_id" required onchange="this.form.submit()">
                            <option value="">Selecione uma não-conformidade...</option>
                            <?php foreach ($todas_ncs as $nc_item): ?>
                                <option value="<?= $nc_item['id'] ?>">
                                    #<?= $nc_item['id'] ?> - <?= htmlspecialchars($nc_item['titulo']) ?> 
                                    (<?= htmlspecialchars($nc_item['status']) ?>) - 
                                    <?= htmlspecialchars($nc_item['responsavel']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
                
                <div class="form-actions">
                    <a href="nao_conformidades.php" class="btn-secondary">← Voltar às Não-Conformidades</a>
                </div>
            </div>
        <?php else: ?>
            
            <div class="form-container">
            
            <!-- Informações da NC -->
            <div class="nc-info">
                <h3>📋 Não-Conformidade #<?= htmlspecialchars($nc['id']) ?></h3>
                <p><strong>Título:</strong> <?= htmlspecialchars($nc['titulo']) ?></p>
                <p><strong>Responsável Atual:</strong> <?= htmlspecialchars($nc['responsavel']) ?></p>
                <p><strong>Status:</strong> <?= htmlspecialchars($nc['status']) ?></p>
                <p><strong>Descrição:</strong> <?= htmlspecialchars($nc['descricao']) ?></p>
                <?php if (!empty($nc['prazo_resolucao'])): ?>
                    <p><strong>Prazo Atual:</strong> <?= date('d/m/Y H:i', strtotime($nc['prazo_resolucao'])) ?></p>
                <?php endif; ?>
            </div>
            
            <div class="alert alert-info">
                <strong>📋 Sobre esta funcionalidade:</strong><br>
                Este formulário permite enviar uma solicitação formal de resolução para a não-conformidade.
                O email será enviado com todos os detalhes e orientações para resolução.
            </div>
            
            <!-- Formulário de solicitação -->
            <form method="POST" action="enviar-email-checklist.php">
                <input type="hidden" name="id_nc" value="<?= htmlspecialchars($nc['id']) ?>">
                
                <div class="form-group">
                    <label for="destinatario">Email do Destinatário *</label>
                    <input type="email" id="destinatario" name="destinatario" required 
                           placeholder="email@exemplo.com"
                           value="<?= htmlspecialchars($nc['responsavel']) ?>">
                    <small style="color: #666;">Email da pessoa responsável por resolver a NC</small>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="responsavel">Responsável pela Resolução</label>
                        <input type="text" id="responsavel" name="responsavel" 
                               placeholder="Nome do responsável"
                               value="<?= htmlspecialchars($nc['responsavel']) ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="rqa">RQA Responsável</label>
                        <input type="text" id="rqa" name="rqa" 
                               placeholder="Nome do RQA">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="prazo">Prazo para Resolução</label>
                    <input type="datetime-local" id="prazo" name="prazo"
                           value="<?= !empty($nc['prazo_resolucao']) ? date('Y-m-d\TH:i', strtotime($nc['prazo_resolucao'])) : '' ?>">
                    <small style="color: #666;">Deixe em branco para usar o prazo padrão da NC</small>
                </div>
                
                <div class="form-group">
                    <label for="acao">Ação Corretiva Indicada *</label>
                    <textarea id="acao" name="acao" required 
                              placeholder="Descreva a ação corretiva recomendada para resolver esta não-conformidade..."><?= htmlspecialchars($nc['observacoes'] ?? '') ?></textarea>
                </div>
                
                <div class="preview-section">
                    <h4 style="margin: 0 0 10px 0; color: #333;">📋 Prévia do Email</h4>
                    <p><strong>Assunto:</strong> Solicitação de Resolução de Não Conformidade #<?= $nc['id'] ?></p>
                    <p><strong>Tipo:</strong> Email HTML formatado com todos os detalhes da NC</p>
                    <p><strong>Inclui:</strong> Identificação, descrição, responsável, prazo, ação corretiva e observações</p>
                </div>
                
                <div class="form-actions">
                    <a href="nao_conformidades.php" class="btn-secondary">← Cancelar</a>
                    <button type="submit" class="btn-primary">📧 Enviar Solicitação</button>
                </div>
            </form>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Auto-preencher responsável quando o email for alterado
        document.getElementById('destinatario').addEventListener('change', function() {
            const email = this.value;
            const responsavelField = document.getElementById('responsavel');
            
            if (email && responsavelField.value === '<?= htmlspecialchars($nc['responsavel']) ?>') {
                // Extrair nome do email se possível
                const nomePossivel = email.split('@')[0].replace(/[._]/g, ' ');
                responsavelField.value = nomePossivel;
            }
        });
        
        // Validação do formulário
        document.querySelector('form').addEventListener('submit', function(e) {
            const destinatario = document.getElementById('destinatario').value;
            const acao = document.getElementById('acao').value;
            
            if (!destinatario || !acao) {
                e.preventDefault();
                alert('Por favor, preencha o email do destinatário e a ação corretiva.');
                return;
            }
            
            if (!confirm('Deseja enviar a solicitação de resolução para ' + destinatario + '?')) {
                e.preventDefault();
            }
        });
    </script>
</body>
</html>
