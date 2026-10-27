{{-- Campos del producto. Necesita $categorias y, al editar, $producto --}}
@php $producto = $producto ?? null; @endphp

<div class="grid gap-4 sm:grid-cols-2">
    <div>
        <label for="codigo" class="etiqueta">Código <span class="text-red-500">*</span></label>
        <input id="codigo" name="codigo" type="text" required maxlength="50" placeholder="Ej. MED-001"
            class="campo" value="{{ old('codigo', $producto?->codigo) }}">
        @error('codigo')
            <p class="error-campo">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="categoria_id" class="etiqueta">Categoría <span class="text-red-500">*</span></label>
        <select id="categoria_id" name="categoria_id" required class="campo">
            <option value="">Elige una categoría</option>
            @foreach ($categorias as $categoria)
                <option value="{{ $categoria->id }}" @selected(old('categoria_id', $producto?->categoria_id) == $categoria->id)>
                    {{ $categoria->nombre }}
                </option>
            @endforeach
        </select>
        @error('categoria_id')
            <p class="error-campo">{{ $message }}</p>
        @enderror
    </div>

    <div class="sm:col-span-2">
        <label for="nombre" class="etiqueta">Nombre <span class="text-red-500">*</span></label>
        <input id="nombre" name="nombre" type="text" required maxlength="150" placeholder="Ej. Amoxicilina 250 mg"
            class="campo" value="{{ old('nombre', $producto?->nombre) }}">
        @error('nombre')
            <p class="error-campo">{{ $message }}</p>
        @enderror
    </div>

    <div class="sm:col-span-2">
        <label for="descripcion" class="etiqueta">Descripción</label>
        <textarea id="descripcion" name="descripcion" rows="2" maxlength="5000"
            class="campo">{{ old('descripcion', $producto?->descripcion) }}</textarea>
        @error('descripcion')
            <p class="error-campo">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="unidad" class="etiqueta">Unidad <span class="text-red-500">*</span></label>
        <input id="unidad" name="unidad" type="text" required maxlength="30" list="unidades" placeholder="Ej. caja"
            class="campo" value="{{ old('unidad', $producto?->unidad) }}">
        <datalist id="unidades">
            @foreach (['unidad', 'caja', 'frasco', 'sobre', 'tableta', 'ampolla', 'bolsa', 'kg', 'litro'] as $unidad)
                <option value="{{ $unidad }}">
            @endforeach
        </datalist>
        @error('unidad')
            <p class="error-campo">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="fecha_vencimiento" class="etiqueta">Fecha de vencimiento</label>
        <input id="fecha_vencimiento" name="fecha_vencimiento" type="date" class="campo"
            value="{{ old('fecha_vencimiento', $producto?->fecha_vencimiento?->toDateString()) }}">
        @error('fecha_vencimiento')
            <p class="error-campo">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="precio_compra" class="etiqueta">Precio de compra ($) <span class="text-red-500">*</span></label>
        <input id="precio_compra" name="precio_compra" type="number" step="0.01" min="0" required
            class="campo" value="{{ old('precio_compra', $producto?->precio_compra) }}">
        @error('precio_compra')
            <p class="error-campo">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="precio_venta" class="etiqueta">Precio de venta ($) <span class="text-red-500">*</span></label>
        <input id="precio_venta" name="precio_venta" type="number" step="0.01" min="0" required
            class="campo" value="{{ old('precio_venta', $producto?->precio_venta) }}">
        @error('precio_venta')
            <p class="error-campo">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="stock_minimo" class="etiqueta">Stock mínimo <span class="text-red-500">*</span></label>
        <input id="stock_minimo" name="stock_minimo" type="number" step="1" min="0" required
            class="campo" value="{{ old('stock_minimo', $producto?->stock_minimo ?? 0) }}">
        <p class="mt-1 text-xs text-gray-500">Al llegar a esta cantidad, el producto se marca como "Stock bajo".</p>
        @error('stock_minimo')
            <p class="error-campo">{{ $message }}</p>
        @enderror
    </div>
</div>