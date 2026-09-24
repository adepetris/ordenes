<?php

namespace App\Http\Controllers;

use App\Models\BillingCompany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BillingCompanyController extends Controller
{
    public function index(): View
    {
        return view('billing_companies.index', [
            'companies' => BillingCompany::query()->orderBy('company_number')->paginate(10),
        ]);
    }

    public function create(): View
    {
        return view('billing_companies.create');
    }

    public function store(Request $request): RedirectResponse
    {
        BillingCompany::query()->create($this->validated($request));

        return redirect()->route('billing-companies.index')->with('status', 'Empresa creada correctamente.');
    }

    public function edit(BillingCompany $billingCompany): View
    {
        return view('billing_companies.edit', ['company' => $billingCompany]);
    }

    public function update(Request $request, BillingCompany $billingCompany): RedirectResponse
    {
        $billingCompany->update($this->validated($request, $billingCompany));

        return redirect()->route('billing-companies.index')->with('status', 'Empresa actualizada correctamente.');
    }

    private function validated(Request $request, ?BillingCompany $company = null): array
    {
        return $request->validate([
            'company_number' => ['required', 'string', 'max:50', Rule::unique('billing_companies')->ignore($company?->id)],
            'name' => ['required', 'string', 'max:255'],
            'cuit' => ['required', 'digits:11', Rule::unique('billing_companies')->ignore($company?->id)],
        ]);
    }
}
