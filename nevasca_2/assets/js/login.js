console.log('Script de login carregado');

document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM carregado');
    
    const form = document.getElementById('loginForm');
    if (!form) {
        console.error('Formulário de login não encontrado');
        return;
    }

    const sanitizeHTML = (str) => {
        const temp = document.createElement('div');
        temp.textContent = str;
        return temp.innerHTML;
    };

    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const username = document.getElementById('username').value.trim();
        const password = document.getElementById('password').value;
        const csrfToken = document.querySelector('input[name="csrf_token"]').value;
        
        console.log('Iniciando tentativa de login');
        console.log('CSRF Token:', csrfToken); // Debug
        console.log('CSRF Token a ser enviado:', csrfToken);
        
        if (!username || !password) {
            showError('Por favor, preencha todos os campos');
            return;
        }
        
        try {
            const formData = new FormData();
            formData.append('action', 'login');
            formData.append('username', username);
            formData.append('password', password);
            formData.append('csrf_token', csrfToken);
            
            // Log dos dados do FormData
            for (let pair of formData.entries()) {
                console.log(pair[0] + ': ' + pair[1]);
            }
            
            console.log('Enviando requisição...');
            
            const response = await fetch('../controllers/LoginHandler.php', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            });

            console.log('Status da resposta:', response.status);
            
            // Tenta ler o texto da resposta primeiro
            const responseText = await response.text();
            console.log('Resposta bruta:', responseText);
            
            // Tenta fazer o parse do JSON
            let result;
            try {
                result = JSON.parse(responseText);
            } catch (parseError) {
                console.error('Erro ao fazer parse do JSON:', parseError);
                console.error('Resposta que causou o erro:', responseText);
                throw new Error('Resposta inválida do servidor');
            }
            
            console.log('Resposta processada:', result);
            
            if (result.success) {
                console.log('Login bem-sucedido, redirecionando...');
                window.location.href = 'vendas.php';
            } else {
                let message = result.message;
                if (result.attempts_left) {
                    message += ` (${result.attempts_left} tentativas restantes)`;
                }
                if (result.blocked) {
                    form.querySelectorAll('input, button').forEach(el => el.disabled = true);
                    setTimeout(() => {
                        form.querySelectorAll('input, button').forEach(el => el.disabled = false);
                        showError('Você já pode tentar novamente.');
                    }, 900000); // 15 minutos
                }
                showError(message);
            }
        } catch (error) {
            console.error('Erro completo:', error);
            showError('Erro ao fazer login. Por favor, tente novamente.');
        }
    });

    function showError(message) {
        console.log('Exibindo erro:', message);
        const errorMessage = document.getElementById('errorMessage');
        if (errorMessage) {
            errorMessage.textContent = sanitizeHTML(message);
            errorMessage.style.display = 'block';
        } else {
            console.warn('Elemento de mensagem de erro não encontrado');
            alert(sanitizeHTML(message));
        }
    }
});