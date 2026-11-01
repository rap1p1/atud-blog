(() => {
    const output = document.getElementById('dom-output');
    if (!output) {
        return;
    }

    const value = new URLSearchParams(window.location.search).get('q') || '';
    output.textContent = 'Giá trị nhận được: ' + value;
})();
