

document.addEventListener('DOMContentLoaded', () => {
    const formulario = document.getElementById('form-registro');

    // Este script solo trabaja en la pantalla de registro
    if (!formulario) return;

    const lista = document.getElementById('lista-mascotas');
    const plantilla = document.getElementById('plantilla-mascota');
    const botonAgregar = formulario.querySelector('[data-agregar-mascota]');
    const razasPorEspecie = JSON.parse(formulario.dataset.razas || '{}');
    let siguienteIndice = Number(lista.dataset.siguienteIndice) || 1;

     // Llena el select de razas según la especie elegida
    const cargarRazas = (selectEspecie) => {
        const selectRaza = selectEspecie.closest('[data-mascota]').querySelector('[data-raza]');
        const razas = razasPorEspecie[selectEspecie.value] || [];

        selectRaza.innerHTML = '';
        selectRaza.add(new Option(razas.length ? 'Selecciona una raza' : 'Primero elige la especie', ''));
        razas.forEach((raza) => selectRaza.add(new Option(raza.nombre, raza.id)));
        selectRaza.disabled = razas.length === 0;
    };

     // Muestra la edad aproximada a partir de la fecha de nacimiento
    const calcularEdad = (inputFecha) => {
        const salida = inputFecha.closest('[data-mascota]').querySelector('[data-edad]');

        if (!inputFecha.value) {
            salida.textContent = TEXTO_EDAD_INICIAL;
            return;
        }

        const nacimiento = new Date(`${inputFecha.value}T00:00:00`);
        const hoy = new Date();
        let meses = (hoy.getFullYear() - nacimiento.getFullYear()) * 12 + (hoy.getMonth() - nacimiento.getMonth());
        if (hoy.getDate() < nacimiento.getDate()) meses--;

        if (meses < 0) {
            salida.textContent = 'La fecha no puede ser futura.';
            return;
        }

        const anios = Math.floor(meses / 12);
        const resto = meses % 12;
        const partes = [];

        if (anios) partes.push(`${anios} ${anios === 1 ? 'año' : 'años'}`);
        if (resto || !anios) partes.push(`${resto} ${resto === 1 ? 'mes' : 'meses'}`);

        salida.textContent = `Edad aproximada: ${partes.join(' y ')}`;
    };

    // Muestra la foto elegida antes de guardar
    const mostrarFoto = (inputFoto) => {
        const vista = inputFoto.closest('[data-mascota]').querySelector('[data-vista-foto]');
        const archivo = inputFoto.files[0];

        if (!archivo) {
            vista.textContent = 'Sin foto';
            return;
        }

        const imagen = document.createElement('img');
        imagen.src = URL.createObjectURL(archivo);
        imagen.alt = 'Foto de la mascota';
        imagen.className = 'h-full w-full object-cover';
        vista.replaceChildren(imagen);
    };
   
});