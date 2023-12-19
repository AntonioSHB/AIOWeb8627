<?php

namespace App\Http\Controllers\Home;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Auth;
use Session;
use DateTime;
use DateTimeZone;
use App\Services\UserService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Services\ConfigService;

use App\Models\Configuracion;
use App\Models\SaveLoan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use App\Services\SessionService;
use App\Services\PreCargaPreDirectosService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;




class PrestamosDirectosController extends Controller
{
    private $sessionService;  
    protected $catalogoService;

    public function __construct(SessionService $sessionService,PreCargaPreDirectosService $catalogoService)  
    {

        $this->sessionService = $sessionService; 
        $this->catalogoService = $catalogoService;

    }
    public function index(Request $request)
    {
        // dd($request->session()->all());
        $title = "Préstamos Directos";
        $sessionLifetime = $this->sessionService->getSessionLifetime($request);
        $GetCatalogoServicios = $this->catalogoService->getCatalogoServicios();
        // dd($GetCatalogoServicios);
        $branches = $this->catalogoService->getBranches();
        // dd($branches);
        $CodPlaza = session('CodPlaza');
        $TiendaID = session('TiendaID');
        if (!isset($branches['error'])) {
        $plazaId = $this->getBranchId($branches, $CodPlaza);
        } else {
            $plazaId = null;
        }
        $vCBines = Configuracion::getBinesBancarios();
        // dd($vCBines);
        

        if ($plazaId === null) {
            $errorMessage = "No se encontró una plaza con el código {$CodPlaza}. Contacte al administrador.";
        }
        $stores = $this->catalogoService->getStores($plazaId);
        if (isset($stores['error'])) {
            $errorMessage = $stores['error'];
        } elseif (empty($stores['data'])) {
            $errorMessage = "No se encontraron tiendas registradas para la plaza. Contacte al administrador.";
        }
        // $errorMessage = null;
        // if ($GetCatalogoServicios === false) {
        //     $errorMessage = "Ocurrió un error al conectar con la base de datos SQL. Contacte al administrador.";
        // } elseif (empty($GetCatalogoServicios)) {
        //     $errorMessage = "No se encontraron servicios registrados para la plaza. Contacte al administrador.";
        // }
        
        $Identificaciones = $this->catalogoService->getIdentificaciones();
        // dd($Identificaciones);
       if (isset($Identificaciones['error'])) {
            $errorMessage = $Identificaciones['error'];
        } elseif (empty($Identificaciones['data'])) {
            $errorMessage = "No se encontraron identificaciones registradas para la plaza. Contacte al administrador.";
        }
        $Valor = null; // establecer a null por defecto

        // Verificar si el índice 0 existe antes de intentar acceder a Valor
        if (isset($Identificaciones[0]) && isset($Identificaciones[0]->Valor)) {
            $Valor = explode(',', $Identificaciones[0]->Valor); // convierte el string "Valor" en un array
        }
           
        $identificacionesRaw = $this->catalogoService->getCatalogoIdentificaciones();
        if (isset($identificacionesRaw['error'])) {
            $CatalogoIdentificaciones = $identificacionesRaw; // si hay un error, simplemente asigna el array de error
        } else {
            $CatalogoIdentificaciones = collect($identificacionesRaw) // si no, convierte y filtra
                ->whereIn('IdentificacionID', $Valor);
        }

        // $CatalogoIdentificaciones = collect($this->catalogoService->getCatalogoIdentificaciones()) // convierte el resultado en una colección
        //     ->whereIn('IdentificacionID', $Valor); // obtén solo los objetos que correspondan a los ID's en "Valor"
        
        if (!isset($CatalogoIdentificaciones['error'])) {
            $selectOptions = $CatalogoIdentificaciones->pluck('Nombre', 'IdentificacionID');
        } else {
            $selectOptions = []; // o cualquier valor predeterminado que quieras asignar en caso de error
        }
        
        $bancos = $this->catalogoService->getCatalogoBancos();
        // if($bancos ===false){
        //     $errorMessage = "Ocurrió un error al conectar con la base de datos SQL. Contacte al administrador.";
        // }elseif(empty($bancos)){
        //     $errorMessage = "No se encontraron bancos registrados para la plaza. Contacte al administrador.";
        // }
        // dd($bancos);
        $montoMinPrestamo = $this->catalogoService->getMontoMinPresDis();
        $description = session('CodPlaza').session('TiendaID');
        // if (isset($GetCatalogoServicios['error'])) {
        //     // Restaurar datos de la sesión desde USERDATA si está presente
        //     $originalUserData = $request->session()->get('USERDATA');
        //     if ($originalUserData) {
        //         $request->session()->put('Usuario', $originalUserData['Usuario']);
        //         // Puedes hacerlo para otros campos si es necesario
        //     }
        // }
        // if (isset($bancos['error'])) {
        //     // Restaurar datos de la sesión desde USERDATA si está presente
        //     $originalUserData = $request->session()->get('USERDATA');
        //     if ($originalUserData) {
        //         $request->session()->put('Usuario', $originalUserData['Usuario']);
        //         // Puedes hacerlo para otros campos si es necesario
        //     }
        // }

        $resources = [
            'servicios' => $GetCatalogoServicios,
            'bancos' => $bancos,
            'CatalogoIdentificaciones' => $CatalogoIdentificaciones,
            'montoMinPrestamo' => $montoMinPrestamo,
            'bines' => $vCBines,
            'branches' => $branches,
            'stores' => $stores,
            'Identificaciones' => $Identificaciones,

        ];
        // dd($resources);
        $errorMessage = null;
        foreach ($resources as $key => $resource) {
            if ($resource === false) {
                $errorMessage = "Ocurrió un error al conectar con la base de datos SQL. Contacte al administrador.";
                break; // Terminar el bucle si se encuentra un error
            } elseif (empty($resource)) {
                $errorMessage = ($key == 'servicios' ? "No se encontraron servicios" : "No se encontraron bancos") . " registrados para la plaza. Contacte al administrador.";
                // dd($errorMessage);
                break; // Terminar el bucle si se encuentra un error
            }
        }
        // Si se encontró un error en alguno de los recursos
        if (isset($GetCatalogoServicios['error']) || isset($bancos['error']) || isset($CatalogoIdentificaciones['error']) || 
        isset($montoMinPrestamo['error']) || isset($vCBines['error']) || isset($branches['error']) || isset($stores['error']) || isset($Identificaciones['error'])) {
            $originalUserData = $request->session()->get('USERDATA');
            if ($originalUserData) {
                $request->session()->put('Usuario', $originalUserData['Usuario']);
            }
        }
    
        return view('home.aplicaciones.prestamosDirectos.prestamosDirectos_v', compact('title', 'sessionLifetime', 'GetCatalogoServicios', 'errorMessage', 
        'selectOptions', 'CatalogoIdentificaciones' , 'stores', 'TiendaID', 'bancos', 'montoMinPrestamo', 'vCBines', 'description', 'branches','Identificaciones'));
    }
    
