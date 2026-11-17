<script>
    // Calcula la próxima fecha con el intervalo del catálogo y muestra u oculta el nombre libre
    (() => {
        const selector = document.getElementById('catalogo_id');
        const bloqueNombre = document.getElementById('bloque-nombre');
        const nombreLibre = document.getElementById('nombre_libre');
        const aplicacion = document.getElementById('fecha_aplicacion');
        const proxima = document.getElementById('proxima');
        const ayuda = document.getElementById('ayuda-proxima');
        const dosis = document.getElementById('dosis'); // solo existe en desparasitaciones

        if (!selector) return;

        const dias = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
        let proximaEditadaAMano = proxima.value !== '';
        let dosisSugeridaAnterior = '';

        const sumarDias = (fechaTexto, cantidad) => {
            const fecha = new Date(fechaTexto + 'T00:00:00');
            fecha.setDate(fecha.getDate() + cantidad);
            const mes = String(fecha.getMonth() + 1).padStart(2, '0');
            const dia = String(fecha.getDate()).padStart(2, '0');
            return `${fecha.getFullYear()}-${mes}-${dia}`;
        };

        const describir = (fechaTexto) => {
            const fecha = new Date(fechaTexto + 'T00:00:00');
            const nombreDia = dias[fecha.getDay()];
            return nombreDia === 'domingo'
                ? 'Cae domingo: la cita se agendará el sábado anterior.'
                : `Cae ${nombreDia}.`;
        };

        const actualizar = () => {
            const opcion = selector.selectedOptions[0];
            const usaCatalogo = selector.value !== '';
            const intervalo = Number(opcion?.dataset.intervalo || 0);

            // Nombre libre solo cuando se elige "Otra"
            bloqueNombre.classList.toggle('hidden', usaCatalogo);
            nombreLibre.required = !usaCatalogo;

            // Próxima fecha calculada, salvo que el veterinario la haya cambiado a mano
            if (usaCatalogo && intervalo > 0 && aplicacion.value && !proximaEditadaAMano) {
                proxima.value = sumarDias(aplicacion.value, intervalo);
            }

            ayuda.textContent = proxima.value
                ? (proximaEditadaAMano ? 'Fecha indicada a mano. ' : `Calculada: ${intervalo} días después de la aplicación. `) + describir(proxima.value)
                : '';

            // Dosis sugerida (desparasitaciones): solo si está vacía o tenía la sugerida anterior
            if (dosis) {
                const sugerida = opcion?.dataset.dosis || '';
                if (dosis.value === '' || dosis.value === dosisSugeridaAnterior) {
                    dosis.value = sugerida;
                }
                dosisSugeridaAnterior = sugerida;
                dosis.required = true;
            }
        };

        selector.addEventListener('change', () => { proximaEditadaAMano = false; actualizar(); });
        aplicacion.addEventListener('change', actualizar);
        proxima.addEventListener('input', () => { proximaEditadaAMano = proxima.value !== ''; actualizar(); });

        actualizar();
    })();
</script>