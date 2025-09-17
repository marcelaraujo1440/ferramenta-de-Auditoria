# Sistema de Gestão de Não-Conformidades

## 📋 Visão Geral

Sistema completo para registro e acompanhamento de não-conformidades com processo automatizado de escalonamento e notificação por email.

## 🗂️ Estrutura de Arquivos

### Arquivos Principais
- **`nao_conformidades.php`** - Página principal com dashboard e formulário de registro
- **`processa_nc.php`** - Processamento de criação e edição de NCs
- **`enviar-email-nc.php`** - Interface e processamento de envio de emails
- **`editar_nc.php`** - Formulário de edição de não-conformidades
- **`visualizar_nc.php`** - Visualização detalhada de uma NC
- **`escalar_nc.php`** - Processamento de escalonamento manual

### Arquivos de Banco de Dados
- **`db/nao_conformidades.sql`** - Script de criação da tabela

## 🛠️ Funcionalidades Implementadas

### 📝 Registro de Não-Conformidades
- **Formulário completo** com campos obrigatórios:
  - Título da NC
  - Descrição detalhada
  - Responsável
  - Data de abertura
  - Prazo de resolução
  - Observações (opcional)

- **Validações**:
  - Campos obrigatórios
  - Validação de datas
  - Prazo deve ser posterior à data de abertura

### 📊 Dashboard e Estatísticas
- **Cards de estatísticas** em tempo real:
  - Total de NCs
  - NCs Abertas
  - NCs Em Andamento
  - NCs Resolvidas
  - NCs Escalonadas
  - NCs Vencidas

### 📋 Listagem e Gerenciamento
- **Tabela responsiva** com todas as NCs
- **Ordenação inteligente**:
  - Escalonadas primeiro
  - Depois por status
  - Por prazo de resolução

- **Indicadores visuais**:
  - Badges de status coloridos
  - Badges de urgência (Normal, Crítica, Vencida)
  - Contador de dias restantes

### 🚨 Sistema de Escalonamento

#### Escalonamento Automático
- **Verificação automática** a cada carregamento da página
- **Critério**: NCs com prazo vencido e status não "Resolvida" ou "Escalonada"
- **Ação**: Atualiza status para "Escalonada" automaticamente
- **Redirecionamento**: Para envio de email automático

#### Escalonamento Manual
- **Botão de escalonamento** em cada NC
- **Confirmação** antes do escalonamento
- **Redirecionamento** direto para envio de email

### 📧 Sistema de Notificação por Email

#### Funcionalidades do Email
- **Template profissional** em HTML
- **Informações completas** da NC:
  - ID e título
  - Descrição detalhada
  - Responsável
  - Datas importantes
  - Status atual
  - Observações

#### Integração com PHPMailer
- **Verificação automática** da disponibilidade do PHPMailer
- **Configuração SMTP** para Gmail
- **Fallback gracioso** quando PHPMailer não está disponível

### ✏️ Edição de Não-Conformidades
- **Formulário pré-preenchido** com dados atuais
- **Todos os campos editáveis** incluindo status
- **Informações de auditoria**:
  - Data de criação
  - Última atualização
  - Status atual

### 👁️ Visualização Detalhada
- **Interface limpa e moderna**
- **Informações organizadas** em cards
- **Timeline** com histórico da NC
- **Badges visuais** para status e urgência
- **Ações contextuais** baseadas no status

## 🗄️ Estrutura do Banco de Dados

### Tabela: `nao_conformidades`
```sql
CREATE TABLE nao_conformidades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    descricao TEXT NOT NULL,
    responsavel VARCHAR(100) NOT NULL,
    data_abertura DATETIME NOT NULL,
    prazo_resolucao DATETIME NOT NULL,
    status ENUM('Aberta', 'Em andamento', 'Resolvida', 'Escalonada') DEFAULT 'Aberta',
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    observacoes TEXT,
    escalonada_automaticamente BOOLEAN DEFAULT FALSE
);
```

