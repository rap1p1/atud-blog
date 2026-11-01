document.querySelectorAll('form[data-confirm]').forEach((form) => {
    form.addEventListener('submit', (event) => {
        const message = form.getAttribute('data-confirm') || 'Xác nhận thực hiện thao tác?';
        if (!window.confirm(message)) {
            event.preventDefault();
        }
    });
});
