<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\HotspotUser;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $query = Transaction::with(['user', 'hotspotUser', 'voucher']);

        // Filter by type
        if ($request->type) {
            $query->where('type', $request->type);
        }

        // Filter by status
        if ($request->status) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Search
        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('invoice_number', 'like', '%' . $request->search . '%')
                  ->orWhere('notes', 'like', '%' . $request->search . '%');
            });
        }

        $transactions = $query->latest()->paginate(20);

        // Statistics
        $totalRevenue = Transaction::where('status', 'completed')->sum('amount');
        $todayRevenue = Transaction::where('status', 'completed')
            ->whereDate('created_at', today())
            ->sum('amount');
        $pendingAmount = Transaction::where('status', 'pending')->sum('amount');

        $stats = [
            'total_revenue' => $totalRevenue,
            'today_revenue' => $todayRevenue,
            'pending_amount' => $pendingAmount,
            'total_transactions' => Transaction::count(),
        ];

        return view('transactions.index', compact('transactions', 'stats'));
    }

    public function show(Transaction $transaction)
    {
        $transaction->load(['user', 'hotspotUser', 'voucher']);

        return view('transactions.show', compact('transaction'));
    }

    public function topup(Request $request)
    {
        $request->validate([
            'hotspot_user_id' => 'required|exists:hotspot_users,id',
            'amount' => 'required|numeric|min:1000',
            'payment_method' => 'required|string',
            'notes' => 'nullable|string',
        ]);

        $hotspotUser = HotspotUser::findOrFail($request->hotspot_user_id);

        $transaction = Transaction::create([
            'user_id' => auth()->id(),
            'hotspot_user_id' => $request->hotspot_user_id,
            'type' => 'topup',
            'amount' => $request->amount,
            'payment_method' => $request->payment_method,
            'status' => 'pending',
            'invoice_number' => Transaction::generateInvoiceNumber(),
            'notes' => $request->notes,
        ]);

        // If manual payment, mark as completed immediately
        if ($request->payment_method === 'manual') {
            $transaction->update([
                'status' => 'completed',
                'paid_at' => now(),
            ]);

            $hotspotUser->increment('balance', $request->amount);
        }

        return response()->json([
            'success' => true,
            'message' => 'Top-up berhasil dibuat!',
            'transaction' => $transaction
        ]);
    }

    public function approve(Transaction $transaction)
    {
        if ($transaction->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Transaksi sudah diproses!'
            ], 400);
        }

        $transaction->update([
            'status' => 'completed',
            'paid_at' => now(),
        ]);

        // Process the transaction based on type
        if ($transaction->type === 'topup' && $transaction->hotspotUser) {
            $transaction->hotspotUser->increment('balance', $transaction->amount);
        }

        return response()->json([
            'success' => true,
            'message' => 'Transaksi berhasil disetujui!'
        ]);
    }

    public function reject(Transaction $transaction)
    {
        if ($transaction->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Transaksi sudah diproses!'
            ], 400);
        }

        $transaction->update(['status' => 'cancelled']);

        return response()->json([
            'success' => true,
            'message' => 'Transaksi berhasil ditolak!'
        ]);
    }
}