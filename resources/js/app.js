import './registro-cliente-mascota';
import './calendario-citas';
document.addEventListener('DOMContentLoaded', () => {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('overlay');
    const botonAbrir = document.getElementById('btn-abrir-menu');
    const botonCerrar = document.getElementById('btn-cerrar-menu');

    if (!sidebar) return;

    const abrirMenu = () => {
        sidebar.classList.remove('-translate-x-full');
        overlay.classList.remove('hidden');
    };

    const cerrarMenu = () => {
        sidebar.classList.add('-translate-x-full');
        overlay.classList.add('hidden');
    };

    botonAbrir?.addEventListener('click', abrirMenu);
    botonCerrar?.addEventListener('click', cerrarMenu);
    overlay?.addEventListener('click', cerrarMenu);
});