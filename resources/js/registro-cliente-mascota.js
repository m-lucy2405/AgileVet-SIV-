document.addEventListener('DOMContentLoaded', () => {
    const formulario = document.getElementById('form-registro');

    // Este script solo trabaja en la pantalla de registro
    if (!formulario) return;

    const lista = document.getElementById('lista-mascotas');
    const plantilla = document.getElementById('plantilla-mascota');
    const botonAgregar = formulario.querySelector('[data-agregar-mascota]');
    const razasPorEspecie = JSON.parse(formulario.dataset.razas || '{}');
    let siguienteIndice = Number(lista.dataset.siguienteIndice) || 1;

     // Cambia las sugerencias de raza según la especie elegida
    const cargarRazas = (selectEspecie) => {
        const inputRaza = selectEspecie.closest('[data-mascota]').querySelector('[data-raza]');
        const sugerencias = document.getElementById(inputRaza.getAttribute('list'));
        const razas = razasPorEspecie[selectEspecie.value] || [];

        sugerencias.replaceChildren(...razas.map((raza) => new Option(raza, raza)));
        inputRaza.value = '';
    };

     // Muestra la edad aproximada a partir de la fecha de nacimiento
    const calcularEdad = (inputFecha) => {
        const salida = inputFecha.closest('[data-mascota]').querySelector('[data-edad]');

        if (!inputFecha.value) {
             salida.textContent = '';
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

    // Numera las mascotas y oculta "Quitar" si solo hay una
    const actualizarTarjetas = () => {
        const tarjetas = lista.querySelectorAll('[data-mascota]');

        tarjetas.forEach((tarjeta, posicion) => {
            tarjeta.querySelector('[data-titulo-mascota]').textContent = `Mascota ${posicion + 1}`;
            tarjeta.querySelector('[data-quitar-mascota]').classList.toggle('hidden', tarjetas.length === 1);
        });
    };

    // Agregar otra mascota
    botonAgregar.addEventListener('click', () => {
        const html = plantilla.innerHTML.replaceAll('__INDICE__', siguienteIndice);
        siguienteIndice++;

        lista.insertAdjacentHTML('beforeend', html);
        actualizarTarjetas();
        lista.lastElementChild.querySelector('input[type="text"]')?.focus();
    });

    // Cambios dentro de cualquier mascota
    lista.addEventListener('change', (evento) => {
        const campo = evento.target;

        if (campo.matches('[data-especie]')) cargarRazas(campo);
        if (campo.matches('[data-fecha-nacimiento]')) calcularEdad(campo);
    });

    // Quitar una mascota
    lista.addEventListener('click', (evento) => {
        const boton = evento.target.closest('[data-quitar-mascota]');
        if (!boton) return;

        boton.closest('[data-mascota]').remove();
        actualizarTarjetas();
    });


        const botonGuardar = formulario.querySelector('[data-guardar]');
    const aviso = document.getElementById('aviso-registro');

    // Mensaje en español según el error del campo
    const mensajeError = (campo) => {
        const estado = campo.validity;

        if (estado.valueMissing) return 'Este campo es obligatorio.';
        if (estado.typeMismatch) return 'Escribe un correo válido.';
        if (estado.patternMismatch) return campo.title || 'El formato no es válido.';
        if (estado.tooLong) return `Máximo ${campo.maxLength} caracteres.`;
        if (estado.rangeOverflow) return 'La fecha no puede ser futura.';
        return '';
    };

    // Marca el campo en rojo o verde y muestra el error debajo
    const mostrarError = (campo, mensaje) => {
        const contenedor = campo.parentElement;
        contenedor.querySelectorAll('.error-campo').forEach((error) => error.remove());

        campo.classList.toggle('border-red-500', Boolean(mensaje));
        campo.classList.toggle('border-green-500', !mensaje && campo.value !== '');

        if (!mensaje) return;

        const error = document.createElement('p');
        error.className = 'error-campo';
        error.textContent = mensaje;
        contenedor.append(error);
    };

    const validarCampo = (campo) => mostrarError(campo, campo.checkValidity() ? '' : mensajeError(campo));
    const esValidable = (campo) => campo.willValidate && !['checkbox', 'hidden', 'submit'].includes(campo.type);

    // 70001111 → 7000-1111
    const formatearTelefono = (input) => {
        const digitos = input.value.replace(/\D/g, '').slice(0, 8);
        input.value = digitos.length > 4 ? `${digitos.slice(0, 4)}-${digitos.slice(4)}` : digitos;
    };

    // Valida al salir del campo y, después, mientras se escribe
    formulario.addEventListener('focusout', (evento) => {
        if (!esValidable(evento.target)) return;
        evento.target.dataset.tocado = 'si';
        validarCampo(evento.target);
    });

    formulario.addEventListener('input', (evento) => {
        const campo = evento.target;
        if (campo.id === 'cliente-telefono') formatearTelefono(campo);
        if (esValidable(campo) && campo.dataset.tocado) validarCampo(campo);
    });

       const mostrarAviso = (texto, tipo) => {
        const colores = tipo === 'exito'
            ? ['border-[#4ade80]', 'bg-[#1f4d3a]', 'text-[#dcfce7]']
            : ['border-[#f87171]', 'bg-[#5c1f24]', 'text-[#fee2e2]'];

        aviso.className = 'mb-6 rounded-lg border px-4 py-3 text-sm font-medium';
        aviso.classList.add(...colores);
        aviso.textContent = texto;
        aviso.scrollIntoView({ behavior: 'smooth', block: 'center' });
    };

    // Laravel manda "mascotas.0.nombre"; el campo se llama "mascotas[0][nombre]"
    const aNombreCampo = (clave) => {
        const [primero, ...resto] = clave.split('.');
        return primero + resto.map((parte) => `[${parte}]`).join('');
    };

    // Deja el formulario vacío y con una sola mascota
    const limpiarFormulario = () => {
        lista.querySelectorAll('[data-mascota]').forEach((tarjeta, posicion) => {
            if (posicion > 0) tarjeta.remove();
        });

        formulario.reset();
        formulario.querySelectorAll('.error-campo').forEach((error) => error.remove());
        formulario.querySelectorAll('[data-edad]').forEach((edad) => (edad.textContent = ''));
        formulario.querySelectorAll('.campo').forEach((campo) => {
            campo.classList.remove('border-red-500', 'border-green-500');
            delete campo.dataset.tocado;
        });

        actualizarTarjetas();
        document.getElementById('cliente-nombre').focus();
    };

    // Envía el registro sin recargar la página
    formulario.addEventListener('submit', async (evento) => {
        evento.preventDefault();

        const campos = [...formulario.elements].filter(esValidable);
        campos.forEach((campo) => {
            campo.dataset.tocado = 'si';
            validarCampo(campo);
        });

        const primerError = campos.find((campo) => !campo.checkValidity());
        if (primerError) {
            primerError.focus();
            return;
        }

        botonGuardar.disabled = true;
        botonGuardar.textContent = 'Guardando…';

        try {
            const respuesta = await fetch(formulario.action, {
                method: 'POST',
                body: new FormData(formulario),
                headers: { Accept: 'application/json' },
            });
            const datos = await respuesta.json().catch(() => ({}));

            if (respuesta.status === 422) {
                Object.entries(datos.errors ?? {}).forEach(([clave, mensajes]) => {
                    const campo = formulario.elements[aNombreCampo(clave)];
                    if (campo) mostrarError(campo, mensajes[0]);
                });
                mostrarAviso('Revisa los campos marcados en rojo.', 'error');
                return;
            }

            if (!respuesta.ok) throw new Error();

            mostrarAviso(datos.message ?? 'El registro se guardó correctamente.', 'exito');
            limpiarFormulario();
        } catch {
            mostrarAviso('No se pudo guardar el registro. Intenta de nuevo.', 'error');
        } finally {
            botonGuardar.disabled = false;
            botonGuardar.textContent = 'Guardar registro';
        }
    });

    // Estado inicial
    lista.querySelectorAll('[data-fecha-nacimiento]').forEach((input) => {
        if (input.value) calcularEdad(input);
    });
    actualizarTarjetas();
   
});