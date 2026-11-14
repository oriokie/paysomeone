<?php

namespace App\Http\Controllers\Office;

use App\Models\GatewaySetting;
use App\Support\Office\Gateways;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GatewayController extends OfficeController
{
    public function index(Request $request): View
    {
        $staff = $this->permit($request, 'gateways.view');
        $saved = GatewaySetting::query()->get()->keyBy('gateway');

        return view('office.gateways', [
            'staff' => $staff,
            'gateways' => Gateways::ALL,
            'saved' => $saved,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $staff = $this->permit($request, 'settings.manage');
        $data = $request->validate([
            'gateway' => ['required', 'string'],
            'enabled' => ['nullable', 'boolean'],
            'test_mode' => ['nullable', 'boolean'],
            'currency' => ['required', 'string', 'size:3'],
            'fee_percent' => ['required', 'numeric', 'min:0'],
            'fee_fixed' => ['required', 'numeric', 'min:0'],
        ]);
        abort_unless(isset(Gateways::ALL[$data['gateway']]), 404);
        GatewaySetting::query()->updateOrCreate(['gateway' => $data['gateway']], [
            'enabled' => $request->boolean('enabled'),
            'test_mode' => $request->boolean('test_mode'),
            'currency' => strtoupper($data['currency']),
            'fee_percent' => $data['fee_percent'],
            'fee_fixed' => $data['fee_fixed'],
        ]);
        $this->audit($staff, 'gateway.update', 'gateway', $data['gateway']);

        return back()->with('status', Gateways::ALL[$data['gateway']]['label'].' saved. Keys stay in the environment file.');
    }
}
