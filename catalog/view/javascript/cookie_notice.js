document.addEventListener('DOMContentLoaded', function() {

    if (localStorage.getItem('cookieAccepted') === 'true') {
        return;
    }
    
    // Показываем уведомление
    const cookieNotice = document.getElementById('cookie-notice');
    if (cookieNotice) {
        setTimeout(function() {
            cookieNotice.style.display = 'block';
            cookieNotice.classList.add('show');
        }, 500);
        
        // Обработчик кнопки принятия
        const acceptButton = document.getElementById('cookie-accept');
        if (acceptButton) {
            acceptButton.addEventListener('click', function() {
                // Сохраняем в localStorage
                localStorage.setItem('cookieAccepted', 'true');
                
                // Скрываем уведомление с анимацией
                cookieNotice.style.opacity = '0';
                cookieNotice.style.transform = 'translateY(100%)';
                
                setTimeout(function() {
                    cookieNotice.style.display = 'none';
                }, 300);
            });
        }
        
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && cookieNotice.style.display !== 'none') {
                acceptButton.click();
            }
        });
    }
});
