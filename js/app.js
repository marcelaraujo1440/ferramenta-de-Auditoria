// Funcionalidade principal da aplicação
class AuditTool {
    constructor() {
        this.currentId = 1;
        this.init();
    }

    init() {
        this.bindEvents();
        this.initModal();
    }

    bindEvents() {
        const createBtn = document.querySelector('.btn-primary');
        const input = document.getElementById('checklist-name');
        const form = document.getElementById('checklist-form');

        if (createBtn && input) {
            createBtn.addEventListener('click', () => this.handleCreateChecklist());
            input.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    this.handleCreateChecklist();
                }
            });
        }

        if (form) {
            form.addEventListener('submit', (e) => {
                // Verificar se os elementos existem antes de validar
                const descricao = document.getElementById('descricao');
                const resultado = document.getElementById('resultado');
                const responsavel = document.getElementById('responsavel');
                const dataIdentificacao = document.getElementById('data_identificacao');
                const prazo = document.getElementById('prazo');
                const acaoCorretiva = document.getElementById('acao_corretiva_indicada');
                
                // Se algum elemento não existir, deixar o formulário seguir normalmente
                if (!descricao || !resultado || !responsavel || !dataIdentificacao || !prazo || !acaoCorretiva) {
                    return;
                }
                
                const formData = this.getFormData();
                if (!this.validateForm(formData)) {
                    e.preventDefault(); 
                } 
            });
        }
        
        // Gerenciar campos específicos de NC baseado no resultado
        this.setupCamposNC();
        
    }

    addItemToChecklist() {
        const formData = this.getFormData();
        
        if (this.validateForm(formData)) {
            this.addRowToTable(formData);
            this.clearForm();
            this.showSuccessMessage();
        }
    }

    getFormData() {
        // Verificar se os elementos existem antes de acessar
        const descricaoEl = document.getElementById('descricao');
        const resultadoEl = document.getElementById('resultado');
        const responsavelEl = document.getElementById('responsavel');
        const classificacaoEl = document.getElementById('classificacao');
        const situacaoEl = document.getElementById('situacao');
        const dataIdentificacaoEl = document.getElementById('data_identificacao');
        const prazoEl = document.getElementById('prazo');
        const dataEscalonamentoEl = document.getElementById('data_escalonamento');
        const dataConclusaoEl = document.getElementById('data_conclusao');
        const observacoesEl = document.getElementById('observacoes');
        const acaoCorretivaEl = document.getElementById('acao_corretiva_indicada');
        
        return {
            id: String(this.currentId).padStart(3, '0'),
            descricao: descricaoEl ? descricaoEl.value.trim() : '',
            resultado: resultadoEl ? resultadoEl.value : '',
            responsavel: responsavelEl ? responsavelEl.value.trim() : '',
            classificacao: classificacaoEl ? classificacaoEl.value : '',
            situacao: situacaoEl ? situacaoEl.value : '',
            data_identificacao: dataIdentificacaoEl ? dataIdentificacaoEl.value : '',
            prazo: prazoEl ? prazoEl.value : '',
            data_escalonamento: dataEscalonamentoEl ? (dataEscalonamentoEl.value || '-') : '-',
            data_conclusao: dataConclusaoEl ? (dataConclusaoEl.value || '-') : '-',
            observacoes: observacoesEl ? (observacoesEl.value.trim() || '-') : '-',
            acao_corretiva_indicada: acaoCorretivaEl ? acaoCorretivaEl.value.trim() : ''
        };
    }

    validateForm(data) {
        const required = ['descricao', 'resultado', 'responsavel', 'data_identificacao'];
        
        for (let field of required) {
            if (!data[field] || data[field] === '') {
                alert(`Por favor, preencha o campo: ${this.getFieldLabel(field)}`);
                return false;
            }
        }
        
        // Validar campos específicos apenas se resultado for "Não" (não conformidade)
        if (data.resultado === 'Não') {
            if (!data.classificacao || data.classificacao === '') {
                alert('Por favor, preencha a Classificação da NCF para não conformidades');
                return false;
            }
            
            if (!data.acao_corretiva_indicada || data.acao_corretiva_indicada === '') {
                alert('Por favor, preencha a Ação Corretiva para não conformidades');
                return false;
            }
            
            if (!data.situacao || data.situacao === '') {
                alert('Por favor, preencha a Situação da NCF para não conformidades');
                return false;
            }
            
            if (!data.prazo || data.prazo === '') {
                alert('Por favor, preencha o Prazo para não conformidades');
                return false;
            }
        }
        
        return true;
    }

    getFieldLabel(field) {
        const labels = {
            descricao: 'Descrição',
            resultado: 'Resultado',
            responsavel: 'Responsável',
            classificacao: 'Classificação da NCF',
            situacao: 'Situação da NCF',
            data_identificacao: 'Data de Identificação',
            prazo: 'Prazo',
            acao_corretiva_indicada: 'Ação Corretiva'
        };
        return labels[field] || field;
    }

    addRowToTable(data) {
        const tbody = document.querySelector('#checklist-table tbody');
        const row = this.createTableRow(data);
        tbody.appendChild(row);
        this.currentId++;
        
        // Animação de entrada
        row.style.opacity = '0';
        row.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            row.style.transition = 'all 0.5s ease';
            row.style.opacity = '1';
            row.style.transform = 'translateY(0)';
        }, 100);
    }

    createTableRow(data) {
        const row = document.createElement('tr');
        
        // Tratar classificação vazia
        const classificacaoDisplay = data.classificacao || '-';
        const classificacaoClass = data.classificacao ? `ncf-${data.classificacao.toLowerCase()}` : '';
        
        row.innerHTML = `
            <td>${data.id}</td>
            <td>${data.descricao}</td>
            <td>${data.resultado}</td>
            <td>${data.responsavel}</td>
            <td><span class="status-badge ${classificacaoClass}">${classificacaoDisplay}</span></td>
            <td class="date-cell">${this.formatDate(data.data_identificacao)}</td>
            <td class="date-cell">${this.formatDate(data.prazo)}</td>
            <td class="date-cell">${data.data_escalonamento !== '-' ? this.formatDate(data.data_escalonamento) : '-'}</td>
            <td class="date-cell">${data.data_conclusao !== '-' ? this.formatDate(data.data_conclusao) : '-'}</td>
            <td>${data.observacoes}</td>
            <td>${data.acao_corretiva_indicada}</td>
        `;

        return row;
    }

    formatDate(dateString) {
        if (dateString === '-' || !dateString) return '-';
        
        try {
            const date = new Date(dateString);
            return date.toLocaleDateString('pt-BR');
        } catch (e) {
            return dateString;
        }
    }

    clearForm() {
        document.getElementById('checklist-form').reset();
    }

    showSuccessMessage() {
        const btn = document.querySelector('.btn-primary');
        const originalText = btn.textContent;
        
        btn.textContent = 'Item Adicionado! Redirecionando...';
        btn.style.background = '#00aa00';
        btn.disabled = true;
        
        setTimeout(() => {
            btn.textContent = originalText;
            btn.style.background = '#000000';
            btn.disabled = false;
        }, 3000);
    }

    initModal() {
        // Auto-fechar modal após 3 segundos se estiver visível
        const modal = document.getElementById('successModal');
        if (modal && modal.classList.contains('show')) {
            setTimeout(() => {
                this.closeModal();
            }, 3000);
        }

        // Fechar modal clicando fora dele
        window.onclick = (event) => {
            if (event.target === modal) {
                this.closeModal();
            }
        };
    }

    handleCreateChecklist() {
        const input = document.getElementById('checklist-name');
        const value = input.value.trim();

        if (value === '') {
            this.showError(input, 'Por favor, insira um nome para o checklist');
        } else {
            // Como agora usamos PHP form submit, não precisamos fazer nada aqui
            // O formulário será enviado normalmente
            return true;
        }
    }

    showError(input, message) {
        input.classList.remove('success');
        input.classList.add('error');
        input.focus();
        
        // Mostrar mensagem de erro temporariamente no placeholder
        const originalPlaceholder = input.placeholder;
        input.placeholder = message;
        
        setTimeout(() => {
            input.placeholder = originalPlaceholder;
            this.resetInput(input);
        }, 3000);
    }

    showSuccess(input, message) {
        input.classList.remove('error');
        input.classList.add('success');
        input.placeholder = message;
    }

    resetInput(input) {
        input.classList.remove('error', 'success');
        input.placeholder = 'Insira o nome do seu checklist';
    }

    closeModal() {
        const modal = document.getElementById('successModal');
        if (modal) {
            modal.classList.remove('show');
            // Limpar o formulário após fechar o modal
            const input = document.getElementById('checklist-name');
            if (input) {
                input.value = '';
            }
        }
    }

    setupCamposNC() {
        const resultadoEl = document.getElementById('resultado');
        const classificacaoEl = document.getElementById('classificacao');
        const acaoCorretivaEl = document.getElementById('acao_corretiva_indicada');
        const situacaoEl = document.getElementById('situacao');
        const prazoEl = document.getElementById('prazo');
        const dataEscalonamentoEl = document.getElementById('data_escalonamento');
        
        if (resultadoEl && (classificacaoEl || acaoCorretivaEl || situacaoEl || prazoEl || dataEscalonamentoEl)) {
            // Função para gerenciar o estado dos campos baseado no resultado
            const toggleCamposNC = () => {
                if (resultadoEl.value === 'Sim') {
                    // Se é conformidade, desabilitar e limpar campos específicos de NC
                    if (classificacaoEl) {
                        classificacaoEl.value = '';
                        classificacaoEl.disabled = true;
                        classificacaoEl.style.backgroundColor = '#f5f5f5';
                        classificacaoEl.style.color = '#999';
                    }
                    
                    if (acaoCorretivaEl) {
                        acaoCorretivaEl.value = '';
                        acaoCorretivaEl.disabled = true;
                        acaoCorretivaEl.style.backgroundColor = '#f5f5f5';
                        acaoCorretivaEl.style.color = '#999';
                        acaoCorretivaEl.placeholder = 'Não aplicável para conformidades';
                    }
                    
                    if (situacaoEl) {
                        situacaoEl.value = '';
                        situacaoEl.disabled = true;
                        situacaoEl.style.backgroundColor = '#f5f5f5';
                        situacaoEl.style.color = '#999';
                    }
                    
                    if (prazoEl) {
                        prazoEl.value = '';
                        prazoEl.disabled = true;
                        prazoEl.style.backgroundColor = '#f5f5f5';
                        prazoEl.style.color = '#999';
                    }
                    
                    if (dataEscalonamentoEl) {
                        dataEscalonamentoEl.value = '';
                        dataEscalonamentoEl.disabled = true;
                        dataEscalonamentoEl.style.backgroundColor = '#f5f5f5';
                        dataEscalonamentoEl.style.color = '#999';
                    }
                } else {
                    // Se é não conformidade, habilitar todos os campos
                    if (classificacaoEl) {
                        classificacaoEl.disabled = false;
                        classificacaoEl.style.backgroundColor = '';
                        classificacaoEl.style.color = '';
                    }
                    
                    if (acaoCorretivaEl) {
                        acaoCorretivaEl.disabled = false;
                        acaoCorretivaEl.style.backgroundColor = '';
                        acaoCorretivaEl.style.color = '';
                        acaoCorretivaEl.placeholder = 'Descreva a ação corretiva necessária...';
                    }
                    
                    if (situacaoEl) {
                        situacaoEl.disabled = false;
                        situacaoEl.style.backgroundColor = '';
                        situacaoEl.style.color = '';
                    }
                    
                    if (prazoEl) {
                        prazoEl.disabled = false;
                        prazoEl.style.backgroundColor = '';
                        prazoEl.style.color = '';
                    }
                    
                    if (dataEscalonamentoEl) {
                        dataEscalonamentoEl.disabled = false;
                        dataEscalonamentoEl.style.backgroundColor = '';
                        dataEscalonamentoEl.style.color = '';
                    }
                }
            };
            
            // Executar na inicialização
            toggleCamposNC();
            
            // Executar quando o resultado mudar
            resultadoEl.addEventListener('change', toggleCamposNC);
        }
    }
}

// Função global para o botão do modal
function closeModal() {
    const auditTool = new AuditTool();
    auditTool.closeModal();
}

// Inicializar quando o DOM estiver carregado
document.addEventListener('DOMContentLoaded', () => {
    new AuditTool();
});
