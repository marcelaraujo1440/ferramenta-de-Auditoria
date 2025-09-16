<?php
// Script FORÇADO para corrigir a base de dados
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<style>body{font-family:Arial,sans-serif;padding:20px;} .success{color:green;} .error{color:red;} .info{color:blue;}</style>";

try {
    echo "<h2>🔧 Migração da Base de Dados - Ferramenta de Auditoria</h2>";
    
    // 1. Conexão com a base de dados
    echo "<div class='info'>1. Conectando à base de dados...</div>";
    $host = 'localhost:3307';
    $db = 'ferramenta_auditoria';
    $user = 'root';
    $pass = 'root';
    
    $dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    echo "<div class='success'>✅ Conectado à base de dados com sucesso!</div><br>";

    // 2. Verificar estrutura atual
    echo "<div class='info'>2. Verificando estrutura atual da tabela checklist...</div>";
    $stmt = $pdo->query("DESCRIBE checklist");
    $columns = $stmt->fetchAll();
    
    echo "<h3>📋 Estrutura atual:</h3>";
    echo "<table border='1' style='border-collapse:collapse;margin:10px 0;'>";
    echo "<tr style='background:#f8f9fa;'><th>Campo</th><th>Tipo</th><th>Permite NULL</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td><strong>{$col['Field']}</strong></td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "</tr>";
    }
    echo "</table><br>";

    // 3. Modificação da coluna classificacao
    echo "<div class='info'>3. Verificando coluna 'classificacao'...</div>";
    $stmt = $pdo->query("SHOW COLUMNS FROM checklist WHERE Field = 'classificacao'");
    $classificacao_col = $stmt->fetch();
    
    if ($classificacao_col && $classificacao_col['Null'] === 'NO') {
        echo "<div class='info'>Alterando coluna 'classificacao' para aceitar NULL...</div>";
        $pdo->exec("ALTER TABLE checklist MODIFY COLUMN classificacao ENUM('Menor', 'Maior', 'Observação') NULL");
        echo "<div class='success'>✅ Coluna 'classificacao' atualizada para aceitar NULL</div>";
    } else {
        echo "<div class='success'>✅ Coluna 'classificacao' já aceita NULL</div>";
    }

    // 4. Modificação da coluna acao_corretiva_indicada
    echo "<div class='info'>4. Verificando coluna 'acao_corretiva_indicada'...</div>";
    $stmt = $pdo->query("SHOW COLUMNS FROM checklist WHERE Field = 'acao_corretiva_indicada'");
    $acao_col = $stmt->fetch();
    
    if ($acao_col && $acao_col['Null'] === 'NO') {
        echo "<div class='info'>Alterando coluna 'acao_corretiva_indicada' para aceitar NULL...</div>";
        $pdo->exec("ALTER TABLE checklist MODIFY COLUMN acao_corretiva_indicada TEXT NULL");
        echo "<div class='success'>✅ Coluna 'acao_corretiva_indicada' atualizada para aceitar NULL</div>";
    } else {
        echo "<div class='success'>✅ Coluna 'acao_corretiva_indicada' já aceita NULL</div>";
    }

    // 5. Modificação da coluna situacao
    echo "<div class='info'>5. Verificando coluna 'situacao'...</div>";
    $stmt = $pdo->query("SHOW COLUMNS FROM checklist WHERE Field = 'situacao'");
    $situacao_col = $stmt->fetch();
    
    if ($situacao_col && $situacao_col['Null'] === 'NO') {
        echo "<div class='info'>Alterando coluna 'situacao' para aceitar NULL...</div>";
        $pdo->exec("ALTER TABLE checklist MODIFY COLUMN situacao ENUM('Resolvido', 'Não Resolvido', 'Em Aberto') NULL");
        echo "<div class='success'>✅ Coluna 'situacao' atualizada para aceitar NULL</div>";
    } else {
        echo "<div class='success'>✅ Coluna 'situacao' já aceita NULL</div>";
    }

    // 6. Modificação da coluna prazo
    echo "<div class='info'>6. Verificando coluna 'prazo'...</div>";
    $stmt = $pdo->query("SHOW COLUMNS FROM checklist WHERE Field = 'prazo'");
    $prazo_col = $stmt->fetch();
    
    if ($prazo_col && $prazo_col['Null'] === 'NO') {
        echo "<div class='info'>Alterando coluna 'prazo' para aceitar NULL...</div>";
        $pdo->exec("ALTER TABLE checklist MODIFY COLUMN prazo DATE NULL");
        echo "<div class='success'>✅ Coluna 'prazo' atualizada para aceitar NULL</div>";
    } else {
        echo "<div class='success'>✅ Coluna 'prazo' já aceita NULL</div>";
    }

    // 7. Modificação da coluna data_escalonamento
    echo "<div class='info'>7. Verificando coluna 'data_escalonamento'...</div>";
    $stmt = $pdo->query("SHOW COLUMNS FROM checklist WHERE Field = 'data_escalonamento'");
    $escalonamento_col = $stmt->fetch();
    
    if ($escalonamento_col && $escalonamento_col['Null'] === 'NO') {
        echo "<div class='info'>Alterando coluna 'data_escalonamento' para aceitar NULL...</div>";
        $pdo->exec("ALTER TABLE checklist MODIFY COLUMN data_escalonamento DATE NULL");
        echo "<div class='success'>✅ Coluna 'data_escalonamento' atualizada para aceitar NULL</div>";
    } else {
        echo "<div class='success'>✅ Coluna 'data_escalonamento' já aceita NULL</div>";
    }

    // 8. Estrutura final
    echo "<h3>✅ Estrutura final da tabela:</h3>";
    $stmt = $pdo->query("DESCRIBE checklist");
    $columns = $stmt->fetchAll();
    
    echo "<table border='1' style='border-collapse:collapse;margin:10px 0;'>";
    echo "<tr style='background:#d4edda;'><th>Campo</th><th>Tipo</th><th>Permite NULL</th></tr>";
    foreach ($columns as $col) {
        $style = (in_array($col['Field'], ['classificacao', 'situacao', 'acao_corretiva_indicada', 'prazo', 'data_escalonamento'])) ? "background:#e8f5e8;" : "";
        echo "<tr style='$style'>";
        echo "<td><strong>{$col['Field']}</strong></td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "</tr>";
    }
    echo "</table>";

    echo "<br><div class='success'><h3>🎉 MIGRAÇÃO CONCLUÍDA COM SUCESSO!</h3></div>";
    echo "<div class='info'>Todos os campos de NC podem agora aceitar valores NULL quando não aplicáveis.</div>";

} catch (Exception $e) {
    echo "<div class='error'>❌ Erro durante a migração: " . $e->getMessage() . "</div>";
}
?>
echo "<h2>🔧 Correção FORÇADA da Base de Dados</h2>";

