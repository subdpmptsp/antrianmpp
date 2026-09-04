<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    public const ROLE_ADMIN = 'admin';

    public const ROLE_OPERATOR = 'operator';

    public const ROLE_LABELS = [
        self::ROLE_ADMIN => 'Admin',
        self::ROLE_OPERATOR => 'Petugas Layanan',
    ];

    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $attributes = [
        'is_active' => true,
    ];

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_active && in_array($this->role, array_keys(self::ROLE_LABELS), true);
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isOperator(): bool
    {
        return $this->role === self::ROLE_OPERATOR;
    }

    protected static function booted(): void
    {
        static::saving(function (User $user): void {
            if ($user->isDirty('password')) {
                $user->password_changed_at = now();
            }

            if ($user->role === self::ROLE_OPERATOR && $user->is_active && ! $user->counter_id) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'counter_id' => 'Akun petugas aktif wajib memiliki loket utama.',
                ]);
            }

            if ($user->role === self::ROLE_OPERATOR && $user->counter_id) {
                $counter = Counter::withoutGlobalScopes()->find($user->counter_id);
                if ($counter) {
                    $user->service_id = $counter->service_id;
                }
            }
        });
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'role',
        'is_active',
        'counter_id',
        'service_id',
        'password_changed_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'password_changed_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function counter()
    {
        return $this->belongsTo(Counter::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }
}
