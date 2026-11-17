{{-- Casilla para agendar la cita de refuerzo (marcada por defecto) --}}
<div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
    {{-- Si la casilla se desmarca, se envía 0 --}}
    <input type="hidden" name="agendar_refuerzo" value="0">
    <label class="flex cursor-pointer items-start gap-3">
        <input type="checkbox" name="agendar_refuerzo" value="1" class="mt-1"
            @checked(old('agendar_refuerzo', '1') === '1')>
        <span>
            <span class="block text-sm font-medium text-gray-800">Agendar cita de refuerzo</span>
            <span class="block text-xs text-gray-500">
                Se crea una cita contigo en la primera hora libre de la fecha indicada.
                Si cae domingo, se adelanta al sábado.
            </span>
        </span>
    </label>
</div>