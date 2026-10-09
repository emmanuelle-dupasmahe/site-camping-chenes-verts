document.addEventListener('DOMContentLoaded', function () {
    const btnBurger = document.getElementById('btn-burger');
    const menuPrincipal = document.getElementById('menu-principal');

    if (btnBurger && menuPrincipal) {
        btnBurger.addEventListener('click', function () {
            menuPrincipal.classList.toggle('hidden');
        });
    }
});