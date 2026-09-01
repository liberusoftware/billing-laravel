const menuButton = document.querySelector('[data-billing-menu]');
const sidebar = document.querySelector('[data-billing-sidebar]');

menuButton?.addEventListener('click', () => {
    const open = sidebar?.getAttribute('data-open') === 'true';
    sidebar?.setAttribute('data-open', String(!open));
    menuButton.setAttribute('aria-expanded', String(!open));
});

sidebar?.querySelectorAll('a').forEach((link) => link.addEventListener('click', () => {
    if (window.innerWidth <= 768) sidebar.setAttribute('data-open', 'false');
}));
