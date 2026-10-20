import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import interactionPlugin from '@fullcalendar/interaction';
import esLocale from '@fullcalendar/core/locales/es';

// Horario de atención de la clínica
const HORA_APERTURA = '07:00';
const HORA_CIERRE = '18:00';
const DIAS_ATENCION = [1, 2, 3, 4, 5, 6];

document.addEventListener('DOMContentLoaded', () => {
    const contenedor = document.getElementById('calendario-citas');

    if (!contenedor) return;

    const citas = JSON.parse(contenedor.dataset.citas || '[]');
    const esPantallaPequena = () => window.innerWidth < 768;
    
    const textoAyuda = document.getElementById('fecha-seleccionada');
    const detalle = document.getElementById('detalle-cita');

    const formatoFecha = { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' };
    const formatoHora = { hour: '2-digit', minute: '2-digit' };

    // Muestra los datos de la cita elegida en el panel lateral
    const mostrarCita = (evento) => {
        const inicio = evento.start;
        const fin = evento.end ?? evento.start;
        const datos = {
            ...evento.extendedProps,
            horario: `${inicio.toLocaleDateString('es-SV', formatoFecha)}, de ${inicio.toLocaleTimeString('es-SV', formatoHora)} a ${fin.toLocaleTimeString('es-SV', formatoHora)}`,
        };

        detalle.querySelectorAll('[data-campo]').forEach((campo) => {
            campo.textContent = datos[campo.dataset.campo] ?? '';
        });

        textoAyuda.classList.add('hidden');
        detalle.classList.remove('hidden');
    };

    // Detecta el día u hora elegida (aquí se abrirá el modal de nueva cita)
    const seleccionarFecha = (info) => {
        const fecha = info.date.toLocaleDateString('es-SV', formatoFecha);
        const hora = info.allDay ? '' : ` a las ${info.date.toLocaleTimeString('es-SV', formatoHora)}`;

        detalle.classList.add('hidden');
        textoAyuda.classList.remove('hidden');
        textoAyuda.textContent = `Seleccionaste el ${fecha}${hora}.`;
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