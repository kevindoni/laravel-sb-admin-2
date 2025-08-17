<?php

namespace App\Http\Controllers;

use App\Models\BillingPlan;
use Illuminate\Http\Request;

class BillingPlanController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $plans = BillingPlan::withCount(['hotspotUsers', 'vouchers'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('billing-plans.index', compact('plans'));
    }

    public function create()
    {
        return view('billing-plans.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:time,data,unlimited',
            'time_limit' => 'nullable|integer|min:1',
            'data_limit' => 'nullable|integer|min:1',
            'rate_limit' => 'nullable|string|max:50',
            'price' => 'required|numeric|min:0',
            'validity_period' => 'required|string|max:10',
            'description' => 'nullable|string',
        ]);

        BillingPlan::create($request->all());

        return redirect()->route('billing-plans.index')
            ->with('success', 'Paket billing berhasil ditambahkan!');
    }

    public function show(BillingPlan $billingPlan)
    {
        $billingPlan->load(['hotspotUsers', 'vouchers']);

        return view('billing-plans.show', compact('billingPlan'));
    }

    public function edit(BillingPlan $billingPlan)
    {
        return view('billing-plans.edit', compact('billingPlan'));
    }

    public function update(Request $request, BillingPlan $billingPlan)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:time,data,unlimited',
            'time_limit' => 'nullable|integer|min:1',
            'data_limit' => 'nullable|integer|min:1',
            'rate_limit' => 'nullable|string|max:50',
            'price' => 'required|numeric|min:0',
            'validity_period' => 'required|string|max:10',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $billingPlan->update($request->all());

        return redirect()->route('billing-plans.index')
            ->with('success', 'Paket billing berhasil diperbarui!');
    }

    public function destroy(BillingPlan $billingPlan)
    {
        if ($billingPlan->hotspotUsers()->count() > 0) {
            return back()->withErrors(['error' => 'Tidak dapat menghapus paket yang masih digunakan oleh user!']);
        }

        $billingPlan->delete();

        return redirect()->route('billing-plans.index')
            ->with('success', 'Paket billing berhasil dihapus!');
    }

    public function toggle(BillingPlan $billingPlan)
    {
        $billingPlan->update(['is_active' => !$billingPlan->is_active]);

        $status = $billingPlan->is_active ? 'diaktifkan' : 'dinonaktifkan';
        
        return response()->json([
            'success' => true,
            'message' => "Paket berhasil $status!",
            'is_active' => $billingPlan->is_active
        ]);
    }
}