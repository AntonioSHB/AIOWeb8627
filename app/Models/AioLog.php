<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AioLog extends Model
{
    use HasFactory;

    protected $table = 'AioLog'; // Nombre de la tabla

    protected $fillable = [
        'FechaHora',
        'CodPlaza',
        'UsuarioID',
        'TiendaID',
        'Caja',
        'Operacion',
        'DPVale',
        'Servicio',
        'Request',
        'Response',
        'Informacion',
    ]; // Campos que se pueden asignar masivamente

    public $timestamps = false; // Indica que el modelo no debe gestionar created_at y updated_at
}
