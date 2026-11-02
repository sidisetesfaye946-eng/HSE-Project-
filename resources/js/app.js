import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.data('themeSwitcher', () => ({
    isDark: document.documentElement.classList.contains('dark'),
    toggleTheme() {
        this.isDark = ! this.isDark;
        document.documentElement.classList.toggle('dark', this.isDark);
        localStorage.setItem('theme', this.isDark ? 'dark' : 'light');
    },
}));

Alpine.start();

