<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'balance',
        'currency',
        'account_type',
        'opened_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'balance' => 'decimal:2',
        'opened_at' => 'datetime',
    ];

    /**
     * Relacja: Konto należy do użytkownika
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Dodaj środki do konta
     */
    public function addFunds(float $amount): void
    {
        $this->balance += $amount;
        $this->save();
    }

    /**
     * Odejmij środki z konta
     */
    public function deductFunds(float $amount): void
    {
        $this->balance -= $amount;
        $this->save();
    }

    /**
     * Sprawdź czy konto ma wystarczające środki
     */
    public function hasSufficientFunds(float $amount): bool
    {
        return $this->balance >= $amount;
    }

    /**
     * Formatowanie salda z walutą
     */
    public function getFormattedBalanceAttribute(): string
    {
        return number_format($this->balance, 2, ',', ' ') . ' ' . $this->currency;
    }
}
