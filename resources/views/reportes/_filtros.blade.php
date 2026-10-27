{{-- Encabezado común de los reportes. Necesita $ruta y, si usa fechas, $desde y $hasta --}}
<style>
    @media print {
        #sidebar, header, footer, .no-imprimir { display: none !important; }
        .lg\:pl-64 { padding-left: 0 !important; }
        body { background: #fff !important; color: #000 !important; }
        .bg-white { background: #fff !important; box-shadow: none !important; }
        .text-gray-500, .text-gray-600, .text-gray-800 { color: #000 !important; }
    }
</style>

<div class="no-imprimir mb-4 flex flex-wrap items-center justify-between gap-3">
    <a href="{{ route('reportes.index') }}" class="text-sm text-primario-300 hover:underline">&larr; Volver a reportes</a>
    <button type="button" onclick="window.print()" class="btn-secundario">Imprimir o guardar como PDF</button>
</div>

@if ($errors->any())
    <div role="alert" class="no-imprimir mb-4 rounded-lg border border-red-500/30 bg-red-50 px-4 py-3 text-sm text-red-600">
        @foreach ($errors->all() as $error)
            <p>{{ $error }}</p>
        @endforeach
    </div>
@endif

@isset($desde)
    <form method="GET" action="{{ route($ruta) }}"
        class="no-imprimir mb-6 flex flex-col gap-3 rounded-xl border border-gray-200 bg-white p-5 shadow-sm sm:flex-row sm:items-end">
        <div>
            <label for="desde" class="etiqueta">Desde</label>
            <input id="desde" type="date" name="desde" class="campo" value="{{ $desde->toDateString() }}">
        </div>
        <div>
            <label for="hasta" class="etiqueta">Hasta</label>
            <input id="hasta" type="date" name="hasta" class="campo" value="{{ $hasta->toDateString() }}">
        </div>
        <button type="submit" class="btn-primario">Generar reporte</button>
        <a href="{{ route($ruta) }}" class="btn-secundario">Mes actual</a>
    </form>

    <p class="mb-4 text-sm text-gray-600">
        Período: <strong>{{ $desde->format('d/m/Y') }}</strong> al <strong>{{ $hasta->format('d/m/Y') }}</strong>
    </p>
@endisset