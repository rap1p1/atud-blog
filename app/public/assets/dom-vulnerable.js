(() => {
    const output = document.getElementById('dom-output');
    if (!output) {
        return;
    }

    const value = new URLSearchParams(window.location.search).get('q') || '';
    output.innerHTML = '<p><strong>Giá trị nhận được:</strong> ' + value + '</p>';
})();
