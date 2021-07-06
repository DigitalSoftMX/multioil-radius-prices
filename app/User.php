<?php

namespace App;

use App\Notifications\ResetPasswordNotification;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Contracts\Auth\MustVerifyEmail;

class User extends Authenticatable implements JWTSubject
{
    use Notifiable;
    // Relacion con el rol del usuario
    public function rol()
    {
        return $this->belongsTo(Role::class, 'role_id', 'id');
    }
    // Relacion con los clientes
    public function client()
    {
        return $this->hasOne(Client::class);
    }
    // Relacion con los depositos del cliente
    public function deposits()
    {
        return $this->hasMany(Deposit::class);
    }
    // Relacion con los depositos recibidos
    public function beneficiary()
    {
        return $this->hasMany(SharedBalance::class, 'beneficiary_id', 'id');
    }
    // Relacion con los compañeros cliente
    public function partners()
    {
        return $this->belongsToMany(Client::class, 'partners');
    }
    // Relacion con los deschapadores
    public function dispatcher()
    {
        return $this->hasOne(Dispatcher::class);
    }
    // Relacion con los turnos para el despachador
    public function times()
    {
        return $this->hasMany(RegisterTime::class);
    }

    // Relacion con las empresas
    /*public function company()
    {
        return $this->hasOne(AdminCompany::class);
    }*/
    // Relacion con la estación
    public function stations()
    {
        return $this->hasOne(AdminStation::class, 'user_id');
    }
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'first_surname', 'second_surname', 'email', 'password', 'phone', 'remember_token', 'active', 'role_id',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    /**
     * Send the password reset notification.
     *
     * @param  string  $token
     * @return void
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPasswordNotification($token));
    }
}
