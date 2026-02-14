document.addEventListener('DOMContentLoaded', () => {
    const confirmButtons = document.querySelectorAll('[data-confirm]');
    confirmButtons.forEach((button) => {
        button.addEventListener('click', (event) => {
            const message = button.getAttribute('data-confirm');
            if (!window.confirm(message)) {
                event.preventDefault();
            }
        });
    });
});
