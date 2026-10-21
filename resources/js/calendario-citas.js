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
                mascota: cita.mascota,
                motivo: cita.motivo,
                cliente: cita.cliente,
                telefono: cita.telefono,
                veterinario: cita.veterinario,
                estado: estado.nombre,
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

       // Abre la ventana con los datos de la cita elegida
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
            const contenedor = document.createElement('div');
            contenedor.className = 'cita-mes';

            const punto = document.createElement('span');
            punto.className = 'cita-punto';
            punto.style.backgroundColor = event.backgroundColor;

            contenedor.append(punto, crearLinea(`${timeText} ${mascota}`, 'cita-texto'));
            return { domNodes: [contenedor] };
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

        const formulario = document.getElementById('form-nueva-cita');
    const campoFecha = formulario.elements.fecha_hora;
    const sumarMinutos = (fecha, minutos) => new Date(fecha.getTime() + minutos * 60000);

    // Pasa una fecha al formato del input datetime-local (AAAA-MM-DDTHH:MM)
    const aCampoFecha = (fecha) => {
        const local = new Date(fecha.getTime() - fecha.getTimezoneOffset() * 60000);
        return local.toISOString().slice(0, 16);
    };

    // Revisa si ya hay una cita en ese horario (las canceladas no cuentan)
    const horarioOcupado = (inicio) => {
        const fin = sumarMinutos(inicio, DURACION_CITA_MINUTOS);

        return calendario.getEvents().some((cita) => {
            if (cita.extendedProps.estado === estados.cancelada.nombre) return false;
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

        if (horarioOcupado(inicio)) {
            textoAyuda.textContent = `Ya hay una cita el ${fecha} a las ${hora}.`;
            return;
        }

        textoAyuda.textContent = `Seleccionaste el ${fecha} a las ${hora}.`;
        formulario.reset();
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
});