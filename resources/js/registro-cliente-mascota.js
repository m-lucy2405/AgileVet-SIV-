

document.addEventListener('DOMContentLoaded', () => {
    const formulario = document.getElementById('form-registro');

    // Este script solo trabaja en la pantalla de registro
    if (!formulario) return;

    const lista = document.getElementById('lista-mascotas');
    const plantilla = document.getElementById('plantilla-mascota');
    const botonAgregar = formulario.querySelector('[data-agregar-mascota]');
    const razasPorEspecie = JSON.parse(formulario.dataset.razas || '{}');
    let siguienteIndice = Number(lista.dataset.siguienteIndice) || 1;

   
});