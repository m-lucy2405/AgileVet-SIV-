import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import interactionPlugin from '@fullcalendar/interaction';
import esLocale from '@fullcalendar/core/locales/es';

// Horario de atención de la clínica
const HORA_APERTURA = '07:00';
const HORA_CIERRE = '18:00';
const DIAS_ATENCION = [1, 2, 3, 4, 5, 6];
const DURACION_CITA_MINUTOS = 30;

document.addEventListener('DOMContentLoaded', () => {
    const contenedor = document.getElementById('calendario-citas');

    if (!contenedor) return;

    const estados = JSON.parse(contenedor.dataset.estados || '{}');

    // Convierte cada cita que envía el controlador al formato de FullCalendar
    const aEvento = (cita) => {
        const estado = estados[cita.estado] ?? { nombre: cita.estado, color: '#6b7280' };

        return {
            id: cita.id,
            title: `${cita.mascota} · ${cita.motivo}`,
            start: cita.start,
            end: cita.end,
            backgroundColor: estado.color,
            borderColor: estado.color,
            extendedProps: {
                veterinarioId: cita.veterinario_id,
                mascotaId: cita.mascota_id,
                mascota: cita.mascota,
                motivo: cita.motivo,
                observaciones: cita.observaciones ?? '',
                cliente: cita.cliente,
                telefono: cita.telefono,
                veterinario: cita.veterinario,
                estado: estado.nombre,
                estadoClave: cita.estado,
            },
        };
    };

    const citas = JSON.parse(contenedor.dataset.citas || '[]').map(aEvento);
    const esPantallaPequena = () => window.innerWidth < 768;

    const textoAyuda = document.getElementById('fecha-seleccionada');
    const modal = document.getElementById('modal-cita');
    const modalNueva = document.getElementById('modal-nueva-cita');
    const ventanas = [modal, modalNueva];

    const formatoFecha = { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' };
    const formatoHora = { hour: '2-digit', minute: '2-digit' };

    // Formulario de nueva cita / edición y sus elementos
    const formulario = document.getElementById('form-nueva-cita');
    const campoFecha = formulario.elements.fecha_cita;
    const urlCitas = formulario.dataset.urlCitas;
    const campoMetodo = document.getElementById('metodo-cita');
    const campoCitaId = document.getElementById('cita-id');
    const etiquetaFormulario = document.getElementById('modal-nueva-cita-etiqueta');
    const tituloFormulario = document.getElementById('modal-nueva-cita-titulo');
    const botonGuardar = document.getElementById('boton-guardar-cita');

    // Acciones de la ventana de detalle
    const formEstado = document.getElementById('form-estado-cita');
    const formCancelar = document.getElementById('form-cancelar-cita');
    const selectorEstado = document.getElementById('estado-cita');
    const botonEditar = modal.querySelector('[data-editar-cita]');

    // Cita abierta en la ventana de detalle
    let citaSeleccionada = null;

    const sumarMinutos = (fecha, minutos) => new Date(fecha.getTime() + minutos * 60000);

    // Pasa una fecha al formato del input datetime-local (AAAA-MM-DDTHH:MM)
    const aCampoFecha = (fecha) => {
        const local = new Date(fecha.getTime() - fecha.getTimezoneOffset() * 60000);
        return local.toISOString().slice(0, 16);
    };

    // Abre o cierra cualquiera de las ventanas emergentes
    const abrirVentana = (ventana) => {
        ventana.classList.remove('hidden');
        ventana.classList.add('flex');
        document.body.classList.add('overflow-hidden');
    };

    const cerrarVentana = (ventana) => {
        ventana.classList.add('hidden');
        ventana.classList.remove('flex');
        document.body.classList.remove('overflow-hidden');
    };

    // Aviso dentro del formulario, para que se vea sin cerrar la ventana
    const avisoFormulario = document.createElement('div');
    avisoFormulario.setAttribute('role', 'alert');
    avisoFormulario.className = 'hidden rounded-lg border border-red-500/30 bg-red-50 px-4 py-3 text-sm text-red-600';
    formulario.prepend(avisoFormulario);

    const mostrarAviso = (mensaje) => {
        avisoFormulario.textContent = mensaje;
        avisoFormulario.classList.remove('hidden');
    };

    const ocultarAviso = () => {
        avisoFormulario.textContent = '';
        avisoFormulario.classList.add('hidden');
    };

    // Prepara el formulario para registrar una cita nueva (POST /citas)
    const modoNuevo = () => {
        formulario.action = urlCitas;
        campoMetodo.disabled = true;
        campoCitaId.value = '';
        etiquetaFormulario.textContent = 'Nueva cita';
        tituloFormulario.textContent = 'Agendar cita';
        botonGuardar.textContent = 'Guardar cita';
    };

    // Prepara el formulario para modificar una cita existente (PUT /citas/{id})
    const modoEditar = (evento) => {
        const datos = evento.extendedProps;

        formulario.action = `${urlCitas}/${evento.id}`;
        campoMetodo.disabled = false;
        campoCitaId.value = evento.id;
        etiquetaFormulario.textContent = 'Editar cita';
        tituloFormulario.textContent = 'Modificar cita';
        botonGuardar.textContent = 'Guardar cambios';

        formulario.elements.mascota_id.value = datos.mascotaId;
        formulario.elements.veterinario_id.value = datos.veterinarioId;
        campoFecha.value = aCampoFecha(evento.start);
        formulario.elements.motivo.value = datos.motivo ?? '';
        formulario.elements.observaciones.value = datos.observaciones ?? '';
    };

    // Abre la ventana con los datos de la cita elegida y prepara sus acciones
    const mostrarCita = (evento) => {
        const inicio = evento.start;
        const fin = evento.end ?? evento.start;
        const datos = {
            ...evento.extendedProps,
            horario: `${inicio.toLocaleDateString('es-SV', formatoFecha)}, de ${inicio.toLocaleTimeString('es-SV', formatoHora)} a ${fin.toLocaleTimeString('es-SV', formatoHora)}`,
        };

        modal.querySelectorAll('[data-campo]').forEach((campo) => {
            campo.textContent = datos[campo.dataset.campo] ?? '';
        });
        modal.querySelector('[data-campo="estado"]').style.backgroundColor = evento.backgroundColor;

        citaSeleccionada = evento;
        const estaCancelada = evento.extendedProps.estadoClave === 'cancelada';

        formEstado.action = `${urlCitas}/${evento.id}/estado`;
        formCancelar.action = `${urlCitas}/${evento.id}`;
        selectorEstado.value = estaCancelada ? 'pendiente' : evento.extendedProps.estadoClave;
        formCancelar.classList.toggle('hidden', estaCancelada);

        abrirVentana(modal);
        modal.querySelector('[data-cerrar-modal]').focus();
    };

    // Arma el texto de cada cita según la vista del calendario
    const dibujarCita = ({ event, timeText, view }) => {
        const { mascota, motivo } = event.extendedProps;

        const crearLinea = (texto, clase) => {
            const linea = document.createElement('div');
            linea.className = clase;
            linea.textContent = texto;
            return linea;
        };

        if (view.type === 'dayGridMonth') {
            const contenedorCita = document.createElement('div');
            contenedorCita.className = 'cita-mes';

            const punto = document.createElement('span');
            punto.className = 'cita-punto';
            punto.style.backgroundColor = event.backgroundColor;

            contenedorCita.append(punto, crearLinea(`${timeText} ${mascota}`, 'cita-texto'));
            return { domNodes: [contenedorCita] };
        }

        return {
            domNodes: [
                crearLinea(timeText, 'cita-hora'),
                crearLinea(`${mascota} · ${motivo}`, 'cita-texto'),
            ],
        };
    };

    // Texto completo que aparece al pasar el mouse sobre una cita
    const agregarDescripcion = ({ event, el, timeText }) => {
        const { mascota, motivo, cliente } = event.extendedProps;
        el.title = `${timeText} · ${mascota} · ${motivo}\nPropietario: ${cliente}`;
    };

    // Se cierran con la X, con Cancelar, al dar clic afuera o con Esc
    ventanas.forEach((ventana) => {
        ventana.querySelectorAll('[data-cerrar-modal]').forEach((boton) => {
            boton.addEventListener('click', () => cerrarVentana(ventana));
        });

        ventana.addEventListener('click', (evento) => {
            if (evento.target === ventana) cerrarVentana(ventana);
        });
    });

    document.addEventListener('keydown', (evento) => {
        if (evento.key !== 'Escape') return;
        ventanas.forEach((ventana) => {
            if (!ventana.classList.contains('hidden')) cerrarVentana(ventana);
        });
    });

    // Revisa si el veterinario ya tiene una cita en ese horario.
    // Las canceladas no cuentan, y al editar se ignora la propia cita.
    const horarioOcupado = (inicio, veterinarioId, citaExcluida = null) => {
        const fin = sumarMinutos(inicio, DURACION_CITA_MINUTOS);

        return calendario.getEvents().some((cita) => {
            if (
                cita.extendedProps.estadoClave === 'cancelada'
                || Number(cita.extendedProps.veterinarioId) !== Number(veterinarioId)
                || (citaExcluida !== null && String(cita.id) === String(citaExcluida))
            ) return false;
            const finCita = cita.end ?? sumarMinutos(cita.start, DURACION_CITA_MINUTOS);
            return inicio < finCita && fin > cita.start;
        });
    };

    // Abre el formulario con el día y la hora elegidos
    const seleccionarFecha = (info) => {
        const inicio = new Date(info.date);

        // En la vista de mes se propone la hora de apertura
        if (info.allDay) {
            const [hora, minutos] = HORA_APERTURA.split(':');
            inicio.setHours(hora, minutos, 0, 0);
        }

        const fecha = inicio.toLocaleDateString('es-SV', formatoFecha);
        const hora = inicio.toLocaleTimeString('es-SV', formatoHora);
        const horaTexto = inicio.toTimeString().slice(0, 5);

        if (inicio < new Date()) {
            textoAyuda.textContent = 'No se pueden agendar citas en fechas u horas pasadas.';
            return;
        }

        if (!DIAS_ATENCION.includes(inicio.getDay()) || horaTexto < HORA_APERTURA || horaTexto >= HORA_CIERRE) {
            textoAyuda.textContent = 'La clínica no atiende en ese horario.';
            return;
        }

        textoAyuda.textContent = `Seleccionaste el ${fecha} a las ${hora}.`;
        formulario.reset();
        ocultarAviso();
        modoNuevo();
        campoFecha.value = aCampoFecha(inicio);
        abrirVentana(modalNueva);
        formulario.elements.mascota_id.focus();
    };

    const calendario = new Calendar(contenedor, {
        plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
        locale: esLocale,
        initialView: esPantallaPequena() ? 'timeGridDay' : 'timeGridWeek',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay',
        },
        slotMinTime: `${HORA_APERTURA}:00`,
        slotMaxTime: `${HORA_CIERRE}:00`,
        slotDuration: '00:30:00',
        allDaySlot: false,
        businessHours: {
            daysOfWeek: DIAS_ATENCION,
            startTime: HORA_APERTURA,
            endTime: HORA_CIERRE,
        },
        nowIndicator: true,
        height: 'auto',
        events: citas,
        eventTimeFormat: { hour: 'numeric', minute: '2-digit', hour12: false },
        eventContent: dibujarCita,
        eventDidMount: agregarDescripcion,
        dayMaxEvents: 2,
        dateClick: seleccionarFecha,
        eventClick: (info) => mostrarCita(info.event),
        windowResize: () => {
            if (esPantallaPequena() && calendario.view.type === 'timeGridWeek') {
                calendario.changeView('timeGridDay');
            }
        },
    });

    calendario.render();

    // Recalcula el tamaño cada vez que cambia el ancho del recuadro (al aparecer la barra
    // de desplazamiento, al abrir o cerrar el menú, o al cambiar el tamaño de la ventana).
    // Sin esto, el calendario queda más ancho que su recuadro y se corta la columna del domingo.
    new ResizeObserver(() => calendario.updateSize()).observe(contenedor);

    // Botón "Editar" del detalle: abre el formulario con los datos de la cita
    botonEditar.addEventListener('click', () => {
        if (!citaSeleccionada) return;

        cerrarVentana(modal);
        formulario.reset();
        ocultarAviso();
        modoEditar(citaSeleccionada);
        abrirVentana(modalNueva);
        formulario.elements.mascota_id.focus();
    });

    // Botón "Cancelar cita" del detalle: pide confirmación antes de enviar
    formCancelar.addEventListener('submit', (evento) => {
        const confirmado = window.confirm('¿Seguro que deseas cancelar esta cita? El horario quedará libre.');

        if (!confirmado) evento.preventDefault();
    });

    // Antes de guardar, revisa si el veterinario ya tiene una cita en ese horario
    formulario.addEventListener('submit', (evento) => {
        const veterinarioId = Number(formulario.elements.veterinario_id.value);
        const citaExcluida = campoCitaId.value || null;

        if (!horarioOcupado(new Date(campoFecha.value), veterinarioId, citaExcluida)) return;

        evento.preventDefault();
        const mensaje = 'El veterinario seleccionado ya tiene una cita en ese horario.';
        textoAyuda.textContent = mensaje;
        mostrarAviso(mensaje);
    });
});