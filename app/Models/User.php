<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'account_number',
        'first_name',
        'last_name',
        'email',
        'password',
        'phone',
        'pesel',
        'birth_date',
        'address',
        'city',
        'postal_code',
        'country',
        'status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'birth_date' => 'date',
        'password' => 'hashed',
    ];

    /**
     * Relacja: Użytkownik ma jedno konto bankowe
     */
    public function account()
    {
        return $this->hasOne(Account::class);
    }

    /**
     * Relacja: Transakcje wysłane przez użytkownika
     */
    public function sentTransactions()
    {
        return $this->hasMany(Transaction::class, 'sender_id');
    }

    /**
     * Relacja: Transakcje otrzymane przez użytkownika
     */
    public function receivedTransactions()
    {
        return $this->hasMany(Transaction::class, 'recipient_id');
    }

    /**
     * Pobierz wszystkie transakcje użytkownika (wysłane + otrzymane)
     */
    public function allTransactions()
    {
        return Transaction::where('sender_id', $this->id)
            ->orWhere('recipient_id', $this->id)
            ->orderBy('executed_at', 'desc')
            ->get();
    }

    /**
     * Sprawdź czy użytkownik ma aktywne konto
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Pełne imię i nazwisko
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    /**
     * Generowanie numeru konta przy tworzeniu użytkownika
     */
    public static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
            if (empty($user->account_number)) {
                $user->account_number = self::generateAccountNumber();
            }
        });

        // Automatyczne tworzenie konta bankowego po utworzeniu użytkownika
        static::created(function ($user) {
            Account::create([
                'user_id' => $user->id,
                'balance' => 0.00,
                'currency' => 'PLN',
                'account_type' => 'checking',
            ]);
        });
    }

    /**
     * Generowanie unikalnego numeru konta (26 cyfr - format IBAN PL)
     */
    private static function generateAccountNumber(): string
    {
        do {
            // Format: PL + 2 cyfry kontrolne + 8 cyfr banku + 16 cyfr konta
            $accountNumber = 'PL' . rand(10, 99) . str_pad(rand(0, 99999999), 8, '0', STR_PAD_LEFT) . str_pad(rand(0, 9999999999999999), 16, '0', STR_PAD_LEFT);
        } while (self::where('account_number', $accountNumber)->exists());

        return $accountNumber;
    }
}
