<?php
namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReporteLogsService
{
    public function getModulos()
    {
        try {
            DB::enableQueryLog();

            $query = DB::select('EXEC dbo.getCatalogoModulos');
                        // $query = [];
                        // $query = DB::connection('prueba')->select('exec dbo.getCatalogoModulos');
            // dd($results);
            if (empty($query)) {
                return ['error' => 'No se encontraron modulos registrados. Contacte al administrador.'];
                
            }
            // dd($query);
            return $query;
            // dd($modulos);
        } catch (\PDOException $t) {
            $queries = DB::getQueryLog();
            $lastQuery = end($queries); // Obtener la última consulta ejecutada
            $bindings = $lastQuery['bindings']; // Parámetros de la consulta
            $requestLog = $lastQuery['query']; // SQL de la consulta
            $error = [
                'status' => '0',
                'fecha' => date('Y-m-d H:i:s'),
                'descripcion' => 'Error en la función getTienda() del Modelo ReporteLogsService',
                'codigoError' => $t->getCode(),
                'msnError' => $t->getMessage(),
                'linea' => $t->getLine(),
                'archivo' => $t->getFile(),
                'requestLog' => $requestLog,
                'responseLog' => $bindings,
            ];
            Log::error('Error en la función getTienda() del Modelo ReporteLogsService', $error);
            return ['error' => 'Ocurrió un error al conectar con la base de datos SQL. Contacte al administrador.'];
        } catch (\Exception $e) {
            $queries = DB::getQueryLog();
            $lastQuery = end($queries); // Obtener la última consulta ejecutada
            $bindings = $lastQuery['bindings']; // Parámetros de la consulta
            $requestLog = $lastQuery['query']; // SQL de la consulta
            $error = [
                'status' => '0',
                'fecha' => date('Y-m-d H:i:s'),
                'descripcion' => 'Error en la función getModulos() del Servicio ReporteLogsService',
                'codigoError' => $t->getCode(),
                'msnError' => $t->getMessage(),
                'linea' => $t->getLine(),
                'archivo' => $t->getFile(),
                'requestLog' => $requestLog,
                'responseLog' => $bindings,
            ];
            Log::error('Error en la función getModulos() del Servicio ReporteLogsService', $error);
        }
    }
    public function getPlazas()
    {
        try {
            DB::enableQueryLog();

            $query = DB::select('EXEC dbo.getCatalogoPlazas');
                                    // $query = [];
            // $query = DB::connection('prueba')->select('exec dbo.getCatalogoPlazas');
            // dd($query);
            if (empty($query)) {
                return ['error' => 'No se encontraron modulos registrados. Contacte al administrador.'];
                
            }
            return $query;
            // dd($modulos);
        } catch (\PDOException $t) {
            $queries = DB::getQueryLog();
            $lastQuery = end($queries); // Obtener la última consulta ejecutada
            $bindings = $lastQuery['bindings']; // Parámetros de la consulta
            $requestLog = $lastQuery['query']; // SQL de la consulta
            $error = [
                'status' => '0',
                'fecha' => date('Y-m-d H:i:s'),
                'descripcion' => 'Error en la función getPlazas() del Servicio ReporteLogsService',
                'codigoError' => $t->getCode(),
                'msnError' => $t->getMessage(),
                'linea' => $t->getLine(),
                'archivo' => $t->getFile(),
                'requestLog' => $requestLog,
                'responseLog' => $bindings,
            ];
            Log::error('Error en la función getPlazas() del Servicio ReporteLogsService', $error);
            return ['error' => 'Ocurrió un error al conectar con la base de datos SQL. Contacte al administrador.'];
        } catch (\Exception $e) {
            $queries = DB::getQueryLog();
            $lastQuery = end($queries); // Obtener la última consulta ejecutada
            $bindings = $lastQuery['bindings']; // Parámetros de la consulta
            $requestLog = $lastQuery['query']; // SQL de la consulta
            $error = [
                'status' => '0',
                'fecha' => date('Y-m-d H:i:s'),
                'descripcion' => 'Error en la función getPlazas() del Modelo ReporteLogsService',
                'codigoError' => $t->getCode(),
                'msnError' => $t->getMessage(),
                'linea' => $t->getLine(),
                'archivo' => $t->getFile(),
                'requestLog' => $requestLog,
                'responseLog' => $bindings,
            ];
            Log::error('Error en la función getPlazas() del Modelo ReporteLogsService', $error);
        }
    }

    public function getTienda($codPlaza)
    {
        try {
            DB::enableQueryLog();
            $query = DB::select('EXEC dbo.getCatalogoTiendas ?', [$codPlaza]);
            // $query = [];
            // $result = DB::connection('prueba')->select('exec dbo.getCatalogoTiendas ?', [$codPlaza]);

            if(empty($query)){
                return response()->json(['error' => 'No se encontraron tiendas registradas. Contacte al administrador.'], 404);
            }
            // dd($query);
            return $query;
        } catch (\PDOException $t) {
            $queries = DB::getQueryLog();
            $lastQuery = end($queries); // Obtener la última consulta ejecutada
            $bindings = $lastQuery['bindings']; // Parámetros de la consulta
            $requestLog = $lastQuery['query']; // SQL de la consulta
            $error = [
                'status' => '0',
                'fecha' => date('Y-m-d H:i:s'),
                'descripcion' => 'Error en la función getTienda() del Servicio ReporteLogsService',
                'codigoError' => $t->getCode(),
                'msnError' => $t->getMessage(),
                'linea' => $t->getLine(),
                'archivo' => $t->getFile(),
                'requestLog' => $requestLog,
                'responseLog' => $bindings,
            ];
            Log::error('Error en la función getTienda() del Servicio ReporteLogsService', $error);
            return ['error' => 'Ocurrió un error al conectar con la base de datos SQL. Contacte al administrador.'];
        } catch (\PDOException $e) {
            $queries = DB::getQueryLog();
            $lastQuery = end($queries); // Obtener la última consulta ejecutada
            $bindings = $lastQuery['bindings']; // Parámetros de la consulta
            $requestLog = $lastQuery['query']; // SQL de la consulta
            $error = [
                'status' => '0',
                'fecha' => date('Y-m-d H:i:s'),
                'descripcion' => 'Error en la función getTienda() del Modelo ReporteLogsService',
                'codigoError' => $t->getCode(),
                'msnError' => $t->getMessage(),
                'linea' => $t->getLine(),
                'archivo' => $t->getFile(),
                'requestLog' => $requestLog,
                'responseLog' => $bindings,
            ];
            Log::error('Error en la función getTienda() del Modelo ReporteLogsService', $error);
        } catch (\Exception $e) {
            $queries = DB::getQueryLog();
            $lastQuery = end($queries); // Obtener la última consulta ejecutada
            $bindings = $lastQuery['bindings']; // Parámetros de la consulta
            $requestLog = $lastQuery['query']; // SQL de la consulta
            $error = [
                'status' => '0',
                'fecha' => date('Y-m-d H:i:s'),
                'descripcion' => 'Error en la función getPlazas() del Modelo ReporteLogsService',
                'codigoError' => $t->getCode(),
                'msnError' => $t->getMessage(),
                'linea' => $t->getLine(),
                'archivo' => $t->getFile(),
                'requestLog' => $requestLog,
                'responseLog' => $bindings,
            ];
            Log::error('Error en la función getPlazas() del Modelo ReporteLogsService', $error);
        }
    }
    public function ConsultarLogs()
    {
        try {
            DB::enableQueryLog();
            $query = DB::select('EXEC dbo.ConsultarLogs');
            // dd($query);
            // $query = [];
            // $query = DB::connection('prueba')->select('exec dbo.getCatalogoTiendas ?', [$codPlaza]);
            if(empty($query)){
                return response()->json(['error' => 'No se encontraron logs registrados. Contacte al administrador.'], 400);
            }
            // dd($query);
            return $query;
        } catch (\PDOException $t) {
            $queries = DB::getQueryLog();
            $lastQuery = end($queries); // Obtener la última consulta ejecutada
            $bindings = $lastQuery['bindings']; // Parámetros de la consulta
            $requestLog = $lastQuery['query']; // SQL de la consulta
            $error = [
                'status' => '0',
                'fecha' => date('Y-m-d H:i:s'),
                'descripcion' => 'Error en la función getTienda() del Modelo ReporteLogsService',
                'codigoError' => $t->getCode(),
                'msnError' => $t->getMessage(),
                'linea' => $t->getLine(),
                'archivo' => $t->getFile(),
                'requestLog' => $requestLog,
                'responseLog' => $bindings,
            ];
            Log::error('Error en la función getTienda() del Modelo ReporteLogsService', $error);
            
        } catch(\Exception $e){
            $queries = DB::getQueryLog();
            $lastQuery = end($queries); // Obtener la última consulta ejecutada
            $bindings = $lastQuery['bindings']; // Parámetros de la consulta
            $requestLog = $lastQuery['query']; // SQL de la consulta
            
            $error = [
                'status' => '0',
                'fecha' => date('Y-m-d H:i:s'),
                'descripcion' => 'Error en la función ConsultarLogs() del Modelo ReporteLogsService',
                'codigoError' => $e->getCode(),
                'msnError' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'requestLog' => $requestLog,
                'responseLog' => $bindings,
            ];
            Log::error('Error en la función ConsultarLogs() del Modelo ReporteLogsService', $error);
        
        } catch (\Throwable $e) {
            $queries = DB::getQueryLog();
            $lastQuery = end($queries); // Obtener la última consulta ejecutada
            $bindings = $lastQuery['bindings']; // Parámetros de la consulta
            $requestLog = $lastQuery['query']; // SQL de la consulta
            $error = [
                'status' => '0',
                'fecha' => date('Y-m-d H:i:s'),
                'descripcion' => 'Error en la función getPlazas() del Modelo ReporteLogsService',
                'codigoError' => $e->getCode(),
                'msnError' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'requestLog' => $requestLog,
                'responseLog' => $bindings,
            ];
            Log::error('Error en la función getPlazas() del Modelo ReporteLogsService', $error);        }
    }
    
}
