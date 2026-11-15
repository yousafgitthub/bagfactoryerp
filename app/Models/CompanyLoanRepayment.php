<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyLoanRepayment extends Model
{
    protected $fillable = [
        'company_loan_id',
        'repayment_date',
        'principal_amount',
        'interest_amount',
        'total_amount',
        'treasury_account_id',
        'gl_journal_entry_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'repayment_date' => 'date',
            'principal_amount' => 'float',
            'interest_amount' => 'float',
            'total_amount' => 'float',
        ];
    }

    public function loan(): BelongsTo
    {
        return $this->belongsTo(CompanyLoan::class, 'company_loan_id');
    }

    public function treasuryAccount(): BelongsTo
    {
        return $this->belongsTo(TreasuryAccount::class);
    }
}