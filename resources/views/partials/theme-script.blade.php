<script>
    (function () {
        try {
            var saved = localStorage.getItem('theme');
            var dark = saved === 'dark'
                || (! saved && window.matchMedia('(prefers-color-scheme: dark)').matches);

            document.documentElement.classList.toggle('dark', dark);
        } catch (e) {}
    })();
</script>
