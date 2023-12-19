<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Configuracion;
use App\Models\SaveLoan;
use Illuminate\Support\Facades\Log;
use App\Services\SessionService;
use App\Services\EncryptService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

use function PHPUnit\Framework\isEmpty;

class ConsultaTramiteController extends Controller
{
    protected $sessionService;
    protected $request;
    protected $EncryptService;

    public function __construct(Request $request, SessionService $sessionService, EncryptService $encryptService)
    {
        $this->request = $request;
        $this->sessionService = $sessionService;
        $this->EncryptService = $encryptService;
    }

    public function index()
    {        
        $title = 'Consultar trámite.';
                
        //Instrucción para controlar el tiempo de sesion activa
        $sessionLifetime = $this->sessionService->getSessionLifetime($this->request);

        return view('home.aplicaciones.consultaTramite.index',
                    compact('title', 'sessionLifetime')
                    );        
    }

    public function consultarTramite(Request $request){
        
        $DpVale = $request->input('inputNoTramite');

        $validatorResponse = [];

        $messages = [
            'required' => 'El campo es requerido.',            
            'inputNoTramite.min' => 'El folio del vale debe tener exactamente 10 caracteres.',
            'inputNoTramite.max' => 'El folio del vale debe tener exactamente 10 caracteres.'
        ];

        $validator = Validator::make($request->all(), [
            'inputNoTramite' => 'required|min:10|max:10'            
        ], $messages);

        if ($validator->fails()) {
            
            $errors = $validator->errors();
            $counter = 0;

            foreach($errors->all() as $message){
                $field = $errors->keys()[$counter];                    
                $validatorResponse[$field] = $message;
                $counter++;
            }
            
            $response["errors"] = $validatorResponse;
            return response($response);
        }

        try {            
            DB::enableQueryLog();
            $DpValeVerificado = Configuracion::verificarDPVale($DpVale);
            // dd($DpValeVerificado);
            if ($DpValeVerificado !== null) {
                // dd("El vale ya fue consultado");
            $response = Configuracion::ConsultarTramite($DpValeVerificado);            
            } else{
            $response = Configuracion::ConsultarTramite($DpVale);            

            }
            if (property_exists($response[0], 'PrestamoID')) {
                
                if (in_array($response[0]->ServicioID, ['13', '14'])) {
                    $dat = [
                        'sociedad' => $response[0]->Sociedad,
                        'transferId' => $response[0]->DPVale.$response[0]->Intentos //se concatena el no del vale y el intento
                    ];
                    
                    //Se verifica el status del tramite
                    $resultStatusSwap = SaveLoan::getSwapTransfer($dat);

                    $Detalle = '0';
                    if ($resultStatusSwap['result']['status'] == 'returned') {
                        if(!array_key_exists('failureReason', $resultStatusSwap['result'])){
                            $Detalle = 'sin información';
                        }else if(array_key_exists('failureReason', $resultStatusSwap['result'])){
                            if ($resultStatusSwap['result']['failureReason'] == '') {
                                $Detalle = 'sin información';
                            }else{
                                $Detalle = $resultStatusSwap['result']['failureReason'];
                            }
                        }
                    }
                                        
                    return response()->json([
                        "serviceDisp" => 1, //Correspondiente a Swap
                        "Tramite" => $response[0]->DPVale,
                        "Servicio" => $response[0]->Servicio,
                        "Banco" => $response[0]->Banco,
                        "Cuenta" => $this->maskTarjeta($resultStatusSwap['result']['account']),
                        "Estatus" => $resultStatusSwap['result']['status'],
                        "Detalle" => $Detalle
                    ]);
                    
                }else if(in_array($response[0]->ServicioID, ['9', '15'])){
                    //Codigo para consultar TOKA
                    
                    $dat = [
                        'sociedad' => $response[0]->Sociedad,
                        'NumeroTarjeta' => $response[0]->TarjetaClabe
                        //'NumeroTarjeta' => $this->descifrarTarjeta(1, $result->Fecha, base64_decode($result->TarjetaClabe))
                    ];
                    
                    //Se verifica el status del tramite
                    $resultStatusToka = SaveLoan::TokaApiTarjetas($dat);

                    if (array_key_exists('_Object', $resultStatusToka)) {
                        
                        if (!array_key_exists('Saldo', $resultStatusToka['_Object'])) {
                            return response()->json([
                                "serviceDisp" => 2, //Correspondiente a Toka
                                'tokaEmpty' => true,
                            ]);
                        }else{
                            $detalle = $resultStatusToka['_Object']['Saldo'];                            
                            
                            return response()->json([
                                "serviceDisp" => 2, //Correspondiente a Toka
                                "Tramite" => $response[0]->DPVale,
                                "Servicio" => $response[0]->Servicio,
                                "Banco" => $response[0]->Banco,
                                "Cuenta" => $this->maskTarjeta($dat['NumeroTarjeta']),
                                "Estatus" => 'Pagado',
                                "Detalle" => 'Saldo: '.$detalle
                            ]);
                        }

                    }else {                        
                        
                        return response()->json([
                            "serviceDisp" => 2, //Correspondiente a Toka
                            'tokaEmpty' => true,
                        ]);                        
                    }
                }
                
            }else {
                return response()->json($response);
            }                        
        }catch (\Throwable $t){                        
            $queries = DB::getQueryLog();
            $lastQuery = end($queries); // Obtener la última consulta ejecutada
            $bindings = $lastQuery['bindings']; // Parámetros de la consulta
            $requestLog = $lastQuery['query']; // SQL de la consulta
            $error = [
                'status' => '0',
                'fecha' => date('Y-m-d H:i:s'),
                'descripcion' => 'Error en la funcion ConsultarTramite($DpVale).',
                'codigoError' => $t->getCode(),
                'msnError' => $t->getMessage(),
                'linea' => $t->getLine(),
                'archivo' => $t->getFile(),
                'requestLog' => $requestLog, // Agregar el SQL al log
                'responseLog' => $bindings, // Agregar los parámetros al log
            ];
            Log::error('Error en la funcion consultarTramite()', $error);
            return false;
        }
    }