    public function canHaveLoan(Request $request)
    {
        $number = $request->input('number');
    
        $data = [
            'can-have-loan' => [
                'number' => $number
            ]
        ];
                    // $response = Http::timeout(5)->get('https://httpbin.org/delay/60');
            // $response = [];
        $response = null;

        try {
            $response = Http::post(ConfigService::obtenerUrlS2Credit(), $data);
                                // $response = Http::timeout(5)->get('https://httpbin.org/delay/60');
                                // dd($response);
            // $response = [];
            if(empty($response)){
                return ['error' => 'No se obtuvo información al consultar el servicio. Contacte al administrador.'];
            }

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            $error = [
                'status'=> '0',
                'fecha'=> date('Y-m-d H:i:s'),
                'descripcion' => 'Error de timeout en la función canHaveLoan() al consumir el servicio s2credit',
                'codigoError'=> $e->getCode(),
                'msnError' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'requestLog' => $requestData,
                'responseLog' => $response,
            ];    
            Log::error('Timeout en la función canHaveLoan() al consumir el servicio s2credit', $error);
            return ['error' => 'Ocurrió un error al buscar el distribuidor, favor de volver a intentar.'];
        } catch (\Throwable $t) {
            $error = [
                'status' => '0',
                'fecha' => date('Y-m-d H:i:s'),
                'descripcion' => 'Error en la funcion canHaveLoan() al consumir el servicio.',
                'codigoError' => $t->getCode(),
                'msnError' => $t->getMessage(),
                'linea' => $t->getLine(),
                'archivo' => $t->getFile(),
                'requestLog' => $data,
                'responseLog' => isset($response) ? $response : 'No response', // Verificar si $response existe
            ];
            Log::error('Error en la funcion canHaveLoan() al consumir el servicio', $error);
            return false;
        }
    
        if (isset($response) && $response->successful()) {
            return $response->json();
        }
    
        return false;
    }
    
