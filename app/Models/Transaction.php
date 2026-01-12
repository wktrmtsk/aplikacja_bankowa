<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'transaction_number',
        'sender_id',
        'sender_account_number',
        'recipient_id',
        'recipient_account_number',
        'amount',
        'currency',
        'title',
        'description',
        'status',
        'type',
        'sender_balance_before',
        'sender_balance_after',
        'recipient_balance_before',
        'recipient_balance_after',
        'executed_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'sender_balance_before' => 'decimal:2',
        'sender_balance_after' => 'decimal:2',
        'recipient_balance_before' => 'decimal:2',
        'recipient_balance_after' => 'decimal:2',
        'executed_at' => 'datetime',
    ];

    /**
     * Relacja: Nadawca transakcji
     */
    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * Relacja: Odbiorca transakcji
     */
    public function recipient()
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    /**
     * Generowanie unikalnego numeru transakcji
     */
    public static function boot()
    {
        parent::boot();

        static::creating(function ($transaction) {
            if (empty($transaction->transaction_number)) {
                $transaction->transaction_number = self::generateTransactionNumber();
            }
        });
    }

    /**
     * Generowanie numeru transakcji
     */
    private static function generateTransactionNumber(): string
    {
        do {
            $transactionNumber = 'TRX' . date('Ymd') . str_pad(rand(0, 9999999), 7, '0', STR_PAD_LEFT);
        } while (self::where('transaction_number', $transactionNumber)->exists());

        return $transactionNumber;
    }

    /**
     * Formatowanie kwoty z walutą
     */
    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->amount, 2, ',', ' ') . ' ' . $this->currency;
    }

    /**
     * Czy transakcja jest zakończona pomyślnie
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Scope: Transakcje użytkownika (wysłane lub otrzymane)
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('sender_id', $userId)
            ->orWhere('recipient_id', $userId);
    }

    /**
     * Scope: Tylko zakończone transakcje
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }
}
