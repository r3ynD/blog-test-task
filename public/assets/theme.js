let theme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';

try {
    const savedTheme = sessionStorage.getItem('theme');
    if (savedTheme === 'dark' || savedTheme === 'light') {
        theme = savedTheme;
    }
} catch {
}

document.documentElement.dataset.theme = theme;

document.addEventListener('DOMContentLoaded', () => {
    const button = document.getElementById('theme-toggle');

    if (!button) {
        return;
    }

    function updateButton() {
        const isDark = document.documentElement.dataset.theme === 'dark';
        button.textContent = isDark ? 'Светлая тема' : 'Тёмная тема';
        button.setAttribute('aria-pressed', String(isDark));
    }

    button.hidden = false;
    updateButton();

    button.addEventListener('click', () => {
        theme = document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark';
        document.documentElement.dataset.theme = theme;

        try {
            sessionStorage.setItem('theme', theme);
        } catch {
        }

        updateButton();
    });
});
