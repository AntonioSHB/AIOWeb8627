<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoleRuta extends Model
{
    use HasFactory;

    protected $table = 'RoleRutas';  

    protected $fillable = [
        'id', 'ruta', 'roles', 'nmenu', 'orden', 'icono', 'estatus', 'fechaCreacion', 'rolMenu', 'datamodule', 'grupo', 'alias', 'grupoIcono'
    ];
}
