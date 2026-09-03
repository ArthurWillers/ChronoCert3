<?php

namespace App\Models;

use App\Casts\CpfCast;
use App\Traits\HasInitials;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'cpf', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasInitials, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'cpf' => CpfCast::class,
            'password' => 'hashed',
        ];
    }

    /**
     * @return HasMany<Affiliation, $this>
     */
    public function affiliations(): HasMany
    {
        return $this->hasMany(Affiliation::class);
    }
}
