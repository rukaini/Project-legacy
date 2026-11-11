<script>
    (function () {
        var displayDuration = 3500;
        var exitDuration = 320;
        var toastTimers = new WeakMap();

        function reducedMotion() {
            return window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        }

        function toastRegion() {
            var region = document.querySelector('[data-app-toast-region]');
            if (region) return region;

            region = document.createElement('div');
            region.className = 'app-toast-region';
            region.dataset.appToastRegion = '';
            region.setAttribute('aria-live', 'polite');
            region.setAttribute('aria-atomic', 'false');
            document.body.appendChild(region);

            return region;
        }

        function removeEmptyRegion(region) {
            if (region && !region.querySelector('[data-app-toast]')) region.remove();
        }

        function dismissToast(toast) {
            if (!toast || !toast.isConnected || toast.classList.contains('is-leaving')) return;

            window.clearTimeout(toastTimers.get(toast));
            toast.classList.add('is-leaving');
            window.setTimeout(function () {
                var region = toast.parentElement;
                toast.remove();
                removeEmptyRegion(region);
            }, reducedMotion() ? 0 : exitDuration);
        }

        function scheduleToast(toast) {
            var duration = Number(toast.dataset.autoDismiss) || displayDuration;
            toastTimers.set(toast, window.setTimeout(function () {
                dismissToast(toast);
            }, duration));
        }

        window.showAppToast = function (type, message) {
            var normalizedType = type === 'error' ? 'error' : 'success';
            var normalizedMessage = String(message || '').trim();
            if (!normalizedMessage) return null;

            var toast = document.createElement('div');
            toast.className = 'app-toast ' + normalizedType;
            toast.dataset.appToast = '';
            toast.dataset.autoDismiss = String(displayDuration);
            toast.setAttribute('role', normalizedType === 'error' ? 'alert' : 'status');

            var icon = document.createElement('i');
            icon.className = 'bi ' + (normalizedType === 'error' ? 'bi-exclamation-triangle-fill' : 'bi-check-circle-fill');
            icon.setAttribute('aria-hidden', 'true');

            var copy = document.createElement('span');
            copy.textContent = normalizedMessage;

            toast.appendChild(icon);
            toast.appendChild(copy);
            toastRegion().appendChild(toast);
            scheduleToast(toast);

            return toast;
        };

        function initializeServerToasts() {
            document.querySelectorAll('[data-app-toast]').forEach(function (toast) {
                scheduleToast(toast);
            });
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initializeServerToasts, {once: true});
        } else {
            initializeServerToasts();
        }
    })();
</script>
