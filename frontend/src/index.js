document.addEventListener('DOMContentLoaded', function () {
    const registreForm = document.getElementById('registreForm');
    const loginForm = document.getElementById('loginForm');
    const toggleBtn = document.getElementById('toggleFormBtn');
    const titol = document.getElementById('titolFormulari');

    toggleBtn.addEventListener('click', function () {
        if (registreForm.style.display !== 'none') {
            registreForm.style.display = 'none';
            loginForm.style.display = 'block';
            titol.textContent = 'Inici de Sessió';
            toggleBtn.textContent = 'No tens compte? Registra\'t';
        } else {
            registreForm.style.display = 'block';
            loginForm.style.display = 'none';
            titol.textContent = 'Registre d\'Usuari';
            toggleBtn.textContent = 'Tens un compta? Inicia sessió';
        }
    });
});