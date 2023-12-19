<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\ConfigService;


class SaveLoan extends Model
{
    use HasFactory;

    public static function saveLoan($data)
    {
        $apiUrl = ConfigService::obtenerUrlS2Credit();
        $response = null;
        try {
            $response = Http::post($apiUrl, $data);

            if ($response->successful()) {
                return $response->json();
            } else {
                throw new \Exception("Error al guardar el préstamo: " . $response->status());
                
            }
        } catch (\Throwable $t) {

            $error = [
                'status'=> '0',
                'fecha'=> date('Y-m-d H:i:s'),
                'descripcion' => 'Error en la funcion saveLoan() del Modelo SaveLoan al guardar el préstamo',                
                'codigoError'=> $t->getCode(),
                'msnError' => $t->getMessage(),
                'linea' => $t->getLine(),
                'archivo' => $t->getFile(),
                'requestLog' => $data,
                'responseLog' => $response
            ];    
            Log::error('Error en la funcion saveLoan() del Modelo SaveLoan al guardar el préstamo', $error);            
            throw new \Exception("Error al guardar el préstamo: " . $t->getMessage());
        }
    }
    public static function saveBeneficiary($beneficiaryData)
    {
        $apiUrl = ConfigService::obtenerUrlS2Credit();
        $requestData = [
            'beneficiary-change' => $beneficiaryData
        ];

        try {
            // $response = Http::post($apiUrl, [
            //     'beneficiary-change' => $beneficiaryData
            // ]);
            $response = Http::post($apiUrl, $requestData);
            if ($response->successful()) {
                return $response->json();
            } else {
                throw new \Exception("Error al guardar el beneficiario: " . $response->status());
            }
        } catch (\Exception $e) {
            $error = [
                'status'=> '0',
                'fecha'=> date('Y-m-d H:i:s'),
                'descripcion' => 'Error en la funcion saveBeneficiary() del Modelo SaveLoan',
                'codigoError'=> $e->getCode(),
                'msnError' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'requestLog' => $requestData,
                'responseLog' => $response
            ];    
            Log::error('Error en la funcion saveBeneficiary() del Modelo SaveLoan', $error);
            throw new \Exception("Error al guardar el beneficiario: " . $e->getMessage());
        }
    }
    public static function swapTransfer($transferData)
    {        
        $apiUrl = ConfigService::obtenerValorPorParametro('url_broker_swap') . ConfigService::obtenerValorPorParametro('path_swap_transfer');
        // $apiUrl = "http://aceqa.grupodp.com.mx:7083/swap/transfer";
            // dd($transferData);
            $response = null;
        try {
            // Log::info('Datos enviados: ', $transferData);

            $response = Http::post($apiUrl, $transferData);
            //                        $response = Http::timeout(5)->get('https://httpbin.org/delay/60');
            // // $response = [];
                // dd($response);
                if(empty($response)){
                    return ['error' => 'El servicio de swap no regresó información. Contacte al administrador.'];
                }
            if ($response->successful()) {
                return $response->json();

            } else {
                throw new \Exception("Error al guardar el beneficiario: " . $response->status());
            }

        } catch (\Exception $e) {
            $error = [
                'status'=> '0',
                'fecha'=> date('Y-m-d H:i:s'),
                'descripcion' => 'Error en la funcion swapTransfer() del Modelo SaveLoan',
                'codigoError'=> $e->getCode(),
                'msnError' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'requestLog' => $transferData,
                'responseLog' => $response
            ];    
            Log::error('Error en la funcion swapTransfer() del Modelo SaveLoan', $error);
            throw new \Exception("Error al guardar el beneficiario: " . $e->getMessage());
        }
    }
    public static function getSwapTransfer($transferData)
    {
        $apiUrl = ConfigService::obtenerValorPorParametro('url_broker_swap') . ConfigService::obtenerValorPorParametro('path_swap_gettransfer');

        // $apiUrl = "http://aceqa.grupodp.com.mx:7082/swap/getTransfer";
            // dd($transferData);
            $response = null;
        try {
            $response = Http::post($apiUrl, $transferData);
            
            if ($response->successful()) {
                return $response->json();

            } else {
                throw new \Exception("Error al guardar el prestamo: " . $response->status());
            }
        } catch (\Exception $e) {
            $error = [
                'status'=> '0',
                'fecha'=> date('Y-m-d H:i:s'),
                'descripcion' => 'Error en la funcion getswapTransfer() del Modelo SaveLoan',
                'codigoError'=> $e->getCode(),
                'msnError' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'requestLog' => $transferData,
                'responseLog' => $response
            ];    
            Log::error('Error en la funcion getswapTransfer() del Modelo SaveLoan', $error);
            throw new \Exception("Error al guardar el prestamo: " . $e->getMessage());
        }
    }
    public static function tokaApi($transferData)
    {
        $apiUrl = ConfigService::obtenerValorPorParametro('url_broker') . ConfigService::obtenerValorPorParametro('path_toka_api_asignaciones');
        // $apiUrl = "http://aceqa.grupodp.com.mx:7083/toka_api/Asignaciones";
            // dd($transferData);
            $response = null;
        try {
            $response = Http::post($apiUrl, $transferData);
            
            if ($response->successful()) {
                return $response->json();

            } else {
                throw new \Exception("Error al guardar el prestamo: " . $response->status());
            }
        } catch (\Exception $e) {
            $error = [
                'status'=> '0',
                'fecha'=> date('Y-m-d H:i:s'),
                'descripcion' => 'Error en la funcion tokaApi() del Modelo SaveLoan',
                'codigoError'=> $e->getCode(),
                'msnError' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'requestLog' => $transferData,
                'responseLog' => $response
            ];    
            Log::error('Error en la funcion tokaApi() del Modelo SaveLoan', $error);
            throw new \Exception("Error al guardar el prestamo: " . $e->getMessage());
        }
    }
    public static function cambioEstado($transferData)
    {
        $apiUrl = ConfigService::obtenerValorPorParametro('url_broker') . ConfigService::obtenerValorPorParametro('path_toka_api_cambio_estado');
        // $apiUrl = "http://aceqa.grupodp.com.mx:7083/toka_api/CambioEstado";

            // dd($transferData);
            $response = null;
        try {
            $response = Http::post($apiUrl, $transferData);

            
            if ($response->successful()) {
                return $response->json();

            } else {
                throw new \Exception("Error al cambiar el estado: " . $response->status());
            }
        } catch (\Exception $e) {
            $error = [
                'status'=> '0',
                'fecha'=> date('Y-m-d H:i:s'),
                'descripcion' => 'Error en la funcion cambioEstado() del Modelo SaveLoan',
                'codigoError'=> $e->getCode(),
                'msnError' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'requestLog' => $transferData,
                'responseLog' => $response
            ];    
            Log::error('Error en la funcion cambioEstado() del Modelo SaveLoan', $error);
            throw new \Exception("Error al cambiar el estado: " . $e->getMessage());
        }
    }
    public static function tokaDispersiones($transferData)
    {
        $apiUrl = ConfigService::obtenerValorPorParametro('url_broker') . ConfigService::obtenerValorPorParametro('path_toka_api_dispersiones');
        // $apiUrl = "http://aceqa.grupodp.com.mx:7083/toka_api/Dispersiones";
            // dd($transferData);
            $response = null;
        try {
            $response = Http::post($apiUrl, $transferData);

            
            if ($response->successful()) {
                return $response->json();

            } else {
                throw new \Exception("Error al ejecutar la dispersión: " . $response->status());
            }
        } catch (\Exception $e) {
            $error = [
                'status'=> '0',
                'fecha'=> date('Y-m-d H:i:s'),
                'descripcion' => 'Error en la funcion tokaDispersiones() del Modelo SaveLoan',
                'codigoError'=> $e->getCode(),
                'msnError' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'requestLog' => $transferData,
                'responseLog' => $response
            ];    
            Log::error('Error en la funcion tokaDispersiones() del Modelo SaveLoan', $error);
            throw new \Exception("Error al ejecutar la dispersión: " . $e->getMessage());
        }
    }
    public static function TokaApiTarjetas($transferData)
    {
        $apiUrl = ConfigService::obtenerValorPorParametro('url_broker_swap') . ConfigService::obtenerValorPorParametro('path_toka_api_tarjetas');
        // $apiUrl = "http://aceqa.grupodp.com.mx:7083/toka_api/Movimientos";
        $response = null;
        try {
            $response = Http::post($apiUrl, $transferData);

            
            if ($response->successful()) {
                return $response->json();

            } else {
                throw new \Exception("Error al ejecutar la dispersión: " . $response->status());
            }
        } catch (\Exception $e) {
            $error = [
                'status'=> '0',
                'fecha'=> date('Y-m-d H:i:s'),
                'descripcion' => 'Error en la funcion tokaApiMovimientos() del Modelo SaveLoan',
                'codigoError'=> $e->getCode(),
                'msnError' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'requestLog' => $transferData,
                'responseLog' => $response
            ];    
            Log::error('Error en la funcion tokaApiMovimientos() del Modelo SaveLoan', $error);
            throw new \Exception("Error al ejecutar la dispersión: " . $e->getMessage());
        }
    }
    public static function store(
                $IDCliente, $SecFile, $NoCuenta, $Importe, $Intereses, $DPVale, $Tipo, $CodAlmacen, $NumFact,
                $IDAsociado, $CodPlaza, $Oficina, $AltaTarjeta, $NoIFE, $fechaStr, $FolioFIIntereses,
                $FolioFIMonto, $Usuario, $Benef, $Banco, $Celular, $CompañiaCelular, $Clabe, $Transfer,
                $NumeroTarjeta, $ServicioId, $Codigo, $NombreCliente, $Generado, $FechaDispersion,
                $CodigoSeguridad, $RFC, $FechNac, $Dispersion, $NoColaborador, $Estatus, $Sociedad
    ) {
        try {
            DB::enableQueryLog();
            return DB::connection('sqlsrv2')->statement('exec dbo.DatosArchivosDPVFIns @IDCliente='.$IDCliente.', 
            @SecFile='.$SecFile.', @NoCuenta=\''.$NoCuenta.'\', @Importe='.$Importe.', @Intereses='.$Intereses.', 
            @DPVale=\''.$DPVale.'\', @Tipo='.$Tipo.', @CodAlmacen='.$CodAlmacen.', @NumFact='.$NumFact.', 
            @IDAsociado='.$IDAsociado.', @CodPlaza='.$CodPlaza.', @Oficina='.$Oficina.', @Tarjeta=\''.$AltaTarjeta.'\', 
            @NoIFE=\''.$NoIFE.'\', @Fecha=\''.$fechaStr.'\', @FolioFIIntereses=\''.$FolioFIIntereses.'\', 
            @FolioFIMonto='.$FolioFIMonto.', @Usuario='.$Usuario.', @Benef=\''.$Benef.'\', @Banco=\''.$Banco.'\',
            @Celular=\''.$Celular.'\', @CompañiaCelular=\''.$CompañiaCelular.'\', @Clabe=\''.$Clabe.'\', 
            @Transfer='.$Transfer.', @NumeroTarjeta=\''.$NumeroTarjeta.'\', @ServicioId=\''.$ServicioId.'\',
            @Codigo='.$Codigo.', @NombreCliente=\''.$NombreCliente.'\', @Generado='.$Generado.', 
            @FechaDispersion=\''.$FechaDispersion.'\', @CodigoSeguridad=\''.$CodigoSeguridad.'\', @RFC=\''.$RFC.'\', 
            @FechNac=\''.$FechNac.'\', @Dispersion='.$Dispersion.', @NoColaborador='.$NoColaborador.', 
            @Estatus=\''.$Estatus.'\', @Sociedad='.$Sociedad.'');

        } catch (\Exception $e) {
            $queries = DB::getQueryLog();
            $lastQuery = end($queries); // Obtener la última consulta ejecutada
            $bindings = $lastQuery['bindings']; // Parámetros de la consulta
            $requestLog = $lastQuery['query']; // SQL de la consulta

            $error = [
                'status'=> '0',
                'fecha'=> date('Y-m-d H:i:s'),
                'descripcion' => 'Error en la funcion store() del Modelo SaveLoan',
                'codigoError'=> $e->getCode(),
                'msnError' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'requestLog' => $requestLog,
                'responseLog' => $bindings
            ];    
            Log::error('Error en la funcion store() del Modelo SaveLoan', $error);
            return $e->getMessage();

        }
    }
  
