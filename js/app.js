// Funcionalidade principal da aplicação
class AuditTool {
    constructor() {
        this.init();
    }

    init() {
        this.bindEvents();
    }

    bindEvents() {
        const createBtn = document.querySelector('.btn-primary');
        const input = document.getElementById('checklist-name');

        if (createBtn && input) {
            createBtn.addEventListener('click', () => this.handleCreateChecklist());
            input.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    this.handleCreateChecklist();
                }
            });
        }
    }

    handleCreateChecklist() {
        const input = document.getElementById('checklist-name');
        const value = input.value.trim();

        if (value === '') {
            this.showError(input, 'Por favor, insira um nome para o checklist');
        } else {
            this.showSuccess(input, `Checklist "${value}" criado com sucesso`);
            setTimeout(() => {
                input.value = '';
                this.resetInput(input);
            }, 2000);
        }
    }

    showError(input, message) {
        input.classList.remove('success');
        input.classList.add('error');
        input.focus();
        
        setTimeout(() => {
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
}

// Inicializar quando o DOM estiver carregado
document.addEventListener('DOMContentLoaded', () => {
    new AuditTool();
});