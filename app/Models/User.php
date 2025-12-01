<?php

namespace App\Models;

// 1. AGREGA ESTAS DOS LÍNEAS IMPORTANTE:
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles; // Asegúrate de importar esto también si usas Spatie

// 2. AGREGA "implements FilamentUser" AQUÍ:
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'city',
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
        ];
    }

    public function assignedIncidents(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Incident::class, 'incident_user')
            ->withPivot(['status', 'assigned_by', 'notes', 'assigned_at', 'accepted_at', 'rejected_at'])
            ->withTimestamps();
    }

    // 3. AGREGA ESTA FUNCIÓN AL FINAL (OBLIGATORIO PARA ENTRAR):
    public function canAccessPanel(Panel $panel): bool
    {
        // Opción A: Dejar entrar a cualquiera que tenga cuenta (Peligroso si el registro es público)
        return true; 
        
        // Opción B (Recomendada): Solo permitir si tiene correo de la empresa
        // return str_ends_with($this->email, '@intalnet.com');

        // Opción C (Si usas Roles): Solo permitir administradores
        // return $this->hasRole('admin');
    }
}