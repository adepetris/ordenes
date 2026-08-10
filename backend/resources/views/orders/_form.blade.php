@csrf

@php
    $orderSupplierId = isset($order) ? $order->supplier_id : '';
    $orderNotes = isset($order) ? $order->notes : '';
@endphp

<div class="grid-2">
    <div>
        <label for="supplier_id">Proveedor</label>
        <select id="supplier_id" name="supplier_id" required>
            <option value="">Selecciona un proveedor</option>
            @foreach($suppliers as $supplierOption)
                <option value="{{ $supplierOption->id }}" @selected((string) old('supplier_id', $orderSupplierId) === (string) $supplierOption->id)>
                    {{ $supplierOption->name }} ({{ $supplierOption->tax_id }})
                </option>
            @endforeach
        </select>
        @error('supplier_id')
            <small class="error">{{ $message }}</small>
        @enderror
    </div>
</div>

<input type="hidden" name="currency" value="USD">

<div>
    <label for="notes">Notas</label>
    <textarea id="notes" name="notes" rows="3">{{ old('notes', $orderNotes) }}</textarea>
    @error('notes')
        <small class="error">{{ $message }}</small>
    @enderror
</div>

<div class="items-head">
    <h2>Items</h2>
    <button type="button" class="btn btn-outline" id="add-item">Agregar item</button>
</div>

@error('items')
    <small class="error">{{ $message }}</small>
@enderror

<div id="items-list" class="items-list"></div>

<template id="item-template">
    <article class="item-row card">
        <div class="item-grid">
            <div>
                <label>Descripcion</label>
                <input type="text" data-field="description" required>
            </div>
            <div>
                <label>Cantidad</label>
                <input type="number" data-field="qty" step="0.01" min="0.01" required>
            </div>
            <div>
                <label>Unidad</label>
                <select data-field="unit" required>
                    @foreach(\App\Enums\PurchaseOrderUnit::cases() as $unit)
                        <option value="{{ $unit->value }}">{{ $unit->value }} - {{ $unit->label() }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <input type="hidden" data-field="unit_price" value="0">
        <input type="hidden" data-field="tax_rate" value="0">
        <button type="button" class="btn btn-outline remove-item">Quitar</button>
    </article>
</template>

<div class="actions-row">
    <button type="submit" class="btn">Guardar borrador</button>
    <a href="{{ route('orders.index') }}" class="btn btn-outline">Cancelar</a>
</div>

@php
    $initialItems = old('items');

    if (!is_array($initialItems)) {
        $initialItems = isset($order) ? $order->items->map(fn ($item) => [
            'description' => $item->description,
            'qty' => $item->qty,
            'unit' => $item->unit->value,
            'unit_price' => $item->unit_price,
            'tax_rate' => $item->tax_rate,
        ])->toArray() : [];
    }

    if ($initialItems === []) {
        $initialItems = [[
            'description' => '',
            'qty' => 1,
            'unit' => \App\Enums\PurchaseOrderUnit::Unit->value,
            'unit_price' => 0,
            'tax_rate' => 0,
        ]];
    }
@endphp

<script>
    (() => {
        const initialItems = @json($initialItems);
        const itemsList = document.getElementById('items-list');
        const addButton = document.getElementById('add-item');
        const template = document.getElementById('item-template');

        function reindexInputs() {
            const rows = itemsList.querySelectorAll('.item-row');

            rows.forEach((row, index) => {
                row.querySelectorAll('[data-field]').forEach((input) => {
                    input.name = `items[${index}][${input.dataset.field}]`;
                });
            });
        }

        function addRow(item = { description: '', qty: 1, unit: 'Un', unit_price: 0, tax_rate: 0 }) {
            const row = template.content.firstElementChild.cloneNode(true);

            row.querySelector('[data-field="description"]').value = item.description ?? '';
            row.querySelector('[data-field="qty"]').value = item.qty ?? 1;
            row.querySelector('[data-field="unit"]').value = item.unit ?? 'Un';
            row.querySelector('[data-field="unit_price"]').value = 0;
            row.querySelector('[data-field="tax_rate"]').value = 0;

            row.querySelector('.remove-item').addEventListener('click', () => {
                if (itemsList.children.length === 1) return;
                row.remove();
                reindexInputs();
            });

            itemsList.appendChild(row);
            reindexInputs();
        }

        addButton.addEventListener('click', () => addRow());

        initialItems.forEach((item) => addRow(item));
    })();
</script>
