<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLES = ['superadmin', 'admin', 'pengguna'];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'google_id',
        'phone',
        'avatar',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'google_id',
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
        ];
    }

    /**
     * Alamat gambar avatar yang siap dipasang di atribut src.
     *
     * Avatar bisa berasal dari dua sumber: URL penuh milik Google, atau berkas
     * yang diunggah sendiri dan tersimpan relatif terhadap folder public.
     * Keduanya harus menghasilkan URL yang benar tanpa pemanggil perlu tahu
     * asalnya.
     */
    public function getAvatarUrlAttribute(): ?string
    {
        if (blank($this->avatar)) {
            return null;
        }

        return str_starts_with($this->avatar, 'http://') || str_starts_with($this->avatar, 'https://')
            ? $this->avatar
            : asset($this->avatar);
    }

    /**
     * Avatar berupa berkas milik sendiri, bukan tautan dari Google.
     *
     * Hanya berkas seperti ini yang boleh dihapus dari penyimpanan.
     */
    public function avatarDiunggahSendiri(): bool
    {
        return filled($this->avatar)
            && ! str_starts_with($this->avatar, 'http://')
            && ! str_starts_with($this->avatar, 'https://');
    }

    /**
     * Akun yang mendaftar lewat Google belum tentu punya kata sandi. Dipakai
     * untuk menentukan apakah formulir meminta kata sandi lama atau tidak.
     */
    public function punyaKataSandi(): bool
    {
        return filled($this->password);
    }

    /**
     * Akun tertaut Google, sehingga bisa masuk lewat tombol SSO.
     */
    public function tertautGoogle(): bool
    {
        return filled($this->google_id);
    }

    public function keranjangs(): HasMany
    {
        return $this->hasMany(Keranjang::class);
    }

    public function alamats(): HasMany
    {
        return $this->hasMany(Alamat::class);
    }

    public function pesanans(): HasMany
    {
        return $this->hasMany(Pesanan::class);
    }

    public function isSuperadmin(): bool
    {
        return $this->role === 'superadmin';
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, ['superadmin', 'admin']);
    }

    public function isPengguna(): bool
    {
        return $this->role === 'pengguna';
    }

    public function getRoleLabelAttribute(): string
    {
        return match ($this->role) {
            'superadmin' => 'Superadmin',
            'admin' => 'Admin',
            default => 'Pengguna',
        };
    }

    public function getRoleWarnaAttribute(): string
    {
        return match ($this->role) {
            'superadmin' => 'bg-purple-100 text-purple-700 ring-purple-200',
            'admin' => 'bg-blue-100 text-blue-700 ring-blue-200',
            default => 'bg-gray-100 text-gray-700 ring-gray-200',
        };
    }
}