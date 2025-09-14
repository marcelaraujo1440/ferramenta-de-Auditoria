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

        // Formulário do checklist
        if (form) {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                this.addItemToChecklist();
            });
        }
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
        return {
            id: String(this.currentId).padStart(3, '0'),
            descricao: document.getElementById('descricao').value.trim(),
            resultado: document.getElementById('resultado').value,
            responsavel: document.getElementById('responsavel').value.trim(),
            classificacao: document.getElementById('classificacao').value,
            situacao: document.getElementById('situacao').value,
            dataIdentificacao: document.getElementById('dataIdentificacao').value,
            prazo: document.getElementById('prazo').value,
            dataEscalonamento: document.getElementById('dataEscalonamento').value || '-',
            dataConclusao: document.getElementById('dataConclusao').value || '-',
            observacoes: document.getElementById('observacoes').value.trim() || '-',
            acaoCorretiva: document.getElementById('acaoCorretiva').value.trim()
        };
    }

    validateForm(data) {
        const required = ['descricao', 'resultado', 'responsavel', 'classificacao', 'situacao', 'dataIdentificacao', 'prazo', 'acaoCorretiva'];
        
        for (let field of required) {
            if (!data[field] || data[field] === '') {
                alert(`Por favor, preencha o campo: ${this.getFieldLabel(field)}`);
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
            dataIdentificacao: 'Data de Identificação',
            prazo: 'Prazo',
            acaoCorretiva: 'Ação Corretiva'
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
        
        row.innerHTML = `
            <td>${data.id}</td>
            <td>${data.descricao}</td>
            <td>${data.resultado}</td>
            <td>${data.responsavel}</td>
            <td><span class="status-badge ncf-${data.classificacao.toLowerCase()}">${data.classificacao}</span></td>
            <td><span class="status-badge status-${this.getSituacaoClass(data.situacao)}">${data.situacao}</span></td>
            <td class="date-cell">${this.formatDate(data.dataIdentificacao)}</td>
            <td class="date-cell">${this.formatDate(data.prazo)}</td>
            <td class="date-cell">${data.dataEscalonamento !== '-' ? this.formatDate(data.dataEscalonamento) : '-'}</td>
            <td class="date-cell">${data.dataConclusao !== '-' ? this.formatDate(data.dataConclusao) : '-'}</td>
            <td>${data.observacoes}</td>
            <td>${data.acaoCorretiva}</td>
        `;

        return row;
    }

    getSituacaoClass(situacao) {
        const map = {
            'Pendente': 'pendente',
            'Em Andamento': 'andamento',
            'Concluído': 'concluido',
            'Vencido': 'vencido'
        };
        return map[situacao] || 'pendente';
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