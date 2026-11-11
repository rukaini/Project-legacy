<script>
(() => {
    const input = document.getElementById('materialFile');
    const name = document.querySelector('[data-file-name]');
    input?.addEventListener('change', () => { if (name) name.textContent = input.files?.[0]?.name || 'Choose a file'; });
})();
</script>
@include('trainer.simple.partials.report-scripts')
