<?php

namespace App\Logging;

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;
use Monolog\LogRecord;
use Illuminate\Support\Facades\DB;
use App\Services\Log;



class DatabaseLogHandler extends AbstractProcessingHandler
{
    protected function write(LogRecord $record): void
    {   
        // dd($record);
        if ($record->level < Logger::ERROR) {
            return; // Solo registramos errores o más graves
        }

        try {
            $data = $record->context;
            // dd($data,session()->all());
            $dpVale = $data['dpVale'] ?? 'NULL'; // Si 'dpVale' no está disponible, se asignará 'No disponible'
            $requestLog = json_encode($data['request'] ?? 'No hay request');
            $responseLog = json_encode($data['response'] ?? 'No hay response');
            
            $ip_asociado = \Request::ip();
            //  dd(session()->all());
            $codPlaza= session('CodPlaza');
            $usuarioID = session('IDuser') ?? session('UsuarioID');
            $idModulo = session('idModulo' ,7);
            // dd($usuarioID);
            $tiendaID= session('TiendaID');
            // dd($data, $ip_asociado, $codPlaza, $usuarioID, $tiendaID,$requestLog,$responseLog);
            // dd($usuarioID);
            // Insertar un registro simple en la tabla AioLog
            DB::statement('EXEC guardarLog 
            @IP = :ip,
            @FechaHora = :fechaHora,
            @CodPlaza = :codPlaza,
            @UsuarioID = :usuarioID,
            @TiendaID = :tiendaID,
            @Operacion = :operacion,
            @DPVale = :dpVale,
            @Modulo = :modulo,
            @Servicio = :servicio,
            @RequestLog = :requestLog,
            @ResponseLog = :responseLog',
        [
            'ip' => $ip_asociado,
            'fechaHora' => $data['fecha'] ? $data['fecha'] : now(),
            // 'fechaHora' => now(),
            'codPlaza' => $codPlaza,
            'usuarioID' => $usuarioID,//revisar
            'tiendaID' => $tiendaID,
            'operacion' => $data['descripcion'],
            'dpVale' => $data['dpVale'] ?? 'NULL',
            'modulo' => $idModulo,
            'servicio' => $data['msnError'],
            'requestLog' => array_key_exists('requestLog', $data) ? (is_array($data['requestLog']) ? json_encode($data['requestLog']) : $data['requestLog']) : 'No disponible',
            'responseLog' => array_key_exists('responseLog', $data) ? (is_array($data['responseLog']) ? json_encode($data['responseLog']) : $data['responseLog']) : 'No disponible',
            
                       
        ]);
        } catch (\Exception $e) {
            \Log::channel('single')->error('Error logging to database: ' . $e->getMessage());
        }
    }
}
