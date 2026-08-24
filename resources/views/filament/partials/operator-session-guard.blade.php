<script>
    (() => {
        if (window.__operatorSessionGuardLoaded) return;
        window.__operatorSessionGuardLoaded = true;

        const idleMilliseconds = {{ (int) config('attendance.operator_idle_timeout_minutes', 60) }} * 60 * 1000;
        const activityKey = 'antrian_mpp_operator_last_activity';
        const logoutUrl = @js(route('filament.admin.auth.logout'));
        let lastStoredAt = 0;

        const recordActivity = () => {
            const now = Date.now();
            if (now - lastStoredAt < 30000) return;
            lastStoredAt = now;
            localStorage.setItem(activityKey, String(now));
        };

        const logout = () => {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = logoutUrl;
            const token = document.createElement('input');
            token.type = 'hidden';
            token.name = '_token';
            token.value = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
            form.appendChild(token);
            document.body.appendChild(form);
            form.submit();
        };

        ['pointerdown', 'keydown', 'touchstart', 'scroll'].forEach((eventName) => {
            window.addEventListener(eventName, recordActivity, { passive: true });
        });

        recordActivity();
        window.setInterval(() => {
            const lastActivity = Number(localStorage.getItem(activityKey) || Date.now());
            if (Date.now() - lastActivity >= idleMilliseconds) logout();
        }, 60000);
    })();
</script>
