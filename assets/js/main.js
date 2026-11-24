/* PlayFinder.lk - shared JavaScript */
document.addEventListener('DOMContentLoaded', () => {
    // Toggle menus / dropdowns / sidebar: <button data-toggle="#id">
    document.querySelectorAll('[data-toggle]').forEach(btn => {
        btn.addEventListener('click', ev => {
            ev.stopPropagation();
            document.querySelector(btn.dataset.toggle)?.classList.toggle('open');
        });
    });
    document.addEventListener('click', () => {
        document.querySelectorAll('.dropdown-menu.open').forEach(m => m.classList.remove('open'));
    });

    // Close alerts
    document.querySelectorAll('.alert-close').forEach(b => b.addEventListener('click', () => b.parentElement.remove()));

    // Confirm dangerous actions: <form data-confirm="Are you sure?">
    document.querySelectorAll('form[data-confirm]').forEach(f => {
        f.addEventListener('submit', ev => {
            if (!confirm(f.dataset.confirm)) ev.preventDefault();
        });
    });

    // Password show/hide: <button type="button" data-show-password="#id">
    document.querySelectorAll('[data-show-password]').forEach(b => {
        b.addEventListener('click', () => {
            const input = document.querySelector(b.dataset.showPassword);
            input.type = input.type === 'password' ? 'text' : 'password';
            b.textContent = input.type === 'password' ? 'Show' : 'Hide';
        });
    });
});
