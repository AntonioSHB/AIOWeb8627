<?php

namespace App\Models;

use GuzzleHttp\Psr7\Request;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\PreCargaPreDirectosService;


class Configuracion extends Model
{
    use HasFactory;
    protected $table = 'configuracion';
    public static function obtenerValorPorParametro($parametro)
{
    try{
        $results= Configuracion::where('Parametro', $parametro)->first()->Valor;

    // dd($results);
        // $results = DB::connection('prueba')->select('exec dbo.getCatalogoIdentificaciones');
            // $results =[];
    
            // Puedes agregar una verificación para los resultados vacíos si es necesario, como hicimos en el caso anterior
            if (empty($results)) {
                return ['error' => 'No se encontró la configuración. Contacte al administrador'];
            }
    return $results;
    } catch (\PDOException $e) {
        $queries = DB::getQueryLog();
        $lastQuery = end($queries); // Obtener la última consulta ejecutada
        $bindings = $lastQuery['bindings']; // Parámetros de la consulta
        $requestLog = $lastQuery['query']; // SQL de la consulta
        $error = [
            'status' => '0',
            'fecha' => date('Y-m-d H:i:s'),
            'descripcion' => 'Error en la función getTienda() del Modelo ReporteLogsService',
            'codigoError' => $e->getCode(),
            'msnError' => $e->getMessage(),
            'linea' => $e->getLine(),
            'archivo' => $e->getFile(),
            'requestLog' => $requestLog,
            'responseLog' => $bindings,
        ];
    Log::error('Error en la función getTienda() del Modelo ReporteLogsService', $error);
    return ['error' => 'Ocurrió un error al conectar con la base de datos SQL.'];

    }catch(\Throwable $t){
        $error = [
            'status' => '0',
            'fecha' => date('Y-m-d H:i:s'),
            'descripcion' => 'Error al obtener el valor del parametro '.$parametro.'.',
            'codigoError' => $t->getCode(),
            'msnError' => $t->getMessage(),
            'linea' => $t->getLine(),
            'archivo' => $t->getFile()
        ];    
        Log::error('Error al obtener el valor del parametro '.$parametro.' del Modelo Configuracion', $error);
        return ['error' => 'Ocurrió un error al conectar con la base de datos SQL.'];
    }
}
public static function getCatalogoBancos()
{
    try{
        
        DB::enableQueryLog();
        $query = DB::select('exec dbo.getCatalogoBancos');
        // dd($query);
        // $query =[];
        // $query = DB::connection('prueba')->select('exec dbo.getCatalogoBancos');

        if (empty($query)) {
            return ['error' => 'No se encontró la configuración. Contacte al administrador'];
        }
        return $query;
    } catch (\PDOException $e) {
        $queries = DB::getQueryLog();
        $lastQuery = end($queries); // Obtener la última consulta ejecutada
        $bindings = $lastQuery['bindings']; // Parámetros de la consulta
        $requestLog = $lastQuery['query']; // SQL de la consulta
        $error = [
            'status' => '0',
            'fecha' => date('Y-m-d H:i:s'),
            'descripcion' => 'Error en la funcióngetCatalogoBancos',
            'codigoError' => $e->getCode(),
            'msnError' => $e->getMessage(),
            'linea' => $e->getLine(),
            'archivo' => $e->getFile(),
            'requestLog' => $requestLog,
            'responseLog' => $bindings,
        ];
    Log::error('Error en la función getCatalogoBancos', $error);
    return ['error' => 'Ocurrió un error al conectar con la base de datos SQL.'];
    }catch(\Throwable $t){
        $queries = DB::getQueryLog();
        $lastQuery = end($queries); // Obtener la última consulta ejecutada
        $bindings = $lastQuery['bindings']; // Parámetros de la consulta
        $requestLog = $lastQuery['query']; // SQL de la consulta
        $error = [
            'status' => '0',
            'fecha' => date('Y-m-d H:i:s'),
            'descripcion' => 'Error en la funcion getCatalogoBancos().',
            'codigoError' => $t->getCode(),
            'msnError' => $t->getMessage(),
            'linea' => $t->getLine(),
            'archivo' => $t->getFile(),
            'requestLog' => $requestLog,
            'responseLog' => $bindings
        ];    
        Log::error('Error en la funcion getCatalogoBancos() del Modelo Configuracion', $error);
        return ['error' => 'Ocurrió un error al conectar con la base de datos SQL.'];
    }    
}
public static function getBinesBancarios()
{
    try{
        DB::enableQueryLog();
        $results = DB::select('exec dbo.getCatalogoBines');
        // $results =[];
            // $results = DB::connection('prueba')->select('exec dbo.getCatalogoBines');
        if(empty($results)){
            return ['error' => 'No se encontraron bines registrados para la plaza. Contacte al administrador.'];
        }
        return $results;
    } catch (\PDOException $e) {
        $errorMessage = PreCargaPreDirectosService::handleException($e, 'Ocurrió un error al conectar con la base de datos SQL en getBinesBancarios.','Ocurrió un error al conectar con la base de datos SQL.');
        return $errorMessage;

    }catch(\Throwable $t){
        $queries = DB::getQueryLog();
        $lastQuery = end($queries); // Obtener la última consulta ejecutada
        $bindings = $lastQuery['bindings']; // Parámetros de la consulta
        $requestLog = $lastQuery['query']; // SQL de la consulta
        $error = [
            'status' => '0',
            'fecha' => date('Y-m-d H:i:s'),
            'descripcion' => 'Error en la funcion getBinesBancarios().',
            'codigoError' => $t->getCode(),
            'msnError' => $t->getMessage(),
            'linea' => $t->getLine(),
            'archivo' => $t->getFile(),
            'requestLog' => $requestLog,
            'responseLog' => $bindings
        ];    
        Log::error('Error en la funcion getBinesBancarios() del Modelo Configuracion', $error);
        return ['error' => 'Error al obtener Bines Bancarios. Contacte al administrador.'];

    }    
}
public static function getCatalogoIdentificaciones()
{
    try{
        DB::enableQueryLog();
        $results = DB::select('exec dbo.getCatalogoIdentificaciones');
        // $results =[];
        // $results = db::connection('prueba')->select('exec dbo.getCatalogoIdentificaciones');
        // dd($results);
        if(empty($results)){
            return ['error' => 'No se encontraron Catalogos de Identificaciones registrados para la plaza. Contacte al administrador.'];
        }
        return $results;
    } catch (\PDOException $e) {
        $errorMessage = PreCargaPreDirectosService::handleException($e, 'Ocurrió un error al conectar con la base de datos SQL.','Ocurrió un error al conectar con la base de datos SQL.');
        return $errorMessage;

    }catch(\Throwable $t){
        $queries = DB::getQueryLog();
        $lastQuery = end($queries); // Obtener la última consulta ejecutada
        $bindings = $lastQuery['bindings']; // Parámetros de la consulta
        $requestLog = $lastQuery['query']; // SQL de la consulta
        $error = [
            'status' => '0',
            'fecha' => date('Y-m-d H:i:s'),
            'descripcion' => 'Error en la funcion getCatalogoIdentificaciones().',
            'codigoError' => $t->getCode(),
            'msnError' => $t->getMessage(),
            'linea' => $t->getLine(),
            'archivo' => $t->getFile(),
            'requestLog' => $requestLog,
            'responseLog' => $bindings
        ];    
        Log::error('Error en la funcion getCatalogoIdentificaciones() del Modelo Configuracion', $error);
    }    
}

public static function getFechaServidor()
{
    try{
        DB::enableQueryLog();
        $results = DB::select('exec dbo.FechaServidorSel');
        // dd($result);
        //  $results =[];
        // $results = db::connection('prueba')->select('exec dbo.getCatalogoIdentificaciones');
        // dd($results);
        if(empty($results)){
            return ['error' => 'No se pudo obtener la fecha del servidor. Contacte al administrador.'];
        }
        return $results;
    } catch (\PDOException $e) {
        $errorMessage = PreCargaPreDirectosService::handleException($e, 'Ocurrió un error al conectar con la base de datos SQL.','Ocurrió un error al conectar con la base de datos SQL.');
        return $errorMessage;

    }catch(\Throwable $t){
        $queries = DB::getQueryLog();
        $lastQuery = end($queries); // Obtener la última consulta ejecutada
        $bindings = $lastQuery['bindings']; // Parámetros de la consulta
        $requestLog = $lastQuery['query']; // SQL de la consulta
        $error = [
            'status' => '0',
            'fecha' => date('Y-m-d H:i:s'),
            'descripcion' => 'Error en la funcion getFechaServidor().',
            'codigoError' => $t->getCode(),
            'msnError' => $t->getMessage(),
            'linea' => $t->getLine(),
            'archivo' => $t->getFile(),
            'requestLog' => $requestLog,
            'responseLog' => $bindings
        ];    
        Log::error('Error en la funcion getFechaServidor() del Modelo Configuracion', $error);
    }    
    $date = new \DateTime($result[0]->datetime);
    return $date->format('Y-m-d'); 
}

// public function validarTarjeta($request)
// {
//     $tarjeta = $request->input('tarjeta-clabe');
//     try{
//         DB::enableQueryLog();
//         $dias = Configuracion::obtenerValorPorParametro('ValidaTarjetaDias');
//     }catch(\Throwable $t){
//         $queries = DB::getQueryLog();
//         $lastQuery = end($queries); // Obtener la última consulta ejecutada
//         $bindings = $lastQuery['bindings']; // Parámetros de la consulta
//         $requestLog = $lastQuery['query']; // SQL de la consulta
//         $error = [
//             'status' => '0',
//             'fecha' => date('Y-m-d H:i:s'),
//             'descripcion' => 'Error en la funcion validarTarjeta().',
//             'codigoError' => $t->getCode(),
//             'msnError' => $t->getMessage(),
//             'linea' => $t->getLine(),
//             'archivo' => $t->getFile(),
//             'requestLog' => $requestLog,
//             'responseLog' => $bindings
//         ];    
//         Log::error('Error en la funcion validarTarjeta() del Modelo Configuracion', $error);
//     }    

//     try{
//         DB::enableQueryLog();
//         // Llamada al SP
//         $result = DB::select("exec dbo.ValidaPrestamoxTarjetaDias ?, ?", [$tarjeta, $dias]);
//         $dias = Configuracion::obtenerValorPorParametro('ValidaTarjetaDias');
    
//         // Llamada al SP
//         $result = DB::select("exec dbo.ValidaPrestamoxTarjetaDias ?, ?", [$tarjeta, $dias]);

//         return $result;

//     }catch(\Throwable $t){
//         $queries = DB::getQueryLog();
//         $lastQuery = end($queries); // Obtener la última consulta ejecutada
//         $bindings = $lastQuery['bindings']; // Parámetros de la consulta
//         $requestLog = $lastQuery['query']; // SQL de la consulta
//         $error = [
//             'status' => '0',
//             'fecha' => date('Y-m-d H:i:s'),
//             'descripcion' => 'Error en la funcion validarTarjeta().',
//             'codigoError' => $t->getCode(),
//             'msnError' => $t->getMessage(),
//             'linea' => $t->getLine(),
//             'archivo' => $t->getFile(),
//             'requestLog' => $requestLog,
//             'responseLog' => $bindings
//         ];    
//         Log::error('Error en la funcion validarTarjeta() del Modelo Configuracion', $error);
//     }   
    
// }

public static function BuscaPrestamosFallidos($CodPlaza, $TiendaID, $PrestamoID)
{   
    try{
        DB::enableQueryLog();
        //return DB::table('dbo.FinancieroDigital')->get();
        // dd($CodPlaza, $TiendaID, $PrestamoID);
        $results= DB::select("exec dbo.BuscaPrestamosFallidos ?, ?, ?", [$CodPlaza, $TiendaID, $PrestamoID]);        
                // dd($results);
        //  $results =[];
        // $results = db::connection('prueba')->select('exec dbo.getCatalogoIdentificaciones');
        // dd($results);
        if(empty($results)){
            return ['error' => 'No se encontraron prestamos fallidos. Contacte al administrador.'];
        }
        return $results;
    }catch(\Throwable $t){
        $queries = DB::getQueryLog();
        $lastQuery = end($queries); // Obtener la última consulta ejecutada
        $bindings = $lastQuery['bindings']; // Parámetros de la consulta
        $requestLog = $lastQuery['query']; // SQL de la consulta
        $error = [
            'status' => '0',
            'fecha' => date('Y-m-d H:i:s'),
            'descripcion' => 'Error en la funcion BuscaPrestamosFallidos().',
            'codigoError' => $t->getCode(),
            'msnError' => $t->getMessage(),
            'linea' => $t->getLine(),
            'archivo' => $t->getFile(),
            'requestLog' => $requestLog,
            'responseLog' => $bindings
        ];    
        Log::error('Error en la funcion BuscaPrestamosFallidos() del Modelo Configuracion', $error);
        return false;
    }
}

public static function BuscarPrestamoPorID($PrestamoID)
{   
    try{
        DB::enableQueryLog();
        $results= DB::select("exec dbo.BuscaPrestamosFallidos ?, ?, ?", [null, null, $PrestamoID]);
                        // dd($result);
        //  $results =[];
        // $results = db::connection('prueba')->select('exec dbo.getCatalogoIdentificaciones');
        // dd($results);
        if(empty($results)){
            return ['error' => 'No se encontraron prestamos fallidos. Contacte al administrador.'];
        }
        return $results;
    }catch(\Throwable $t){
        $queries = DB::getQueryLog();
        $lastQuery = end($queries); // Obtener la última consulta ejecutada
        $bindings = $lastQuery['bindings']; // Parámetros de la consulta
        $requestLog = $lastQuery['query']; // SQL de la consulta
        $error = [
            'status' => '0',
            'fecha' => date('Y-m-d H:i:s'),
            'descripcion' => 'Error en la funcion BuscarPrestamoPorID().',
            'codigoError' => $t->getCode(),
            'msnError' => $t->getMessage(),
            'linea' => $t->getLine(),
            'archivo' => $t->getFile(),
            'requestLog' => $requestLog,
            'responseLog' => $bindings
        ];    
        Log::error('Error en la funcion BuscarPrestamoPorID() del Modelo Configuracion', $error);
    }
}

public static function getCatalogoServicios($CodPlaza)
{
    try{
        DB::enableQueryLog();
        $results = DB::select("exec dbo.getCatalogoServicios ?", [$CodPlaza]);  
                                // dd($result);
        //  $results =[];
        // $results = db::connection('prueba')->select('exec dbo.getCatalogoIdentificaciones');
        // dd($results);
        if(empty($results)){
            return ['error' => 'No se encontraron prestamos fallidos. Contacte al administrador.'];
        }
        return $results;
         
    }catch(\Throwable $t){
        $queries = DB::getQueryLog();
        $lastQuery = end($queries); // Obtener la última consulta ejecutada
        $bindings = $lastQuery['bindings']; // Parámetros de la consulta
        $requestLog = $lastQuery['query']; // SQL de la consulta
        $error = [
            'status' => '0',
            'fecha' => date('Y-m-d H:i:s'),
            'descripcion' => 'Error en la funcion getCatalogoServicios().',
            'codigoError' => $t->getCode(),
            'msnError' => $t->getMessage(),
            'linea' => $t->getLine(),
            'archivo' => $t->getFile(),
            'requestLog' => $requestLog,
            'responseLog' => $bindings
        ];    
        Log::error('Error en la funcion getCatalogoServicios() del Modelo Configuracion', $error);
    }
}

public static function ConfValidaTarjetaDias(){
    
    try{
        DB::enableQueryLog();
        return DB::table('dbo.Configuracion')
            ->select('Valor')
            ->where('Parametro', '=', 'ValidaTarjetaDias')
            ->get()
            ->first();
    }catch(\Throwable $t){
        $queries = DB::getQueryLog();
        $lastQuery = end($queries); // Obtener la última consulta ejecutada
        $bindings = $lastQuery['bindings']; // Parámetros de la consulta
        $requestLog = $lastQuery['query']; // SQL de la consulta
        $error = [
            'status' => '0',
            'fecha' => date('Y-m-d H:i:s'),
            'descripcion' => 'Error en la funcion ConfValidaTarjetaDias().',
            'codigoError' => $t->getCode(),
            'msnError' => $t->getMessage(),
            'linea' => $t->getLine(),
            'archivo' => $t->getFile(),
            'requestLog' => $requestLog,
            'responseLog' => $bindings
        ];    
        Log::error('Error en la funcion ConfValidaTarjetaDias() del Modelo Configuracion', $error);
    }    
}

public static function ConfLimiteReproceso(){
    
    try{
        DB::enableQueryLog();
        return DB::table('dbo.Configuracion')
            ->select('Valor')
            ->where('Parametro', '=', 'LimiteReproceso')
            ->get()
            ->first();
    }catch(\Throwable $t){
        $queries = DB::getQueryLog();
        $lastQuery = end($queries); // Obtener la última consulta ejecutada
        $bindings = $lastQuery['bindings']; // Parámetros de la consulta
        $requestLog = $lastQuery['query']; // SQL de la consulta
        $error = [
            'status' => '0',
            'fecha' => date('Y-m-d H:i:s'),
            'descripcion' => 'Error en la funcion ConfLimiteReproceso().',
            'codigoError' => $t->getCode(),
            'msnError' => $t->getMessage(),
            'linea' => $t->getLine(),
            'archivo' => $t->getFile(),
            'requestLog' => $requestLog,
            'responseLog' => $bindings
        ];    
        Log::error('Error en la funcion ConfLimiteReproceso() del Modelo Configuracion', $error);
    }    
}

public static function getNumReference(){
    
    try{
        DB::enableQueryLog();
        return DB::table('dbo.Configuracion')
            ->select('Valor')
            ->where('Parametro', '=', 'numReference')
            ->get()
            ->first();
    }catch(\Throwable $t){
        $queries = DB::getQueryLog();
        $lastQuery = end($queries); // Obtener la última consulta ejecutada
        $bindings = $lastQuery['bindings']; // Parámetros de la consulta
        $requestLog = $lastQuery['query']; // SQL de la consulta
        $error = [
            'status' => '0',
            'fecha' => date('Y-m-d H:i:s'),
            'descripcion' => 'Error en la funcion getNumReference().',
            'codigoError' => $t->getCode(),
            'msnError' => $t->getMessage(),
            'linea' => $t->getLine(),
            'archivo' => $t->getFile(),
            'requestLog' => $requestLog,
            'responseLog' => $bindings
        ];    
        Log::error('Error en la funcion ConfLimiteReproceso() del Modelo Configuracion', $error);
    }    
}

public static function ValidaPrestamoxTarjetaDias($tarjeta, $dias)
{   
    try{
        DB::enableQueryLog();
        return DB::select("exec dbo.ValidaPrestamoxTarjetaDias ?, ?", [$tarjeta, $dias]);
    }catch(\Throwable $t){
        $queries = DB::getQueryLog();
        $lastQuery = end($queries); // Obtener la última consulta ejecutada
        $bindings = $lastQuery['bindings']; // Parámetros de la consulta
        $requestLog = $lastQuery['query']; // SQL de la consulta
        $error = [
            'status' => '0',
            'fecha' => date('Y-m-d H:i:s'),
            'descripcion' => 'Error en la funcion ValidaPrestamoxTarjetaDias().',
            'codigoError' => $t->getCode(),
            'msnError' => $t->getMessage(),
            'linea' => $t->getLine(),
            'archivo' => $t->getFile(),
            'requestLog' => $requestLog,
            'responseLog' => $bindings
        ];    
        Log::error('Error en la funcion ValidaPrestamoxTarjetaDias() del Modelo Configuracion', $error);
    }    
}

public static function getCatalogoSociedades($SociedadID)
{
    try{
        DB::enableQueryLog();
        return DB::table('dbo.CatalogoSociedades')
                ->select('Sociedad')
                ->where('SociedadID', '=', $SociedadID)
                ->get();
    }catch(\Throwable $t){
        $queries = DB::getQueryLog();
        $lastQuery = end($queries); // Obtener la última consulta ejecutada
        $bindings = $lastQuery['bindings']; // Parámetros de la consulta
        $requestLog = $lastQuery['query']; // SQL de la consulta
        $error = [
            'status' => '0',
            'fecha' => date('Y-m-d H:i:s'),
            'descripcion' => 'Error en la funcion getCatalogoSociedades().',
            'codigoError' => $t->getCode(),
            'msnError' => $t->getMessage(),
            'linea' => $t->getLine(),
            'archivo' => $t->getFile(),
            'requestLog' => $requestLog,
            'responseLog' => $bindings
        ];    
        Log::error('Error en la funcion getCatalogoSociedades() del Modelo Configuracion', $error);
    }
}

public static function RegistrarIntento($PrestamoID, $DPVale, $Banco, $TarjetaClabe, $Intento, $NuevoBanco,
                            $NuevaTarjetaClabe, $UsuarioID, $ServicioID, $BancoID, $EstatusDispersion)
{
    try{
        DB::enableQueryLog();
        return DB::select("exec dbo.RegistrarIntento ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?", [
            $PrestamoID,
            $DPVale,
            $Banco,
            $TarjetaClabe,
            $Intento,
            $NuevoBanco,
            $NuevaTarjetaClabe,
            $UsuarioID,
            $ServicioID,
            $BancoID,
            $EstatusDispersion
        ]);   
    }catch(\Throwable $t){
        $queries = DB::getQueryLog();
        $lastQuery = end($queries); // Obtener la última consulta ejecutada
        $bindings = $lastQuery['bindings']; // Parámetros de la consulta
        $requestLog = $lastQuery['query']; // SQL de la consulta
        $error = [
            'status' => '0',
            'fecha' => date('Y-m-d H:i:s'),
            'descripcion' => 'Error en la funcion RegistrarIntento().',
            'codigoError' => $t->getCode(),
            'msnError' => $t->getMessage(),
            'linea' => $t->getLine(),
            'archivo' => $t->getFile(),
            'requestLog' => $requestLog,
            'responseLog' => $bindings,
        ];    
        Log::error('Error en la funcion RegistrarIntento() del Modelo Configuracion', $error);
    }
}

public static function getCatalogoRoles()
{
    try{
        DB::enableQueryLog();
        return DB::select("exec dbo.getCatalogoRoles");   
    }catch(\Throwable $t){
        $queries = DB::getQueryLog();
        $lastQuery = end($queries); // Obtener la última consulta ejecutada
        $bindings = $lastQuery['bindings']; // Parámetros de la consulta
        $requestLog = $lastQuery['query']; // SQL de la consulta
        $error = [
            'status' => '0',
            'fecha' => date('Y-m-d H:i:s'),
            'descripcion' => 'Error en la funcion getCatalogoRoles().',
            'codigoError' => $t->getCode(),
            'msnError' => $t->getMessage(),
            'linea' => $t->getLine(),
            'archivo' => $t->getFile(),
            'requestLog' => $requestLog,
            'responseLog' => $bindings,
        ];    
        Log::error('Error en la funcion getCatalogoRoles() del Modelo Configuracion', $error);
        return false;        
    }
}

public static function getCatalogoUsuarios($RoleID, $NoColaborador)
{
    try{
        DB::enableQueryLog();
        return DB::select("exec dbo.getUsuarios ?, ?", [$RoleID, $NoColaborador]);   
    }catch(\Throwable $t){
        $queries = DB::getQueryLog();
        $lastQuery = end($queries); // Obtener la última consulta ejecutada
        $bindings = $lastQuery['bindings']; // Parámetros de la consulta
        $requestLog = $lastQuery['query']; // SQL de la consulta
        $error = [
            'status' => '0',
            'fecha' => date('Y-m-d H:i:s'),
            'descripcion' => 'Error en la funcion getCatalogoUsuarios().',
            'codigoError' => $t->getCode(),
            'msnError' => $t->getMessage(),
            'linea' => $t->getLine(),
            'archivo' => $t->getFile(),
            'requestLog' => $requestLog,
            'responseLog' => $bindings,
        ];    
        Log::error('Error en la funcion getCatalogoUsuarios() del Modelo Configuracion', $error);
        return false;
    }
}

public static function getCatalogoUsuariosTienda($CodPlaza, $TiendaID)
{
    try{
        DB::enableQueryLog();
        return DB::select("exec dbo.getUsuariosTienda ?, ?", [$CodPlaza, $TiendaID]);   
    }catch(\Throwable $t){
        $queries = DB::getQueryLog();
        $lastQuery = end($queries); // Obtener la última consulta ejecutada
        $bindings = $lastQuery['bindings']; // Parámetros de la consulta
        $requestLog = $lastQuery['query']; // SQL de la consulta
        $error = [
            'status' => '0',
            'fecha' => date('Y-m-d H:i:s'),
            'descripcion' => 'Error en la funcion getCatalogoUsuariosTienda().',
            'codigoError' => $t->getCode(),
            'msnError' => $t->getMessage(),
            'linea' => $t->getLine(),
            'archivo' => $t->getFile(),
            'requestLog' => $requestLog,
            'responseLog' => $bindings,
        ];    
        Log::error('Error en la funcion getCatalogoUsuariosTienda() del Modelo Configuracion', $error);
        return false;
    }
}

public static function ExisteUsuario($NoColaborador, $Usuario, $Nombre){
   DB::enableQueryLog();
    try{
        DB::enableQueryLog();

        if ($NoColaborador != null) {
            return DB::table('dbo.Usuarios')
                    ->where('NoColaborador', '=', $NoColaborador)
                    ->exists();
        }else if($Usuario != null) {
            return DB::table('dbo.Usuarios')
                    ->where('Usuario', '=', $Usuario)
                    ->exists();
        }else if($Nombre != null){
            return DB::table('dbo.Usuarios')
                    ->where('Nombre', '=', $Nombre)
                    ->exists();
        }                
    }catch(\Throwable $t){
        $queries = DB::getQueryLog();
        $lastQuery = end($queries); // Obtener la última consulta ejecutada
        $bindings = $lastQuery['bindings']; // Parámetros de la consulta
        $requestLog = $lastQuery['query']; // SQL de la consulta
        $error = [
            'status' => '0',
            'fecha' => date('Y-m-d H:i:s'),
            'descripcion' => 'Error en la funcion ExisteUsuario().',
            'codigoError' => $t->getCode(),
            'msnError' => $t->getMessage(),
            'linea' => $t->getLine(),
            'archivo' => $t->getFile(),
            'requestLog' => $requestLog,
            'responseLog' => $bindings,
        ];    
        Log::error('Error en la funcion ExisteUsuario() del Modelo Configuracion', $error);
        return false;
    }
}

public static function ExisteUsuarioTienda($NoColaborador, $Usuario, $Nombre){
    DB::enableQueryLog();
    try{
        DB::enableQueryLog();

        if ($NoColaborador != null) {
            return DB::table('dbo.UsuariosTienda')
                    ->where('NoColaborador', '=', $NoColaborador)
                    ->exists();
        }else if($Usuario != null) {
            return DB::table('dbo.UsuariosTienda')
                    ->where('Usuario', '=', $Usuario)
                    ->exists();
        }else if($Nombre != null){
            return DB::table('dbo.UsuariosTienda')
                    ->where('Nombre', '=', $Nombre)
                    ->exists();
        }                
    }catch(\Throwable $t){
        $queries = DB::getQueryLog();
        $lastQuery = end($queries); // Obtener la última consulta ejecutada
        $bindings = $lastQuery['bindings']; // Parámetros de la consulta
        $requestLog = $lastQuery['query']; // SQL de la consulta
        $error = [
            'status' => '0',
            'fecha' => date('Y-m-d H:i:s'),
            'descripcion' => 'Error en la funcion ExisteUsuarioTienda().',
            'codigoError' => $t->getCode(),
            'msnError' => $t->getMessage(),
            'linea' => $t->getLine(),
            'archivo' => $t->getFile(),
            'requestLog' => $requestLog,
            'responseLog' => $bindings,
        ];    
        Log::error('Error en la funcion ExisteUsuario() del Modelo Configuracion', $error);
        return false;
    }
}

public static function getCatalogoPlazas()
{
    try{
        DB::enableQueryLog();
        return DB::select("exec dbo.getCatalogoPlazas");   
    }catch(\Throwable $t){
        $queries = DB::getQueryLog();
        $lastQuery = end($queries); // Obtener la última consulta ejecutada
        $bindings = $lastQuery['bindings']; // Parámetros de la consulta
        $requestLog = $lastQuery['query']; // SQL de la consulta
        $error = [
            'status' => '0',
            'fecha' => date('Y-m-d H:i:s'),
            'descripcion' => 'Error en la funcion getCatalogoPlazas().',
            'codigoError' => $t->getCode(),
            'msnError' => $t->getMessage(),
            'linea' => $t->getLine(),
            'archivo' => $t->getFile(),
            'requestLog' => $requestLog,
            'responseLog' => $bindings,
        ];    
        Log::error('Error en la funcion getCatalogoPlazas() del Modelo Configuracion', $error);
        return false;
    }
}

public static function getCatalogoTipos()
{
    try{
        DB::enableQueryLog();
        return DB::select("exec dbo.getCatalogoTipos");   
    }catch(\Throwable $t){
        $queries = DB::getQueryLog();
        $lastQuery = end($queries); // Obtener la última consulta ejecutada
        $bindings = $lastQuery['bindings']; // Parámetros de la consulta
        $requestLog = $lastQuery['query']; // SQL de la consulta
        $error = [
            'status' => '0',
            'fecha' => date('Y-m-d H:i:s'),
            'descripcion' => 'Error en la funcion getCatalogoTipos().',
            'codigoError' => $t->getCode(),
            'msnError' => $t->getMessage(),
            'linea' => $t->getLine(),
            'archivo' => $t->getFile(),
            'requestLog' => $requestLog,
            'responseLog' => $bindings,

        ];    
        Log::error('Error en la funcion getCatalogoTipos() del Modelo Configuracion', $error);
        return false;
    }
}

public static function getCatalogoTiendas($CodPlaza = null)
{
    try{  
        DB::enableQueryLog();
        return DB::select("exec dbo.getCatalogoTiendas ?", [$CodPlaza]);   
    }catch(\Throwable $t){
        $queries = DB::getQueryLog();
        $lastQuery = end($queries); // Obtener la última consulta ejecutada
        $bindings = $lastQuery['bindings']; // Parámetros de la consulta
        $requestLog = $lastQuery['query']; // SQL de la consulta
        $error = [
            'status' => '0',
            'fecha' => date('Y-m-d H:i:s'),
            'descripcion' => 'Error en la funcion getCatalogoTiendas().',
            'codigoError' => $t->getCode(),
            'msnError' => $t->getMessage(),
            'linea' => $t->getLine(),
            'archivo' => $t->getFile(),
            'requestLog' => $requestLog,
            'responseLog' => $bindings,
        ];    
        Log::error('Error en la funcion getCatalogoTiendas() del Modelo Configuracion', $error);
        return false;
    }
}

public static function BuscarUsuario($NoColaborador){
    try{
        DB::enableQueryLog();
        return DB::select("exec dbo.AioBuscarUsuario ?", [$NoColaborador]);   
    }catch(\Throwable $t){
        $queries = DB::getQueryLog();
        $lastQuery = end($queries); // Obtener la última consulta ejecutada
        $bindings = $lastQuery['bindings']; // Parámetros de la consulta
        $requestLog = $lastQuery['query']; // SQL de la consulta
        $error = [
            'status' => '0',
            'fecha' => date('Y-m-d H:i:s'),
            'descripcion' => 'Error en la funcion BuscarUsuario().',
            'codigoError' => $t->getCode(),
            'msnError' => $t->getMessage(),
            'linea' => $t->getLine(),
            'archivo' => $t->getFile(),
            'requestLog' => $requestLog,
            'responseLog' => $bindings,
        ];    
        Log::error('Error en la funcion BuscarUsuario() del Modelo Configuracion', $error);
        return false;
    }
}

public static function GuardarUsuario($Usuario, $Nombre, $NoColaborador, $RoleID, $Plazas, $TiendaID, $hashedPassword, $usuarioAlta){
    
    $plazasString = '';
    
    if (is_array($Plazas)) {
        $plazasString = implode(',', $Plazas);        
    }else{
        $plazasString = $Plazas;
    }

    try{
        DB::enableQueryLog();
        return DB::select("exec dbo.GuardarUsuario ?, ?, ?, ?, ?, ?, ?, ?", [$Usuario, $Nombre, $NoColaborador, $RoleID, $plazasString, $TiendaID, $hashedPassword, $usuarioAlta]);
    }catch(\Throwable $t){
        $queries = DB::getQueryLog();
        $lastQuery = end($queries); // Obtener la última consulta ejecutada
        $bindings = $lastQuery['bindings']; // Parámetros de la consulta
        $requestLog = $lastQuery['query']; // SQL de la consulta
        $error = [
            'status' => '0',
            'fecha' => date('Y-m-d H:i:s'),
            'descripcion' => 'Error en la funcion GuardarUsuario().',
            'codigoError' => $t->getCode(),
            'msnError' => $t->getMessage(),
            'linea' => $t->getLine(),
            'archivo' => $t->getFile(),
            'requestLog' => $requestLog,
            'responseLog' => $bindings,
        ];    
        Log::error('Error en la funcion GuardarUsuario() del Modelo Configuracion', $error);
        return false;
    }
}

public static function GuardarUsuarioTienda($Usuario, $CodPlaza, $TiendaID, $Oficina, $TiendaOVTA, $hashedPassword, $usuarioAlta){
    
    try{
        DB::enableQueryLog();
        return DB::select("exec dbo.GuardarUsuarioTienda ?, ?, ?, ?, ?, ?, ?", [$Usuario, $CodPlaza, $TiendaID, $Oficina, $TiendaOVTA, $hashedPassword, $usuarioAlta]);
    }catch(\Throwable $t){
        $queries = DB::getQueryLog();
        $lastQuery = end($queries); // Obtener la última consulta ejecutada
        $bindings = $lastQuery['bindings']; // Parámetros de la consulta
        $requestLog = $lastQuery['query']; // SQL de la consulta
        $error = [
            'status' => '0',
            'fecha' => date('Y-m-d H:i:s'),
            'descripcion' => 'Error en la funcion GuardarUsuarioTienda().',
            'codigoError' => $t->getCode(),
            'msnError' => $t->getMessage(),
            'linea' => $t->getLine(),
            'archivo' => $t->getFile(),
            'requestLog' => $requestLog,
            'responseLog' => $bindings,
        ];    
        Log::error('Error en la funcion GuardarUsuarioTienda() del Modelo Configuracion', $error);
        return false;
    }
}

public static function EditarUsuario($NoColaborador, $Plazas, $TiendaID, $usuarioMod){
    
    $plazasString = '';
    
    if (is_array($Plazas)) {
        $plazasString = implode(',', $Plazas);        
    }else{
        $plazasString = $Plazas;
    }

    try{
        DB::enableQueryLog();
        return DB::select("exec dbo.EditarUsuario ?, ?, ?, ?", [$NoColaborador, $plazasString, $TiendaID, $usuarioMod]);        
    }catch(\Throwable $t){
        $queries = DB::getQueryLog();
        $lastQuery = end($queries); // Obtener la última consulta ejecutada
        $bindings = $lastQuery['bindings']; // Parámetros de la consulta
        $requestLog = $lastQuery['query']; // SQL de la consulta
        $error = [
            'status' => '0',
            'fecha' => date('Y-m-d H:i:s'),
            'descripcion' => 'Error en la funcion EditarUsuario().',
            'codigoError' => $t->getCode(),
            'msnError' => $t->getMessage(),
            'linea' => $t->getLine(),
            'archivo' => $t->getFile(),
            'requestLog' => $requestLog,
            'responseLog' => $bindings,
        ];    
        Log::error('Error en la funcion EditarUsuario() del Modelo Configuracion', $error);
        return false;
    }
}

public static function EditarUsuarioTienda($Usuario, $CodPlaza, $TiendaID, $usuarioMod, $Oficina, $TiendaOVTA){
    
    // dd($Usuario, $CodPlaza, $TiendaID, $usuarioMod, $Oficina, $TiendaOVTA);
    try{
        DB::enableQueryLog();
        // dd("meejecutro");
        return DB::select("exec dbo.EditarUsuarioTienda ?, ?, ?, ?, ?, ?", [$Usuario, $CodPlaza, $TiendaID, $usuarioMod, $Oficina, $TiendaOVTA]);
                            //esto se usa para imprimir consulta                              
        // $query = DB::getQueryLog();
        // $lastQuery = end($query);
        // dd($lastQuery);

    }catch(\Throwable $t){
        $queries = DB::getQueryLog();
        $lastQuery = end($queries); // Obtener la última consulta ejecutada
        $bindings = $lastQuery['bindings']; // Parámetros de la consulta
        $requestLog = $lastQuery['query']; // SQL de la consulta
        $error = [
            'status' => '0',
            'fecha' => date('Y-m-d H:i:s'),
            'descripcion' => 'Error en la funcion EditarUsuarioTienda().',
            'codigoError' => $t->getCode(),
            'msnError' => $t->getMessage(),
            'linea' => $t->getLine(),
            'archivo' => $t->getFile(),
            'requestLog' => $requestLog,
            'responseLog' => $bindings,
        ];    
        Log::error('Error en la funcion EditarUsuarioTienda() del Modelo Configuracion', $error);
        return false;
    }
}

public static function ReiniciarUsuario($tipoUsuario, $UsuarioID, $Clave, $usuarioMod){
    
    if ($tipoUsuario == '1') {
        try{
            DB::enableQueryLog();
            return DB::select("exec dbo.ReiniciarUsuario ?, ?, ?", [$UsuarioID, $Clave, $usuarioMod]);
        }catch(\Throwable $t){
            $queries = DB::getQueryLog();
            $lastQuery = end($queries); // Obtener la última consulta ejecutada
            $bindings = $lastQuery['bindings']; // Parámetros de la consulta
            $requestLog = $lastQuery['query']; // SQL de la consulta
            $error = [
                'status' => '0',
                'fecha' => date('Y-m-d H:i:s'),
                'descripcion' => 'Error en la funcion ReiniciarUsuario().',
                'codigoError' => $t->getCode(),
                'msnError' => $t->getMessage(),
                'linea' => $t->getLine(),
                'archivo' => $t->getFile(),
                'requestLog' => $requestLog,
                'responseLog' => $bindings,
            ];    
            Log::error('Error en la funcion ReiniciarUsuario() del Modelo Configuracion', $error);
            return false;
        }
    }elseif ($tipoUsuario == '2'){
        try{
            DB::enableQueryLog();
            return DB::select("exec dbo.ReiniciarUsuarioTienda ?, ?, ?", [$UsuarioID, $Clave, $usuarioMod]);
        }catch(\Throwable $t){
            $queries = DB::getQueryLog();
            $lastQuery = end($queries); // Obtener la última consulta ejecutada
            $bindings = $lastQuery['bindings']; // Parámetros de la consulta
            $requestLog = $lastQuery['query']; // SQL de la consulta
            $error = [
                'status' => '0',
                'fecha' => date('Y-m-d H:i:s'),
                'descripcion' => 'Error en la funcion ReiniciarUsuario().',
                'codigoError' => $t->getCode(),
                'msnError' => $t->getMessage(),
                'linea' => $t->getLine(),
                'archivo' => $t->getFile(),
                'requestLog' => $requestLog,
                'responseLog' => $bindings,
            ];    
            Log::error('Error en la funcion ReiniciarUsuario() del Modelo Configuracion', $error);
            return false;
        }
    }    
}

public static function DeshabilitarUsuario($tipoUsuario, $UsuarioID, $usuarioMod){
    if ($tipoUsuario == '1') {
        try{
            DB::enableQueryLog();
            return DB::select("exec dbo.DeshabilitarUsuario ?, ?", [$UsuarioID, $usuarioMod]);
        }catch(\Throwable $t){
            $queries = DB::getQueryLog();
            $lastQuery = end($queries); // Obtener la última consulta ejecutada
            $bindings = $lastQuery['bindings']; // Parámetros de la consulta
            $requestLog = $lastQuery['query']; // SQL de la consulta
            $error = [
                'status' => '0',
                'fecha' => date('Y-m-d H:i:s'),
                'descripcion' => 'Error en la funcion DeshabilitarUsuario().',
                'codigoError' => $t->getCode(),
                'msnError' => $t->getMessage(),
                'linea' => $t->getLine(),
                'archivo' => $t->getFile(),
                'requestLog' => $requestLog,
                'responseLog' => $bindings,
            ];    
            Log::error('Error en la funcion DeshabilitarUsuario() del Modelo Configuracion', $error);
            return false;            
        }
    }else if ($tipoUsuario == '2') {
        try{
            DB::enableQueryLog();
            return DB::select("exec dbo.DeshabilitarUsuarioTienda ?, ?", [$UsuarioID, $usuarioMod]);
        }catch(\Throwable $t){
            $queries = DB::getQueryLog();
            $lastQuery = end($queries); // Obtener la última consulta ejecutada
            $bindings = $lastQuery['bindings']; // Parámetros de la consulta
            $requestLog = $lastQuery['query']; // SQL de la consulta
            $error = [
                'status' => '0',
                'fecha' => date('Y-m-d H:i:s'),
                'descripcion' => 'Error en la funcion DeshabilitarUsuario().',
                'codigoError' => $t->getCode(),
                'msnError' => $t->getMessage(),
                'linea' => $t->getLine(),
                'archivo' => $t->getFile(),
                'requestLog' => $requestLog,
                'responseLog' => $bindings,
            ];    
            Log::error('Error en la funcion DeshabilitarUsuario() del Modelo Configuracion', $error);
            return false;
        }
    }
}

public static function ConsultarTramite($DpVale){
    try{
        DB::enableQueryLog();
        return DB::select("exec dbo.ConsultarTramite ?", [$DpVale]);
    }catch(\Throwable $t){
        $queries = DB::getQueryLog();
        $lastQuery = end($queries); // Obtener la última consulta ejecutada
        $bindings = $lastQuery['bindings']; // Parámetros de la consulta
        $requestLog = $lastQuery['query']; // SQL de la consulta
        $error = [
            'status' => '0',
            'fecha' => date('Y-m-d H:i:s'),
            'descripcion' => 'Error en la funcion ConsultarTramite().',
            'codigoError' => $t->getCode(),
            'msnError' => $t->getMessage(),
            'linea' => $t->getLine(),
            'archivo' => $t->getFile(),
            'requestLog' => $requestLog,
            'responseLog' => $bindings,
        ];    
        Log::error('Error en la funcion ConsultarTramite() del Modelo Configuracion', $error);
        return false;
    }
}
public static function verificarDPVale($DpVale) {
    // Intenta buscar el DPVale con ceros a la izquierda (o en su forma original)
    $conCeros = DB::table('FinancieroDigital')->where('DPVale', $DpVale)->first();
    
    // Intenta buscar el DPVale sin ceros a la izquierda (tratándolo como cadena)
    $sinCeros = DB::table('FinancieroDigital')
        ->where('DPVale', ltrim($DpVale, '0'))
        ->first();
    
    if ($conCeros) {
        return $conCeros->DPVale;
    } elseif ($sinCeros) {
        return $sinCeros->DPVale;
    } else {
        return null;
    }
}



public static function BuscarDistribuidor($ValorBusqueda){
    try{
        DB::enableQueryLog();
        return DB::select("exec dbo.BuscarDistribuidor ?", [$ValorBusqueda]);
    }catch(\Throwable $t){
        $queries = DB::getQueryLog();
        $lastQuery = end($queries); // Obtener la última consulta ejecutada
        $bindings = $lastQuery['bindings']; // Parámetros de la consulta
        $requestLog = $lastQuery['query']; // SQL de la consulta
        $error = [
            'status' => '0',
            'fecha' => date('Y-m-d H:i:s'),
            'descripcion' => 'Error en la funcion BuscarDistribuidor().',
            'codigoError' => $t->getCode(),
            'msnError' => $t->getMessage(),
            'linea' => $t->getLine(),
            'archivo' => $t->getFile(),
            'requestLog' => $requestLog,
            'responseLog' => $bindings,
        ];    
        Log::error('Error en la funcion BuscarDistribuidor() del Modelo Configuracion', $error);
        return false;
    }
}

public static function BuscarCliente($ValorBusqueda){
    try{
        DB::enableQueryLog();
        return DB::select("exec dbo.BuscarCliente ?", [$ValorBusqueda]);
    }catch(\Throwable $t){
        $queries = DB::getQueryLog();
        $lastQuery = end($queries); // Obtener la última consulta ejecutada
        $bindings = $lastQuery['bindings']; // Parámetros de la consulta
        $requestLog = $lastQuery['query']; // SQL de la consulta
        $error = [
            'status' => '0',
            'fecha' => date('Y-m-d H:i:s'),
            'descripcion' => 'Error en la funcion BuscarCliente().',
            'codigoError' => $t->getCode(),
            'msnError' => $t->getMessage(),
            'linea' => $t->getLine(),
            'archivo' => $t->getFile(),
            'requestLog' => $requestLog,
            'responseLog' => $bindings,
        ];    
        Log::error('Error en la funcion BuscarCliente() del Modelo Configuracion', $error);
        return false;
    }
}

public static function ObtenerTramites($DPVale, $FechaInicio, $FechaFin, $CodPlaza, $TiendaID, $TipoID, $DistribuidorID,
                                        $ClienteID, $Notas){
    try{
        DB::enableQueryLog();
        // dd($FechaInicio, $FechaFin, $CodPlaza, $TiendaID, $TipoID, $DistribuidorID, $ClienteID, $Notas, $DPVale);
        return DB::select("exec dbo.ObtenerTramites ?, ?, ?, ?, ?, ?, ?, ?, ?", [
            $FechaInicio, $FechaFin, $CodPlaza, $TiendaID, $TipoID,
            $DistribuidorID, $ClienteID, $Notas, $DPVale
            ]);
    }catch(\Throwable $t){
        $lastQuery = end($queries); // Obtener la última consulta ejecutada
        $bindings = $lastQuery['bindings']; // Parámetros de la consulta
        $requestLog = $lastQuery['query']; // SQL de la consulta

        $error = [
            'status' => '0',
            'fecha' => date('Y-m-d H:i:s'),
            'descripcion' => 'Error en la funcion ObtenerTramites().',
            'codigoError' => $t->getCode(),
            'msnError' => $t->getMessage(),
            'linea' => $t->getLine(),
            'archivo' => $t->getFile(),
            'requestLog' => $requestLog,
            'responseLog' => $bindings,
        ];    
        Log::error('Error en la funcion ObtenerTramites() del Modelo Configuracion', $error);
        return false;
    }
}

public static function ActualizarRevision($PrestamoID, $RoleID, $chk, $UsuarioID){
    try{
        DB::enableQueryLog();
        return DB::select("exec dbo.ActualizarRevision ?, ?, ?, ?", [$PrestamoID, $RoleID, $chk, $UsuarioID]);
    }catch(\Throwable $t){
        $queries = DB::getQueryLog();
        $lastQuery = end($queries); // Obtener la última consulta ejecutada
        $bindings = $lastQuery['bindings']; // Parámetros de la consulta
        $requestLog = $lastQuery['query']; // SQL de la consulta
        $error = [
            'status' => '0',
            'fecha' => date('Y-m-d H:i:s'),
            'descripcion' => 'Error en la funcion ActualizarRevision().',
            'codigoError' => $t->getCode(),
            'msnError' => $t->getMessage(),
            'linea' => $t->getLine(),
            'archivo' => $t->getFile(),
            'requestLog' => $requestLog,
            'responseLog' => $bindings,
        ];    
        Log::error('Error en la funcion ActualizarRevision() del Modelo Configuracion', $error);
        return false;
    }
}

public static function GetNotas($PrestamoID){
    try{
        DB::enableQueryLog();
        return DB::select("exec dbo.getNotas ?", [$PrestamoID]);
    }catch(\Throwable $t){
        $queries = DB::getQueryLog();
        $lastQuery = end($queries); // Obtener la última consulta ejecutada
        $bindings = $lastQuery['bindings']; // Parámetros de la consulta
        $requestLog = $lastQuery['query']; // SQL de la consulta
        $error = [
            'status' => '0',
            'fecha' => date('Y-m-d H:i:s'),
            'descripcion' => 'Error en la funcion GetNotas().',
            'codigoError' => $t->getCode(),
            'msnError' => $t->getMessage(),
            'linea' => $t->getLine(),
            'archivo' => $t->getFile(),
            'requestLog' => $requestLog,
            'responseLog' => $bindings,
        ];    
        Log::error('Error en la funcion GetNotas() del Modelo Configuracion', $error);
        return false;
    }
}

public static function AgregarNota($PrestamoID, $RoleID, $UsuarioID, $Nota, $NotaModal){
    try{
        DB::enableQueryLog();
        return DB::select("exec dbo.agregarNota ?, ?, ?, ?, ?", [$PrestamoID, $RoleID, $UsuarioID, $Nota, $NotaModal]);
    }catch(\Throwable $t){
        $queries = DB::getQueryLog();
        $lastQuery = end($queries); // Obtener la última consulta ejecutada
        $bindings = $lastQuery['bindings']; // Parámetros de la consulta
        $requestLog = $lastQuery['query']; // SQL de la consulta
        $error = [
            'status' => '0',
            'fecha' => date('Y-m-d H:i:s'),
            'descripcion' => 'Error en la funcion AgregarNota().',
            'codigoError' => $t->getCode(),
            'msnError' => $t->getMessage(),
            'linea' => $t->getLine(),
            'archivo' => $t->getFile(),
            'requestLog' => $requestLog,
            'responseLog' => $bindings,
        ];    
        Log::error('Error en la funcion AgregarNota() del Modelo Configuracion', $error);
        return false;
    }
}

public static function GetCatalogoDocumentos(){
    try{
        DB::enableQueryLog();
        return DB::select("exec dbo.getCatalogoDocumentos");
    }catch(\Throwable $t){
        $queries = DB::getQueryLog();
        $lastQuery = end($queries); // Obtener la última consulta ejecutada
        $bindings = $lastQuery['bindings']; // Parámetros de la consulta
        $requestLog = $lastQuery['query']; // SQL de la consulta
        $error = [
            'status' => '0',
            'fecha' => date('Y-m-d H:i:s'),
            'descripcion' => 'Error en la funcion GetCatalogoDocumentos().',
            'codigoError' => $t->getCode(),
            'msnError' => $t->getMessage(),
            'linea' => $t->getLine(),
            'archivo' => $t->getFile(),
            'requestLog' => $requestLog,
            'responseLog' => $bindings,
        ];    
        Log::error('Error en la funcion GetCatalogoDocumentos() del Modelo Configuracion', $error);
        return false;
    }
}

public static function GetPlantillas($Fecha, $ID_CatalogoDocumentos){
    try{
        DB::enableQueryLog();
        return DB::select("exec dbo.getPlantillas ?, ?", [$Fecha, $ID_CatalogoDocumentos]);
    }catch(\Throwable $t){
        $queries = DB::getQueryLog();
        $lastQuery = end($queries); // Obtener la última consulta ejecutada
        $bindings = $lastQuery['bindings']; // Parámetros de la consulta
        $requestLog = $lastQuery['query']; // SQL de la consulta
        $error = [
            'status' => '0',
            'fecha' => date('Y-m-d H:i:s'),
            'descripcion' => 'Error en la funcion GetPlantillas().',
            'codigoError' => $t->getCode(),
            'msnError' => $t->getMessage(),
            'linea' => $t->getLine(),
            'archivo' => $t->getFile(),
            'requestLog' => $requestLog,
            'responseLog' => $bindings,
        ];    
        Log::error('Error en la funcion GetPlantillas() del Modelo Configuracion', $error);
        return false;
    }
}

public static function GetSeguro($DPVale){
    try{
        DB::enableQueryLog();
        return DB::select("exec dbo.getSeguro ?", [$DPVale]);
    }catch(\Throwable $t){
        $queries = DB::getQueryLog();
        $lastQuery = end($queries); // Obtener la última consulta ejecutada
        $bindings = $lastQuery['bindings']; // Parámetros de la consulta
        $requestLog = $lastQuery['query']; // SQL de la consulta
        $error = [
            'status' => '0',
            'fecha' => date('Y-m-d H:i:s'),
            'descripcion' => 'Error en la funcion GetSeguro().',
            'codigoError' => $t->getCode(),
            'msnError' => $t->getMessage(),
            'linea' => $t->getLine(),
            'archivo' => $t->getFile(),
            'requestLog' => $requestLog,
            'responseLog' => $bindings,
        ];    
        Log::error('Error en la funcion GetSeguro() del Modelo Configuracion', $error);
        return false;
    }
}

public static function ObtenerTramite($DPVale){
    try{
        DB::enableQueryLog();
        // dd($DPVale);
        return DB::select("exec dbo.ObtenerTramite ?", [$DPVale]);
    }catch(\Throwable $t){
        $queries = DB::getQueryLog();
        $lastQuery = end($queries); // Obtener la última consulta ejecutada
        $bindings = $lastQuery['bindings']; // Parámetros de la consulta
        $requestLog = $lastQuery['query']; // SQL de la consulta
        $error = [
            'status' => '0',
            'fecha' => date('Y-m-d H:i:s'),
            'descripcion' => 'Error en la funcion ObtenerTramite().',
            'codigoError' => $t->getCode(),
            'msnError' => $t->getMessage(),
            'linea' => $t->getLine(),
            'archivo' => $t->getFile(),
            'requestLog' => $requestLog,
            'responseLog' => $bindings,

        ];    
        Log::error('Error en la funcion ObtenerTramite() del Modelo Configuracion', $error);
        return false;
    }
}
public static function rutaS3($ruta)
{
    try {
        DB::enableQueryLog();
        $urlPrefix = 'url_'; 
        $parametroBusqueda = $urlPrefix . $ruta;

        $config = DB::table('dbo.Configuracion')
            ->select('Valor')
            ->where(DB::raw('LOWER(Parametro)'), 'LIKE', '%' . strtolower($parametroBusqueda) . '%')
            ->first();
        
        // Devuelve el valor del campo 'Valor' si el resultado no es nulo
        return $config ? $config->Valor : null;

    } catch (\Throwable $t) {
        $queries = DB::getQueryLog();
        $lastQuery = end($queries); // Obtener la última consulta ejecutada
        $bindings = $lastQuery['bindings']; // Parámetros de la consulta
        $requestLog = $lastQuery['query']; // SQL de la consulta
        $error = [
            'status' => '0',
            'fecha' => date('Y-m-d H:i:s'),
            'descripcion' => 'Error en la funcion rutaS3().',
            'codigoError' => $t->getCode(),
            'msnError' => $t->getMessage(),
            'linea' => $t->getLine(),
            'archivo' => $t->getFile(),
            'requestLog' => $requestLog,
            'responseLog' => $bindings,
        ];
        Log::error('Error en la funcion rutaS3()', $error);
        return false;
    }
}

public static function getAllDocumentos()
{
    try {
        DB::enableQueryLog();
        $results = DB::select("EXEC FinancieroDigital.dbo.getCatalogoDocumentos");

        return $results;

    } catch (\Throwable $t) {
        $queries = DB::getQueryLog();
        $lastQuery = end($queries); // Obtener la última consulta ejecutada
        $bindings = $lastQuery['bindings']; // Parámetros de la consulta
        $requestLog = $lastQuery['query']; // SQL de la consulta
        $error = [
            'status' => '0',
            'fecha' => date('Y-m-d H:i:s'),
            'descripcion' => 'Error en la funcion getAllDocumentos().',
            'codigoError' => $t->getCode(),
            'msnError' => $t->getMessage(),
            'linea' => $t->getLine(),
            'archivo' => $t->getFile(),
            'requestLog' => $requestLog,
            'responseLog' => $bindings,
        ];
        Log::error('Error en la funcion getAllDocumentos()', $error);
        
        return null;
    }
}
public static function getDocumentosMap() {
    $documentos = self::getAllDocumentos();
    $map = [];

    foreach ($documentos as $documento) {
        $map[substr($documento->Codigo, 0, 1)] = $documento->Nombre; // Considera solo el primer caracter
    }

    return $map;
}

public static function CambiarEstatusDocumentos($DPVale, $DocEstatus){
    try{
        DB::enableQueryLog();
        return DB::select("exec dbo.CambiarEstatusDocumentos ?, ?", [$DPVale, $DocEstatus]);
    }catch(\Throwable $t){
        $queries = DB::getQueryLog();
        $lastQuery = end($queries); // Obtener la última consulta ejecutada
        $bindings = $lastQuery['bindings']; // Parámetros de la consulta
        $requestLog = $lastQuery['query']; // SQL de la consulta
        $error = [
            'status' => '0',
            'fecha' => date('Y-m-d H:i:s'),
            'descripcion' => 'Error en la funcion CambiarEstatusDocumentos().',
            'codigoError' => $t->getCode(),
            'msnError' => $t->getMessage(),
            'linea' => $t->getLine(),
            'archivo' => $t->getFile(),
            'requestLog' => $requestLog, // Agregar el SQL al log
            'responseLog' => $bindings, // Agregar los parámetros al log
        ];
        Log::error('Error en la funcion CambiarEstatusDocumentos()', $error);       
        return false;
    }
}

}