    public function getEncrypt($dateString) {

        try{
            DB::enableQueryLog();
            $results = DB::select('EXEC dbo.getEncrypt @dateParam=:dateParam', ['dateParam' => $dateString]);
        } catch(\Exception $e){
            $queries = DB::getQueryLog();
            $lastQuery = end($queries); // Obtener la última consulta ejecutada
            $bindings = $lastQuery['bindings']; // Parámetros de la consulta
            $requestLog = $lastQuery['query']; // SQL de la consulta

            $error = [
                'status'=> '0',
                'fecha'=> date('Y-m-d H:i:s'),
                'descripcion' => 'Error en la funcion getEncrypt() del Modelo SaveLoan',
                'codigoError'=> $e->getCode(),
                'msnError' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'requestLog' => $requestLog,
                'responseLog' => $bindings
            ];    
            Log::error('Error en la funcion getEncrypt() del Modelo SaveLoan', $error);
            return $e->getMessage();
        }

        return $results;
    }


    public static function registrarTramite(
        $CodPlaza, $TiendaID, $SociedadID, $TipoID, $DPVale, $DistribuidorID,
        $ClienteID, $NoPlazo, $Importe, $Interes, $pagoQuincenal, $MontoSeguro, $Seguro, 
        $ServicioID, $BancoID, $Banco, $TarjetaClabe, $EstatusDispersion,
        $NombreDistribuidor, $Firma, $NombreCliente, $IdentificacionID, $Identificacion, $Celular,
        $UsuarioID, $validSeguro, $enviarBucket, $Compra,
        $FechaInicioString, $FechaFinString, $NoPoliza, $RFC, $Genero, $Correo
    ) {
        $MontoSeguroSQL = is_null($MontoSeguro) ? 0 : $MontoSeguro;
        $CompraSQL = is_null($Compra) ? 0 : $Compra;

        try {
            DB::enableQueryLog();

            $sql = "exec dbo.RegistrarTramite 
            @CodPlaza = ?,
            @TiendaID = ?, 
            @SociedadID = ?, 
            @TipoID = ?, 
            @DPVale = ?, 
            @DistribuidorID = ?, 
            @NombreDistribuidor = ?,
            @ClienteID = ?, 
            @NombreCliente = ?,
            @NoPlazo = ?, 
            @Importe = ?, 
            @Interes = ?, 
            @PagoQuincenal = ?, 
            @MontoSeguro = ?,
            @ServicioID = ?, 
            @BancoID = ?, 
            @Banco = ?, 
            @TarjetaClabe = ?, 
            @EstatusDispersion = ?,  
            @Firma = ?,
            @IdentificacionID = ?, 
            @Identificacion = ?,
            @Celular = ?, 
            @UsuarioID = ?, 
            @DocEstatus = ?,
            @validSeguro = ?,
            @Compra = ?,
            @FechaInicio = ?, 
            @FechaFin = ?, 
            @Seguro = ?, 
            @NoPoliza = ?, 
            @RFC = ?, 
            @Genero = ?, 
            @Correo = ?";
    
    $params = [
        $CodPlaza,
        $TiendaID,
        $SociedadID,
        $TipoID,
        $DPVale,
        $DistribuidorID,
        $NombreDistribuidor,
        $ClienteID,
        $NombreCliente,
        $NoPlazo,
        $Importe,
        $Interes,
        $pagoQuincenal,
        $MontoSeguroSQL,
        $ServicioID,
        $BancoID,
        $Banco,
        $TarjetaClabe,
        $EstatusDispersion,
        $Firma,
        $IdentificacionID,
        $Identificacion,
        $Celular,
        $UsuarioID,
        $enviarBucket,
        $validSeguro,
        $CompraSQL,
        $FechaInicioString,
        $FechaFinString,
        $Seguro,
        $NoPoliza,
        $RFC,
        $Genero,
        $Correo
    ];
    
    return DB::connection('sqlsrv')->select($sql, $params);
    
            } catch (\Throwable $t) {
                $queries = DB::getQueryLog();
                $lastQuery = end($queries); // Obtener la última consulta ejecutada
                $bindings = $lastQuery['bindings']; // Parámetros de la consulta
                $requestLog = $lastQuery['query']; // SQL de la consulta

                $error = [
                    'status'=> '0',
                    'fecha'=> date('Y-m-d H:i:s'),
                    'descripcion' => 'Error en la funcion registrarTramite() del Modelo SaveLoan al ejecutar dbo.RegistrarTramite',                    
                    'codigoError'=> $t->getCode(),
                    'msnError' => $t->getMessage(),
                    'linea' => $t->getLine(),
                    'archivo' => $t->getFile(),
                    'requestLog' => $requestLog,
                    'responseLog' => $bindings
                ];    
                Log::error('Error en la funcion registrarTramite() del Modelo SaveLoan al ejecutar dbo.RegistrarTramite', $error);
                //Log::error("Error al ejecutar dbo.RegistrarTramite: " . $e->getMessage());
                //Log::error("Línea: " . $e->getLine() . " Archivo: " . $e->getFile());
                //Log::error("Traza de la pila: " . $t->getTraceAsString());
    
                //Log::info('Error');
            return $t->getMessage();
        }
    }
    public static function getConfPlantilla($parametro)
    {
        try{
            DB::enableQueryLog();
                $valor = DB::table('dbo.Configuracion')
                ->where('Parametro', $parametro)
                ->value('Valor');

            if($valor) {
                return $valor;
            } else {
                throw new \Exception("No se encontraron resultados");
            }
        } catch(\Exception $e){
            $queries = DB::getQueryLog();
            $lastQuery = end($queries); // Obtener la última consulta ejecutada
            $bindings = $lastQuery['bindings']; // Parámetros de la consulta
            $requestLog = $lastQuery['query']; // SQL de la consulta

            $error=[
                'status'=> '0',
                'fecha'=> date('Y-m-d H:i:s'),
                'descripcion' => 'Error en la funcion getConfPlantilla() del Modelo SaveLoan al ejecutar dbo.getConfPlantilla',                    
                'codigoError'=> $e->getCode(),
                'msnError' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'requestLog' => $requestLog,
                'responseLog' => $bindings
            ];
            Log::error('Error en la funcion getConfPlantilla() del Modelo SaveLoan al ejecutar dbo.getConfPlantilla', $error);
            throw new \Exception("Error al obtener la configuración de la plantilla: " . $e->getMessage());
        }
        return $valor;
    }
    public static function getPlantilla($valor) {
        try {
            DB::enableQueryLog();
            $result = DB::connection('sqlsrv')->select('exec dbo.getPlantillas @ID_CatalogoDocumentos = ?', [$valor]);
            
            if (empty($result)) {
                throw new \Exception('No se encontraron plantillas.');
            }
    
            return $result;
        } catch (\Exception $e) {
            $queries = DB::getQueryLog();
            $lastQuery = end($queries); // Obtener la última consulta ejecutada
            $bindings = $lastQuery['bindings']; // Parámetros de la consulta
            $requestLog = $lastQuery['query']; // SQL de la consulta

            $error = [
                'status'=> '0',
                'fecha'=> date('Y-m-d H:i:s'),
                'descripcion' => 'Error en la funcion getPlantilla() del Modelo SaveLoan',
                'codigoError'=> $e->getCode(),
                'msnError' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'requestLog' => $requestLog,
                'responseLog' => $bindings
            ];    
            Log::error('Error en la funcion getPlantilla() del Modelo SaveLoan', $error);
            Log::error("Error al ejecutar dbo.getPlantilla: " . $e->getMessage());
    
            throw new \Exception($e->getMessage());
        }
    }
    
}
