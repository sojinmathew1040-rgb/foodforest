(function() {
        try {
            var savedTheme = localStorage.getItem('foodforest_adm_theme');
            var dbTheme = 'dark' || 'dark';
            var activeTheme = savedTheme || dbTheme;
            if (activeTheme === 'auto') {
                activeTheme = (window.matchMedia && window.matchMedia('(prefers-color-scheme: light)').matches) ? 'light' : 'dark';
            }
            document.documentElement.setAttribute('data-theme', activeTheme);
            document.documentElement.classList.add('theme-' + activeTheme);
        } catch(e) {}
    })();