// Conexão direta com configurações explícitas
try {
    $pdo = new PDO(
        "mysql:host=localhost;port=3307;dbname=ferramenta_auditoria;charset=utf8", 
        'root', 
        'root',
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
    echo "<p class='success'>✓ Conectado à base de dados</p>";
    
    // 1. Mostrar estrutura atual
    echo "<h3>📋 Estrutura atual da tabela checklist:</h3>";
    $stmt = $pdo->query("DESCRIBE checklist");
    $columns = $stmt->fetchAll();
    
    echo "<table border='1' style='border-collapse:collapse;margin:10px 0;'>";
    echo "<tr style='background:#f0f0f0;'><th>Campo</th><th>Tipo</th><th>Permite NULL</th></tr>";
    
    $classificacao_permite_null = false;
    $situacao_existe = false;
    $situacao_permite_null = false;
    $acao_corretiva_permite_null = false;
    $prazo_permite_null = false;
    $data_escalonamento_permite_null = false;
    
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>{$col['Field']}</td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "</tr>";
        
        if ($col['Field'] === 'classificacao' && $col['Null'] === 'YES') {
            $classificacao_permite_null = true;
        }
        if ($col['Field'] === 'situacao') {
            $situacao_existe = true;
            if ($col['Null'] === 'YES') {
                $situacao_permite_null = true;
            }
        }
        if ($col['Field'] === 'acao_corretiva_indicada' && $col['Null'] === 'YES') {
            $acao_corretiva_permite_null = true;
        }
        if ($col['Field'] === 'prazo' && $col['Null'] === 'YES') {
            $prazo_permite_null = true;
        }
        if ($col['Field'] === 'data_escalonamento' && $col['Null'] === 'YES') {
            $data_escalonamento_permite_null = true;
        }
    }
    echo "</table>";
    
    // 2. Forçar alteração da classificacao
    if (!$classificacao_permite_null) {
        echo "<h3>🔨 FORÇANDO alteração da coluna classificacao...</h3>";
        
        // Primeiro, atualizar registros problemáticos
        $pdo->exec("UPDATE checklist SET classificacao = 'Simples' WHERE classificacao = '' OR classificacao IS NULL");
        echo "<p class='info'>→ Registros vazios atualizados para 'Simples'</p>";
        
        // Alterar a estrutura
        $pdo->exec("ALTER TABLE checklist MODIFY COLUMN classificacao ENUM('Simples', 'Média', 'Complexa') NULL DEFAULT NULL");
        echo "<p class='success'>✓ Coluna classificacao agora permite NULL</p>";
    } else {
        echo "<h3>✓ Coluna classificacao já permite NULL</h3>";
    }
    
    // 3. Alterar acao_corretiva_indicada para permitir NULL se necessário
    if (!$acao_corretiva_permite_null) {
        echo "<h3>🔨 FORÇANDO alteração da coluna acao_corretiva_indicada...</h3>";
        
        // Primeiro, atualizar registros problemáticos
        $pdo->exec("UPDATE checklist SET acao_corretiva_indicada = NULL WHERE acao_corretiva_indicada = ''");
        echo "<p class='info'>→ Registros vazios atualizados para NULL</p>";
        
        // Alterar a estrutura
        $pdo->exec("ALTER TABLE checklist MODIFY COLUMN acao_corretiva_indicada TEXT NULL");
        echo "<p class='success'>✓ Coluna acao_corretiva_indicada agora permite NULL</p>";
    } else {
        echo "<h3>✓ Coluna acao_corretiva_indicada já permite NULL</h3>";
    }
    
    // 4. Adicionar coluna situacao se não existir
    if (!$situacao_existe) {
        echo "<h3>➕ Adicionando coluna situacao...</h3>";
        $pdo->exec("ALTER TABLE checklist ADD COLUMN situacao ENUM('Resolvido', 'Não Resolvido', 'Em Aberto') NULL AFTER classificacao");
        echo "<p class='success'>✓ Coluna situacao adicionada</p>";
    } else {
        // Se existe mas não permite NULL, alterar
        if (!$situacao_permite_null) {
            echo "<h3>🔨 FORÇANDO alteração da coluna situacao...</h3>";
            
            // Primeiro, atualizar registros com valor padrão
            $pdo->exec("UPDATE checklist SET situacao = NULL WHERE situacao = ''");
            echo "<p class='info'>→ Registros vazios atualizados para NULL</p>";
            
            // Alterar a estrutura
            $pdo->exec("ALTER TABLE checklist MODIFY COLUMN situacao ENUM('Resolvido', 'Não Resolvido', 'Em Aberto') NULL");
            echo "<p class='success'>✓ Coluna situacao agora permite NULL</p>";
        } else {
            echo "<h3>✓ Coluna situacao já existe e permite NULL</h3>";
        }
    }
    
    // 6. Alterar prazo para permitir NULL se necessário
    if (!$prazo_permite_null) {
        echo "<h3>🔨 FORÇANDO alteração da coluna prazo...</h3>";
        
        // Alterar a estrutura
        $pdo->exec("ALTER TABLE checklist MODIFY COLUMN prazo DATETIME NULL");
        echo "<p class='success'>✓ Coluna prazo agora permite NULL</p>";
    } else {
        echo "<h3>✓ Coluna prazo já permite NULL</h3>";
    }
    
    // 7. Alterar data_escalonamento para permitir NULL se necessário
    if (!$data_escalonamento_permite_null) {
        echo "<h3>🔨 FORÇANDO alteração da coluna data_escalonamento...</h3>";
        
        // Alterar a estrutura
        $pdo->exec("ALTER TABLE checklist MODIFY COLUMN data_escalonamento DATETIME NULL");
        echo "<p class='success'>✓ Coluna data_escalonamento agora permite NULL</p>";
    } else {
        echo "<h3>✓ Coluna data_escalonamento já permite NULL</h3>";
    }
    
    // 8. Estrutura final
    echo "<h3>✅ Estrutura FINAL da tabela:</h3>";
    $stmt = $pdo->query("DESCRIBE checklist");
    $columns = $stmt->fetchAll();
    
    echo "<table border='1' style='border-collapse:collapse;margin:10px 0;'>";
    echo "<tr style='background:#d4edda;'><th>Campo</th><th>Tipo</th><th>Permite NULL</th></tr>";
    foreach ($columns as $col) {
        $style = ($col['Field'] === 'classificacao' || $col['Field'] === 'situacao' || $col['Field'] === 'acao_corretiva_indicada') ? "background:#e8f5e8;" : "";
        echo "<tr style='$style'>";
        echo "<td><strong>{$col['Field']}</strong></td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<div style='background:#d4edda;padding:20px;margin:20px 0;border-radius:8px;border:2px solid #28a745;'>";
    echo "<h2>🎉 MIGRAÇÃO CONCLUÍDA COM SUCESSO!</h2>";
    echo "<p><strong>Alterações realizadas:</strong></p>";
    echo "<ul>";
    echo "<li>✅ Coluna 'classificacao' agora permite valores NULL</li>";
    echo "<li>✅ Coluna 'acao_corretiva_indicada' agora permite valores NULL</li>";
    echo "<li>✅ Coluna 'situacao' agora permite valores NULL</li>";
    echo "<li>✅ Sistema pronto para funcionar corretamente</li>";
    echo "<li>✅ Campos específicos de NC são bloqueados automaticamente para conformidades</li>";
    echo "</ul>";
    echo "<p><a href='adicionar-item.php' style='background:#007cba;color:white;padding:12px 24px;text-decoration:none;border-radius:6px;font-weight:bold;'>🚀 Testar Sistema</a></p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background:#f8d7da;padding:20px;margin:20px 0;border-radius:8px;border:2px solid #dc3545;'>";
    echo "<h3>❌ ERRO NA MIGRAÇÃO:</h3>";
    echo "<p><strong>Erro:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>Verifique:</strong></p>";
    echo "<ul>";
    echo "<li>MAMP está rodando?</li>";
    echo "<li>MySQL na porta 3307?</li>";
    echo "<li>Base de dados 'ferramenta_auditoria' existe?</li>";
    echo "</ul>";
    echo "</div>";
}
?>