    //Función para descifrar la tarjeta
    public function descifrarTarjeta($Indicador, $Fecha, $TarjetaClabe){
        
        try{
            DB::enableQueryLog();

            if ($TarjetaClabe != null && $Fecha != null){                
                
                $encryptName = $this->EncryptService->getEncrypt($Fecha);                
                $resultDecrypt = $this->EncryptService->Encrypt($Indicador, $encryptName[0]->Nombre, $TarjetaClabe);

                if ($resultDecrypt){                    
                    return $resultDecrypt['Resultado'][0]['datos'][0]['data'];                    
                }else{
                    return false;
                }
            }
            else{
                return false;
            }
        }catch(\Throwable $t){            
            $queries = DB::getQueryLog();
            $lastQuery = end($queries); // Obtener la última consulta ejecutada
            $bindings = $lastQuery['bindings']; // Parámetros de la consulta
            $requestLog = $lastQuery['query']; // SQL de la consulta
            $error = [
                'status' => '0',
                'fecha' => date('Y-m-d H:i:s'),
                'descripcion' => 'Error en la funcion descifrarTarjeta().',
                'codigoError' => $t->getCode(),
                'msnError' => $t->getMessage(),
                'linea' => $t->getLine(),
                'archivo' => $t->getFile(),
                'requestLog' => $requestLog, // Agregar el SQL al log
                'responseLog' => $bindings, // Agregar los parámetros al log
            ];
            Log::error('Error en la funcion descifrarTarjeta()', $error);
            return false;
        }        
    } 

    //Función para emnascarar caracteres de la tarjeta
    public function maskTarjeta($TarjetaClabe){
        if (strlen($TarjetaClabe) == 16) {                    
            $maskedValue = substr($TarjetaClabe, 0, 6) . '******' . substr($TarjetaClabe, -4);            
            return $maskedValue;
        }else if(strlen($TarjetaClabe) == 18){
            $maskedValue = substr($TarjetaClabe, 0, 6) . '********' . substr($TarjetaClabe, -4);
            return $maskedValue;
        }else{
            return false;
        }
    }




}
