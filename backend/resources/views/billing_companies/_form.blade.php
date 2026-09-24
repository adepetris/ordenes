@csrf
<div>
    <label for="company_number">Nº Empresa</label>
    <input id="company_number" name="company_number" type="text" maxlength="50" value="{{ old('company_number', $company->company_number ?? '') }}" required>
    @error('company_number') <small class="error">{{ $message }}</small> @enderror
</div>
<div>
    <label for="name">Nombre de Empresa</label>
    <input id="name" name="name" type="text" maxlength="255" value="{{ old('name', $company->name ?? '') }}" required>
    @error('name') <small class="error">{{ $message }}</small> @enderror
</div>
<div>
    <label for="cuit">CUIT (11 dígitos, sin guiones)</label>
    <input id="cuit" name="cuit" type="text" inputmode="numeric" maxlength="11" pattern="[0-9]{11}" value="{{ old('cuit', $company->cuit ?? '') }}" required>
    @error('cuit') <small class="error">{{ $message }}</small> @enderror
</div>
<div class="actions-row">
    <button type="submit" class="btn">Guardar</button>
    <a href="{{ route('billing-companies.index') }}" class="btn btn-outline">Cancelar</a>
</div>
