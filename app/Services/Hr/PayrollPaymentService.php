<?php

namespace App\Services\Hr;

use App\Models\PayrollPayment;
use App\Models\PayrollRecord;
use App\Models\PayrollRun;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PayrollPaymentService
{
    public function __construct(
        private readonly PayrollRunService $runService
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function recordPayment(PayrollRecord $record, array $data, ?User $actor = null): PayrollPayment
    {
        if (! in_array($record->status, ['approved', 'partially_paid', 'paid'], true)) {
            throw new PayrollException('Payroll must be approved before salary payment.');
        }

        $amount = round((float) $data['amount'], 2);
        if ($amount <= 0) {
            throw new PayrollException('Payment amount must be greater than zero.');
        }

        $outstanding = (float) $record->outstanding_amount;
        if ($amount > $outstanding + 0.001) {
            throw new PayrollException('Payment exceeds outstanding salary balance.');
        }

        return DB::transaction(function () use ($record, $data, $amount, $actor) {
            $payment = PayrollPayment::query()->create([
                'payroll_record_id' => $record->id,
                'payment_date' => $data['payment_date'],
                'amount' => $amount,
                'payment_method' => $data['payment_method'],
                'reference' => $data['reference'] ?? null,
                'notes' => $data['notes'] ?? null,
                'recorded_by' => $actor?->id,
            ]);

            $totalPaid = round((float) $record->payments()->sum('amount'), 2);
            $net = (float) $record->net_payable;
            $newOutstanding = max(0, round($net - $totalPaid, 2));

            $status = 'partially_paid';
            if ($newOutstanding <= 0.009) {
                $status = 'paid';
                $newOutstanding = 0;
            }

            $record->update([
                'amount_paid' => $totalPaid,
                'outstanding_amount' => $newOutstanding,
                'status' => $status,
            ]);

            $run = $record->run;
            $this->runService->syncRunTotals($run);
            $this->refreshRunPaymentStatus($run->fresh());

            return $payment->fresh();
        });
    }

    private function refreshRunPaymentStatus(PayrollRun $run): void
    {
        if ($run->status === 'cancelled') {
            return;
        }

        $records = $run->records()->get();
        if ($records->isEmpty()) {
            return;
        }

        $allPaid = $records->every(fn (PayrollRecord $r) => $r->status === 'paid');
        $anyPaid = $records->contains(fn (PayrollRecord $r) => (float) $r->amount_paid > 0);

        if ($allPaid) {
            $run->update(['status' => 'paid']);
        } elseif ($anyPaid) {
            $run->update(['status' => 'partially_paid']);
        }
    }
}