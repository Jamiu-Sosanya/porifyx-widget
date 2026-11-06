<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ManualPayment;
use Illuminate\Http\Request;

/** Bank transfers waiting for the platform admin to confirm the money arrived. */
class PaymentController extends Controller
{
    public function index()
    {
        return view('admin.payments', [
            'pending' => ManualPayment::where('status', 'pending')->with('user', 'plan')->oldest()->get(),
            'recent'  => ManualPayment::where('status', '!=', 'pending')->with('user', 'plan')->latest('reviewed_at')->limit(30)->get(),
        ]);
    }

    public function approve(Request $request, ManualPayment $payment)
    {
        abort_unless($payment->status === 'pending', 409);
        $payment->approve($request->user());
        \App\Support\Audit::log('payment.approved', $payment, ['user' => $payment->user->email, 'plan' => $payment->plan->name, 'amount' => (string) $payment->amount]);

        return back()->with('success', "Approved. {$payment->user->name} is now on {$payment->plan->name}.");
    }

    public function reject(Request $request, ManualPayment $payment)
    {
        abort_unless($payment->status === 'pending', 409);
        $d = $request->validate(['note' => 'nullable|string|max:500']);
        $payment->update(['status' => 'rejected', 'note' => $d['note'] ?? null, 'reviewed_by' => $request->user()->id, 'reviewed_at' => now()]);
        \App\Support\Audit::log('payment.rejected', $payment, ['user' => $payment->user->email]);

        return back()->with('success', 'Payment rejected.');
    }
}
