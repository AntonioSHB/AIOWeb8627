<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class Usuario extends Model
{
    use HasFactory;

    public function getCodPlazaAttribute()
{
    try{
        DB::enableQueryLog();
        $usuario = DB::table('Usuarios')
                ->where('UsuarioID', $this->UsuarioID)
                ->select('CodPlaza')
                ->first();
    }catch(\Throwable $t){
        $queries = DB::getQueryLog();
        $lastQuery = end($queries); // Obtener la última consulta ejecutada
        $bindings = $lastQuery['bindings']; // Parámetros de la consulta
        $requestLog = $lastQuery['query']; // SQL de la consulta

        $error = [
            'status'=> '0',
            'fecha'=> date('Y-m-d H:i:s'),
            'descripcion' => 'Error en la funcion getCodPlazaAttribute() del Modelo Usuario',            
            'codigoError'=> $t->getCode(),
            'msnError' => $t->getMessage(),
            'linea' => $t->getLine(),
            'archivo' => $t->getFile(),
            'requestLog' => $requestLog,
            'bindings' => $bindings
        ];    
        Log::error('Error en la funcion getCodPlazaAttribute() del Modelo Usuario', $error);
    }
    
    return $usuario->CodPlaza;
}

}