### Tabela: `emails_enviados` (criada automaticamente)
```sql
CREATE TABLE emails_enviados (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nc_id INT,
    destinatario VARCHAR(255),
    data_envio TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

## 🎨 Design e Interface

### Características do Design
- **Clean e moderno** seguindo padrão do sistema
- **Responsivo** para desktop, tablet e mobile
- **Cores consistentes** com identidade visual
- **Tipografia limpa** e legível
- **Espaçamento adequado** para melhor UX

### Componentes Visuais
- **Cards informativos** com cores distintivas
- **Badges coloridos** para status e urgência
- **Botões de ação** com ícones intuitivos
- **Modais e alertas** para feedback
- **Tabelas responsivas** com overflow

## 🔄 Fluxo de Trabalho

### 1. Registro de NC
1. Usuário acessa `nao_conformidades.php`
2. Preenche formulário de registro
3. Sistema valida dados
4. NC é criada com status "Aberta"

### 2. Acompanhamento
1. Sistema verifica automaticamente prazos vencidos
2. NCs vencidas são escalonadas automaticamente
3. Dashboard atualiza estatísticas em tempo real

### 3. Escalonamento
1. **Automático**: Sistema detecta prazo vencido
2. **Manual**: Usuário clica em "Escalonar"
3. Status muda para "Escalonada"
4. Redirecionamento para envio de email

### 4. Notificação
1. Usuário informa email destinatário
2. Sistema monta template profissional
3. Email é enviado via SMTP
4. Registro do envio é mantido

## 🔧 Configuração e Instalação

### Pré-requisitos
- **PHP 7.4+** com PDO MySQL
- **MySQL 5.7+** ou MariaDB
- **PHPMailer** (opcional, para emails)
- **Servidor web** (Apache/Nginx)

### Passos de Instalação
1. **Copiar arquivos** para diretório web
2. **Executar script SQL** em `db/nao_conformidades.sql`
3. **Configurar banco** nos arquivos PHP (host, porta, usuário, senha)
4. **Instalar PHPMailer** (opcional) para funcionalidade de email
5. **Configurar SMTP** em `enviar-email-nc.php`

### Configuração de Email
```php
$mail->Host = 'smtp.gmail.com';
$mail->Username = 'seu-email@gmail.com';
$mail->Password = 'sua-senha-app';
$mail->Port = 587;
```

## 🔐 Segurança Implementada

### Validações
- **Sanitização** de todas as entradas
- **Prepared statements** para prevenir SQL injection
- **Validação de tipos** e formatos de dados
- **Verificação de permissões** em operações críticas

### Proteções
- **Escape de HTML** em todas as saídas
- **Validação de sessão** onde aplicável
- **Verificação de métodos HTTP** apropriados
- **Tratamento de erros** sem exposição de dados sensíveis

## 📱 Responsividade

### Breakpoints
- **Desktop**: > 768px - Layout completo
- **Tablet**: 768px - 480px - Layout adaptado
- **Mobile**: < 480px - Layout vertical

### Adaptações Mobile
- **Tabelas** com scroll horizontal
- **Botões** em layout vertical
- **Cards** em coluna única
- **Formulários** otimizados para touch

## 🚀 Melhorias Futuras

### Funcionalidades Planejadas
- [ ] **Workflow de aprovação** para escalonamentos
- [ ] **Notificações push** em tempo real
- [ ] **Relatórios PDF** exportáveis
- [ ] **API REST** para integrações
- [ ] **Dashboard analítico** avançado
- [ ] **Sistema de comentários** nas NCs
- [ ] **Anexos de arquivos** nas NCs
- [ ] **Integração com sistemas** externos

### Melhorias Técnicas
- [ ] **Cache** de consultas frequentes
- [ ] **Logs de auditoria** detalhados
- [ ] **Backup automático** de dados
- [ ] **Testes automatizados**
- [ ] **Monitoramento** de performance
- [ ] **Versionamento** de alterações

## 📞 Suporte e Manutenção

### Logs do Sistema
O sistema gera logs automáticos para:
- Criação de NCs
- Escalonamentos (manuais e automáticos)
- Envios de email
- Erros de sistema

### Manutenção Recomendada
- **Backup regular** do banco de dados
- **Limpeza periódica** de logs antigos
- **Monitoramento** de espaço em disco
- **Atualizações** de segurança do PHP/MySQL

---

**Desenvolvido para atender às necessidades específicas de gestão de qualidade e conformidade regulatória.**
