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
                const form = document.getElementById('checklist-form-main');
                const input = document.getElementById('checklist-name');

                if (form) {
                    form.addEventListener('submit', (e) => {
                        this.handleFormSubmit(e);
                    });
                }

                if (input) {
                    input.addEventListener('keypress', (e) => {
                        if (e.key === 'Enter') {
                            e.preventDefault();
                            form.dispatchEvent(new Event('submit'));
                        }
                    });
                }
            }

            handleFormSubmit(e) {
                const input = document.getElementById('checklist-name');
                const value = input.value.trim();

                if (value === '') {
                    e.preventDefault();
                    this.showError(input, 'Por favor, insira um nome para o checklist');
                    return false;
                }

                // Se chegou até aqui, o formulário pode ser enviado normalmente
                return true;
            }

            initModal() {
                const modal = document.getElementById('successModal');
                
                // Fechar modal clicando fora dele
                if (modal) {
                    modal.addEventListener('click', (e) => {
                        if (e.target === modal) {
                            this.closeModal();
                        }
                    });

                    // Se o modal está sendo mostrado, auto-fechar após 10 segundos
                    if (modal.classList.contains('show')) {
                        setTimeout(() => {
                            this.closeModal();
                        }, 10000);
                    }
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
                    // Limpar a URL removendo os parâmetros de sucesso
                    if (window.location.search.includes('success=true')) {
                        const url = new URL(window.location);
                        url.searchParams.delete('success');
                        url.searchParams.delete('name');
                        window.history.replaceState({}, document.title, url.pathname);
                    }
                }
            }
        }

        // Função global para o botão do modal
        function closeModal() {
            const auditTool = window.auditToolInstance || new AuditTool();
            auditTool.closeModal();
        }

        // Inicializar quando o DOM estiver carregado
        document.addEventListener('DOMContentLoaded', () => {
            window.auditToolInstance = new AuditTool();
        });