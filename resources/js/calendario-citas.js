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
    const modal = document.getElementById('modal-cita');

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

        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.classList.add('overflow-hidden');
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

    // Cierra la ventana de la cita
    const cerrarModal = () => {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.classList.remove('overflow-hidden');
    };

    // Se cierra con la X, al dar clic afuera o con la tecla Esc
    modal.querySelector('[data-cerrar-modal]').addEventListener('click', cerrarModal);

    modal.addEventListener('click', (evento) => {
        if (evento.target === modal) cerrarModal();
    });

    document.addEventListener('keydown', (evento) => {
        if (evento.key === 'Escape' && !modal.classList.contains('hidden')) cerrarModal();
    });

    // Detecta el día u hora elegida (aquí se abrirá el modal de nueva cita)
    const seleccionarFecha = (info) => {
        const fecha = info.date.toLocaleDateString('es-SV', formatoFecha);
        const hora = info.allDay ? '' : ` a las ${info.date.toLocaleTimeString('es-SV', formatoHora)}`;
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