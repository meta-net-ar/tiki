document.addEventListener('DOMContentLoaded', () => {
    const digits = document.querySelectorAll('.otp-digit');
    const hidden = document.getElementById('authCode');

    function syncHidden() {
        hidden.value = Array.from(digits).map(input => input.value).join('');
    }

    digits.forEach((input, i) => {
        input.addEventListener('input', () => {
            input.value = input.value.replace(/\D/g, '').slice(-1);
            syncHidden();
            if (input.value && i < digits.length - 1) digits[i + 1].focus();
        });

        input.addEventListener('keydown', (e) => {
            if (e.key === 'Backspace' && !input.value && i > 0) digits[i - 1].focus();
        });

        input.addEventListener('paste', (e) => {
            e.preventDefault();
            const pasted = (e.clipboardData || window.clipboardData)
                .getData('text')
                .replace(/\D/g, '')
                .slice(0, digits.length);
            [...pasted].forEach((char, j) => {
                if (digits[i + j]) digits[i + j].value = char;
            });
            const nextIndex = Math.min(i + pasted.length, digits.length - 1);
            digits[nextIndex].focus();
            syncHidden();
        });
    });
});