@csrf

<div class="grid-2">
    @if(isset($supplier))
        <div>
            <label>Nº Proveedor</label>
            <input type="text" value="{{ $supplier->tax_id }}" readonly>
        </div>
    @endif

    <div>
        <label for="name">Nombre</label>
        <input id="name" type="text" name="name" value="{{ old('name', $supplier->name ?? '') }}" required>
        @error('name')
            <small class="error">{{ $message }}</small>
        @enderror
    </div>

    <div>
        <label for="email">Correo</label>
        <input id="email" type="email" name="email" value="{{ old('email', $supplier->email ?? '') }}">
        @error('email')
            <small class="error">{{ $message }}</small>
        @enderror
    </div>

    <div>
        <label for="phone">Telefono</label>
        <input id="phone" type="text" name="phone" value="{{ old('phone', $supplier->phone ?? '') }}">
        @error('phone')
            <small class="error">{{ $message }}</small>
        @enderror
    </div>

    <div>
        <label for="status">Estado</label>
        <select id="status" name="status">
            @php($currentStatus = old('status', $supplier->status ?? 'active'))
            <option value="active" @selected($currentStatus === 'active')>Activo</option>
            <option value="inactive" @selected($currentStatus === 'inactive')>Inactivo</option>
        </select>
        @error('status')
            <small class="error">{{ $message }}</small>
        @enderror
    </div>
</div>

<div class="actions-row">
    <button type="submit" class="btn">Guardar</button>
    <a href="{{ route('suppliers.index') }}" class="btn btn-outline">Cancelar</a>
</div>
