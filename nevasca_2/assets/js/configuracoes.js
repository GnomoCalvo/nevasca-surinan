function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
    input.setAttribute('type', type);
}

async function atualizarCredenciais(event) {
    event.preventDefault();
    
    const form = event.target;
    const mensagemElement = document.getElementById('mensagem');
    
    // Validação de senha e confirmação
    const password = form.password.value.trim();
    const confirmPassword = form.confirm_password.value.trim();
    
    if (password !== confirmPassword) {
        mensagemElement.textContent = 'As senhas não conferem.';
        mensagemElement.className = 'mensagem erro';
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'atualizar_credenciais');
    formData.append('username', form.username.value);
    formData.append('current_password', form.current_password.value);
    formData.append('password', password);
    formData.append('confirm_password', confirmPassword);
    
    try {
        const response = await fetch('../controllers/ConfiguracoesController.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        mensagemElement.textContent = result.message;
        mensagemElement.className = 'mensagem ' + (result.success ? 'sucesso' : 'erro');
        
        if (result.success) {
            // Limpa os campos de senha
            form.current_password.value = '';
            form.password.value = '';
            form.confirm_password.value = '';
        }
    } catch (error) {
        console.error('Erro:', error);
        mensagemElement.textContent = 'Erro ao atualizar credenciais. Tente novamente.';
        mensagemElement.className = 'mensagem erro';
    }
}

// Validações em tempo real
document.getElementById('username').addEventListener('input', function(e) {
    const input = e.target;
    const isValid = /^[A-Za-z0-9_]+$/.test(input.value);
    
    if (!isValid) {
        input.setCustomValidity('Use apenas letras, números e underline');
    } else if (input.value.length < 5) {
        input.setCustomValidity('Mínimo 5 caracteres');
    } else {
        input.setCustomValidity('');
    }
});

document.getElementById('password').addEventListener('input', function(e) {
    const input = e.target;
    const hasLetter = /[A-Za-z]/.test(input.value);
    const hasNumber = /[0-9]/.test(input.value);
    const hasSpecial = /[!@#$%^&*()\-_=+{};:,<.>]/.test(input.value);
    
    if (input.value.length < 8) {
        input.setCustomValidity('Mínimo 8 caracteres');
    } else if (!hasLetter || !hasNumber || !hasSpecial) {
        input.setCustomValidity('Use pelo menos uma letra, um número e um caractere especial');
    } else {
        input.setCustomValidity('');
    }
});

document.getElementById('confirm_password').addEventListener('input', function(e) {
    const input = e.target;
    const password = document.getElementById('password').value.trim();
    
    if (input.value.trim() !== password) {
        input.setCustomValidity('As senhas não conferem');
    } else {
        input.setCustomValidity('');
    }
});