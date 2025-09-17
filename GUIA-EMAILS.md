# 📧 Sistema de Emails - Não-Conformidades

## 🎯 Funcionalidades Disponíveis

### 1. 📧 **Escalonar** (Email de Escalonamento)
- **Quando usar**: Quando uma NC está atrasada ou requer atenção urgente
- **Objetivo**: Notificar superiores sobre NC que precisa de intervenção
- **Conteúdo**: Detalhes da NC + alerta de escalonamento
- **Tom**: Urgente/Alerta

### 2. 📬 **Solicitar Resolução** (Email de Solicitação)
- **Quando usar**: Para solicitar formalmente a resolução de uma NC
- **Objetivo**: Orientar responsável sobre como resolver a NC
- **Conteúdo**: Detalhes da NC + ação corretiva indicada + prazos
- **Tom**: Formal/Instrutivo

## 🔧 Código Adaptado

### ✅ **Melhorias Implementadas:**

1. **Compatibilidade com estrutura atual**:
   - Usa tabela `nao_conformidades` existente
   - Conecta com banco de dados configurado (porta 3307)
   - Mantém sessões PHP ativas

2. **Detecção automática de PHPMailer**:
   - Testa múltiplos caminhos (vendor/, phpmailer/, etc.)
   - Fallback gracioso se não encontrar
   - Suporte para versões diferentes

3. **Validação robusta**:
   - Validação de email com `filter_var()`
   - Verificação de NC existente
   - Tratamento de erros detalhado

4. **Interface amigável**:
   - Formulário responsivo
   - Prévia do email
   - Auto-preenchimento inteligente
   - Validação JavaScript

5. **Email HTML formatado**:
   - Layout profissional responsivo
   - Seções organizadas por cores
   - Informações completas da NC
   - Ação corretiva destacada

6. **Registro de envios**:
   - Tabela `emails_enviados` com histórico
   - Diferenciação por tipo (escalonamento/solicitação)
   - Timestamp automático

## 📋 Como Usar

### **Método 1: Pela tabela de NCs**
1. Na página principal `nao_conformidades.php`
2. Clique no botão "📬 Solicitar" na linha da NC desejada
3. Preencha o formulário de solicitação
4. Envie o email

### **Método 2: Pelo cabeçalho**
1. Clique em "📬 Solicitar Resolução" no topo da página
2. Informe o ID da NC manualmente
3. Preencha o formulário

## 🎨 Diferenças Visuais

| Funcionalidade | Cor | Ícone | Botão |
|---|---|---|---|
| **Escalonar** | ⚠️ Amarelo/Vermelho | 📧 | btn-escalate |
| **Solicitar** | 🔵 Azul | 📬 | btn-primary |

## 🔗 Arquivos Criados

1. **`enviar-email-checklist.php`** - Processa envio de solicitação
2. **`solicitar-resolucao.php`** - Formulário de solicitação
3. **Botões adicionados** em `nao_conformidades.php`

Agora você tem um sistema completo de comunicação por email para NCs! 🚀
