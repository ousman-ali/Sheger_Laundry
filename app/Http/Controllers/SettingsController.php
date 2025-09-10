<?php

namespace App\Http\Controllers;

use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware(['role:Admin']);
    }

    public function editPenalty()
    {
        $rate = optional(SystemSetting::where('key','penalty_daily_rate')->first())->value;
        return view('settings.penalty', ['rate' => $rate]);
    }

    public function updatePenalty(Request $request)
    {
        $data = $request->validate([
            'penalty_daily_rate' => 'required|numeric|min:0',
            'penalty_grace_days' => 'nullable|integer|min:0',
        ]);
        // Core settings
        SystemSetting::updateOrCreate(['key' => 'penalty_daily_rate'], ['value' => (string)$data['penalty_daily_rate']]);
        SystemSetting::updateOrCreate(['key' => 'penalty_grace_days'], ['value' => (string)($data['penalty_grace_days'] ?? '0')]);
        SystemSetting::updateOrCreate(['key' => 'penalties_enabled'], ['value' => $request->boolean('penalties_enabled') ? '1' : '0']);

        // Service-level overrides
        foreach (\App\Models\Service::get(['id']) as $svc) {
            $key = 'penalty_rate_service_'.$svc->id;
            $val = $request->input($key);
            if ($val === null || $val === '') {
                // Clear override when empty
                \App\Models\SystemSetting::where('key', $key)->delete();
            } else {
                \App\Models\SystemSetting::updateOrCreate(['key' => $key], ['value' => (string)$val]);
            }
        }

        return back()->with('success', 'Penalty settings updated.');
    }

    public function editCompany()
    {
        $keys = [
            'company_name','company_address','company_phone','company_email','company_tin','company_vat_no',
            'company_logo_url','company_stamp_url','invoice_footer',
        ];
        $settings = [];
        foreach ($keys as $k) {
            $settings[$k] = SystemSetting::getValue($k, '');
        }
        return view('settings.company', ['settings' => $settings]);
    }

    public function updateCompany(Request $request)
    {
        $data = $request->validate([
            'company_name' => 'required|string|max:255',
            'company_address' => 'nullable|string|max:500',
            'company_phone' => 'nullable|string|max:50',
            'company_email' => 'nullable|email|max:255',
            'company_tin' => 'nullable|string|max:100',
            'company_vat_no' => 'nullable|string|max:100',
            'company_logo_url' => 'nullable|url|max:2048',
            'company_stamp_url' => 'nullable|url|max:2048',
            'company_logo_file' => 'nullable|image|mimes:jpg,jpeg,png,svg,webp|max:4096',
            'company_stamp_file' => 'nullable|image|mimes:jpg,jpeg,png,svg,webp|max:4096',
            'remove_logo' => 'nullable|boolean',
            'remove_stamp' => 'nullable|boolean',
            'invoice_footer' => 'nullable|string|max:500',
        ]);

        // Basic text settings
        foreach ([
            'company_name','company_address','company_phone','company_email',
            'company_tin','company_vat_no','invoice_footer'
        ] as $k) {
            if (array_key_exists($k, $data)) {
                SystemSetting::setValue($k, (string)($data[$k] ?? ''));
            }
        }

        // Handle removals
        if ($request->boolean('remove_logo')) {
            SystemSetting::setValue('company_logo_url', '');
        }
        if ($request->boolean('remove_stamp')) {
            SystemSetting::setValue('company_stamp_url', '');
        }

        // Handle file uploads (override URL if present)
        if ($request->hasFile('company_logo_file')) {
            $path = $request->file('company_logo_file')->store('company', 'public');
            $url = asset('storage/'.$path);
            SystemSetting::setValue('company_logo_url', $url);
        } elseif (!empty($data['company_logo_url'] ?? '')) {
            SystemSetting::setValue('company_logo_url', (string)$data['company_logo_url']);
        }

        if ($request->hasFile('company_stamp_file')) {
            $path = $request->file('company_stamp_file')->store('company', 'public');
            $url = asset('storage/'.$path);
            SystemSetting::setValue('company_stamp_url', $url);
        } elseif (!empty($data['company_stamp_url'] ?? '')) {
            SystemSetting::setValue('company_stamp_url', (string)$data['company_stamp_url']);
        }
        return back()->with('success', 'Company & Invoice settings updated.');
    }

    public function editOrderId()
    {
        $settings = [
            'order_id_prefix' => \App\Models\SystemSetting::getValue('order_id_prefix', config('shebar.order_id_prefix', 'ORD')),
            'order_id_format' => \App\Models\SystemSetting::getValue('order_id_format', config('shebar.order_id_format', 'Ymd')),
            'order_id_sequence_length' => (int) \App\Models\SystemSetting::getValue('order_id_sequence_length', config('shebar.order_id_sequence_length', config('shebar.order_id_suffix_length', 3))),
            'vip_order_id_prefix' => \App\Models\SystemSetting::getValue('vip_order_id_prefix', config('shebar.vip_order_id_prefix', 'VIP')),
        ];
        return view('settings.order-id', compact('settings'));
    }

    public function updateOrderId(Request $request)
    {
        $data = $request->validate([
            'order_id_prefix' => 'required|string|max:10',
            'order_id_format' => 'required|string|max:20',
            'order_id_sequence_length' => 'required|integer|min:1|max:10',
            'vip_order_id_prefix' => 'nullable|string|max:10',
        ]);

        \App\Models\SystemSetting::setValue('order_id_prefix', (string)$data['order_id_prefix']);
        \App\Models\SystemSetting::setValue('order_id_format', (string)$data['order_id_format']);
        \App\Models\SystemSetting::setValue('order_id_sequence_length', (string)$data['order_id_sequence_length']);
        if (array_key_exists('vip_order_id_prefix', $data)) {
            \App\Models\SystemSetting::setValue('vip_order_id_prefix', (string)$data['vip_order_id_prefix']);
        }

        return back()->with('success', 'Order ID settings updated.');
    }
}