    public function getBranchId($branches, $CodPlaza)
    {
        if (isset($branches['data'])) {
            foreach ($branches['data'] as $branch) {
                if ($branch['code'] === $CodPlaza) {
                    return $branch['id'];
                }
            }
        }
    
        return null;
    }
    
public function obtenerMontoMinimoPresDis(PreCargaPreDirectosService $service) {
    $montoMinimo = $service->obtenerMontoMinimoPresDis();
    return response()->json(['montoMinimo' => $montoMinimo]);
}

// public function ValidaPrestamoxTarjetaDias (Request $request){
//     $ValidaTarjetaDias = Configuracion::obtenerValorPorParametro('ValidaTarjetaDias');       

//     $tarjetaClabe = $request->input('tarjetaClabe');
//     // dd($tarjetaClabe);

//     $result = DB::select('exec dbo.ValidaPrestamoxTarjetaDias ?, ?', array($tarjetaClabe, $ValidaTarjetaDias));

//     return response()->json($result);
// }
public function ValidaPrestamoxTarjetaDias (Request $request){
    try {
        DB::enableQueryLog();

        $ValidaTarjetaDias = Configuracion::obtenerValorPorParametro('ValidaTarjetaDias');
        // dd($ValidaTarjetaDias);
        // Verificar si ValidaTarjetaDias es un error
        if (isset($ValidaTarjetaDias['error'])) {
            return response()->json($ValidaTarjetaDias);
        }

        $tarjetaClabe = $request->input('tarjetaClabe');

        $result = DB::select('exec dbo.ValidaPrestamoxTarjetaDias ?, ?', array($tarjetaClabe, $ValidaTarjetaDias));
        // $result = [];

        // Verificar si los resultados están vacíos
        if (empty($result)) {
            return response()->json(['error' => 'No se encontraron registros. Contacte al administrador.']);
        }
        return response()->json($result);

    } catch (\PDOException $e) {
        // Usa el método handleException del servicio PreCargaPreDirectosService
        $errorMessage = PreCargaPreDirectosService::handleException($e, 'Ocurrió un error al conectar con la base de datos SQL en ValidaPrestamoxTarjetaDias.');
        return response()->json(['error' => $errorMessage]);

    } catch (\Throwable $t) {
        $errorMessage = PreCargaPreDirectosService::handleException($t, 'Ocurrió un error inesperado en ValidaPrestamoxTarjetaDias.');
        return response()->json(['error' => $errorMessage]);
    }
}


public function validarDatos(Request $request)
{
    $messages = [
        'predirDistribuidor.required' => 'Campo Requerido',
        'predirDistribuidor.max' => 'El campo Distribuidor no debe tener más de :max caracteres',
        'predirNoIdentificacion.required' => 'Campo Requerido',
        'predirIdentificacion.required' => 'Campo Requerido',
        'predirServicio.required' => 'Campo Requerido',
        'predirBanco.required' => 'Campo Requerido',
        'predirTarjetaClabe.required' => 'Campo Requerido',
        'predirTarjetaClabe.max' => 'El campo Tarjeta Clabe no debe tener más de :max caracteres',
        'predirMontoPrestamo.required' => 'Campo Requerido',
        'predirMontoPrestamo.numeric' => 'El campo Monto Prestamo debe ser numérico',
        'predirMontoPrestamo.min' => 'El campo Monto Prestamo debe ser mayor a :min',
        'predirNoQuincenas.required' => 'Campo Requerido',
        'predirBanco.required' => 'Campo Requerido',
        'predirCelular.required' => 'Campo Requerido',
        'predirCelular.digits' => 'El campo Celular debe tener :digits caracteres',];
    
    
    $rules = [
        'predirDistribuidor' => 'required|max:10',
        'predirNoIdentificacion' => 'required|max:30',
        'predirMontoPrestamo' => 'required',
        'predirNoQuincenas' => 'required',
        'predirIdentificacion' => 'required',
        'predirServicio' => 'sometimes|required',
        'predirBanco' => 'sometimes|required',
        'predirTarjetaClabe' => 'required|max:18',
        'predirCelular' => 'required|digits:10|numeric',

    ];

    $validator = Validator::make($request->all(), $rules, $messages);

    $validator->sometimes('predirBanco', 'required', function ($input) {
        return $input->predirBanco !== '973'; // Esto significa que 'predirBanco' es requerido sólo si su valor no es '973'.
    });

    if ($validator->fails()) {
        $errors = $validator->errors();
        $validatorResponse = [];

        foreach($errors->messages() as $field => $message) {                   
            $validatorResponse[$field] = $message[0];
        }
        
        $response["errors"] = $validatorResponse;

        return response()->json($response);
    }   else {
        $response = [
            'success' => true,
            'message' => 'Validación exitosa.',
        ];
        
        return response()->json($response);
}


}
public function save(Request $request) {
    // Obtén los datos de la solicitud
    $data = $request->json()->all();

    // Llama al método save del servicio con los datos
    $response = PreCargaPreDirectosService::save($data);

    // Verifica si la respuesta tiene una propiedad 'ErrorMessage', lo que indica un error
    if (isset($response['error'])) {
        // Retorna un mensaje de error al cliente
        return response()->json(['status' => 'error', 'message' => $response['error']], 404); // O el código que consideres apropiado
    } else if (isset($response['status']) && $response['status'] == 1) {
        // Retorna los datos de la compra
        return response()->json($response);
    }
}

public function restoreUser(Request $request)
{
    $originalUserData = $request->session()->get('USERDATA');
    if ($originalUserData && isset($originalUserData['Usuario'])) {
        $request->session()->put('Usuario', $originalUserData['Usuario']);
        return response()->json(['success' => true, 'message' => 'Usuario restaurado con éxito.']);
    } else {
        return response()->json(['success' => false, 'message' => 'No se pudo restaurar el usuario.']);
    }
}

}
