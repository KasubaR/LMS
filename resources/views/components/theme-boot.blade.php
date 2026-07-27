{{-- Prevents a flash of the wrong theme before app.js loads. Default: light. --}}
<script>
    (function () {
        try {
            var stored = localStorage.getItem('lms-theme');
            document.documentElement.dataset.theme = stored === 'dark' ? 'dark' : 'light';
        } catch (e) {
            document.documentElement.dataset.theme = 'light';
        }
    })();
</script>
