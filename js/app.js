// Funcionalidade principal da aplicação
class AuditTool {
    constructor() {
        this.init();
    }

    init() {
        this.bindEvents();
        this.initModal();
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