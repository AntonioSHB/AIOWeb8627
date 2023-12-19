<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use App\Models\Configuracion;
use App\Models\SaveLoan;
use App\Models\SwapTransfer;
use Illuminate\Support\Facades\Log; 
use DateTime;



class valeFinancieroController extends Controller
{
    public function index()
    {   
        $selectedInsurance = null;
        $seguroId = Configuracion::obtenerValorPorParametro('SeguroID');
        $response = $this->getInsurance();
        $insurances = $this->getInsurance();
    
        $catalogoIdentificaciones = Configuracion::getCatalogoIdentificaciones();
    
        if ($response && array_key_exists('data', $response)) {
            $insuranceData = $response['data'];
            $filteredInsuranceData = array_filter($insuranceData, function ($insuranceItem) use ($seguroId) {
                return $insuranceItem['id_insurance'] == $seguroId;
            });
    
            if (count($filteredInsuranceData) > 0) {
                $selectedInsurance = array_values($filteredInsuranceData)[0];
            } else {
                $selectedInsurance = null;
            }
        } else {
            // Agregar mensaje de error controlado aquí
            $insuranceError = 'Hubo un problema al obtener los seguros, favor de reportarlo con el administrador del sistema';
        }
    
        $identificacionesId = Configuracion::obtenerValorPorParametro('IdentificacionesID');
        $identificationResponse = $this->getIdentifications();
        $selectedIdentification = null;
        $identificationData = null;
        if ($identificationResponse && array_key_exists('data', $identificationResponse)) {
            $identificationData = $identificationResponse['data'];
    
            if (array_key_exists($identificacionesId, $identificationData)) {
                $selectedIdentification = [
                    'id_identification' => $identificacionesId,
                    'name' => $identificationData[$identificacionesId]
                ];
            } else {
                $selectedIdentification = null;
            }
        } else {
            // Agregar mensaje de error controlado aquí
            $identificationError = 'Hubo un problema al obtener las identificaciones, favor de reportarlo con el administrador del sistema';
        }

    $branches = $this->getBranches();
    $message = null;

    $Plazas = auth()->user()->CodPlaza;
    $filteredBranch = null;

    if ($branches === false) {
        $message = [
            'type' => 'error',
            'text' => 'Ocurrió un error al obtener el catálogo de plazas. Favor de volver a intentar'
        ];
    } else {
        if (isset($branches['data'])) {
            foreach ($branches['data'] as $branch) {
                if ($branch['code'] === $Plazas) {

                    $filteredBranch = $branch;
                    break;
                }
            }
        }

        if ($filteredBranch === null) {
            $message = [
                'type' => 'warning',
                'text' => 'No se encontraron plazas registradas para el trámite. Favor de verificar'
            ];
        }
    }
    if ($filteredBranch) {
        $interestResponse = $this->getInterestByBranch($filteredBranch['id']);
        // dd($filteredBranch['id']);
     

    } else {
        $interestResponse = false;
        
    }
    
    if ($interestResponse === false) {
        $message = [
            'type' => 'error',
            'text' => 'Ocurrió un error al obtener los intereses. Favor de volver a intentar'
        ];
    }
    
        $catalogoServicios = $this->getCatalogoServicios();
        $message = null;
    
        if ($catalogoServicios === false) {
            $message = [
                'type' => 'error',
                'text' => 'Ocurrió un error al obtener el catalogo de servicios. Favor de volver a intentar'
            ];
        } elseif (empty($catalogoServicios)) {
            $message = [
                'type' => 'warning',
                'text' => 'No se encontraron servicios registrados para la plaza enviada. Favor de verificar'
            ];
        }
        try {
            $bancos = Configuracion::getCatalogoBancos();
    
            if (count($bancos) == 0) {
                $errorBancos = 'No se encontraron registros de Bancos';
            } else {
                $errorBancos = null;
            }
    
        } catch (\Exception $e) {
            $errorBancos = 'Ocurrió un error al obtener el catálogo de Bancos';
        }
        try {
            $vcBines = Configuracion::getBinesBancarios();
    
            if (count($vcBines) == 0) {
                $errorBines = 'No se encontraron registros de Bines';
            } else {
                $errorBines = null;
            }
    
        } catch (\Exception $e) {
            $errorBines = 'Ocurrió un error al obtener el catálogo de Bines';
        }
        $storesResponse = $this->getStores($Plazas);
        $errorStores = isset($storesResponse['error']) ? $storesResponse['error'] : null;
        $fechaServidor = Configuracion::getFechaServidor();
        $Plazas = auth()->user()->CodPlaza;
        $vTiendaID =$this->getStores($Plazas);
        // dd($vTiendaID);
        $relationships = $this->searchRelationship();
       

        

        

        return view('DPVALEFINANCIERO/valeFinanciero_v', 
    compact('catalogoServicios', 'message', 'filteredBranch', 'interestResponse', 
    'selectedIdentification', 'identificationData', 'identificacionesId', 'bancos', 'errorBancos', 
    'vcBines', 'errorBines', 'storesResponse', 'errorStores', 'fechaServidor', 'catalogoIdentificaciones', 
    'selectedInsurance','vTiendaID','insurances','relationships'));

        }
    
    
public function getCatalogoServicios()
{
    $Plazas = auth()->user()->CodPlaza;
    // CAMBIAR
    $result = DB::select("EXEC dbo.getCatalogoServicios @Codplaza = ?", [$Plazas]);
    return $result;
}
public function getBranches()
{
    $response = Http::post('http://aceqa.grupodp.com.mx:7083/pos/s2credit', [
        'branches' => [] 
    ]);

    if ($response->successful()) {
        return $response->json();
    }

    return false;
}
public function getBranchesData()
{
    $branchesResponse = $this->getBranches();

    if ($branchesResponse && array_key_exists('data', $branchesResponse)) {
        return $branchesResponse['data'];
    }

    return false;
}


public function getInterestByBranch($id_branch)
{
    $response = Http::post('http://aceqa.grupodp.com.mx:7083/pos/s2credit', [
        'interest-by-branch' => [
            'id_branch' => $id_branch
        ]
    ]);

    if ($response->successful()) {
 
        return $response->json();

    }

    return false;
}

public function getInsurance()
{
    $response = Http::post('http://aceqa.grupodp.com.mx:7083/pos/s2credit', [
        'insurance' => [] 
    ]);

    if ($response->successful()) {
        $responseData = $response->json();
        
        // Verificar si la respuesta es un array y si contiene la clave 'data'
        if (is_array($responseData) && array_key_exists('data', $responseData)) {
            return $responseData;
        }

        // En caso de que no haya una clave 'data', retornar un array vacío o cualquier otro valor por defecto
        return [];
    }

    return false;
}
public function getIdentifications()
{
    $response = Http::post('http://aceqa.grupodp.com.mx:7083/pos/s2credit', [
        'identifications' => [] 
    ]);

    if ($response->successful()) {
        $responseData = $response->json();
        
        // Verificar si la respuesta es un array y si contiene la clave 'data'
        if (is_array($responseData) && array_key_exists('data', $responseData)) {
            return $responseData;
        }

        // En caso de que no haya una clave 'data', retornar un mensaje de error personalizado
        return [
            'error' => 'Hubo un problema, favor de reportarlo con el administrador del sistema'
        ];
    }

    return false;
}

public function findStoreIdByCode($code, $stores)
{  
    foreach ($stores as $store) {
        if (is_array($store) && array_key_exists('code', $store) && array_key_exists('id', $store)) {
            if ($store['code'] == $code) {
                return $store['id'];
                
            }
        }
    }

    return null;
}


public function getStores($Plazas)
{
    $branches = $this->getBranchesData();
    dd($branches);
    $branchId = $this->findBranchIdByCode($Plazas, $branches);
    dd($branchId);
    
    if ($branchId === null) {
        return ['error' => 'No se encontró una sucursal con el código proporcionado.'];
    }

    $response = Http::post('http://aceqa.grupodp.com.mx:7083/pos/s2credit', [
        'store' => [
            'id_branch' => $branchId
        ]
    ]);

    if ($response->successful()) {
        $data = $response->json();
        // dd($data);
        if (empty($data)) {
            return ['error' => 'No se encontraron registros de Tiendas.'];
        }

        // Buscar la tienda con el código "V001" y obtener su ID
        // IMPORTANTE: El valor "V001" se debe cambiar más adelante por un valor dinámico
        $vTiendaID = $this->findStoreIdByCode('V001', $data['data']);
        // dd($vTiendaID);
          

        if ($vTiendaID === null) {
            return ['error' => 'No se encontró una tienda con el código "V001".'];
        }

        return $this->findStoreIdByCode('V001', $data['data']);
    }

    return ['error' => 'Ocurrió un error al obtener el catálogo de Tiendas.'];
}



public function findBranchIdByCode($Plazas, $branches)
{
    if (is_array($branches)) {
        foreach ($branches as $branch) {
            if (array_key_exists('code', $branch) && array_key_exists('id', $branch) && $branch['code'] === $Plazas) {
                return $branch['id'];
            }
        }
    }

    return null;
}


public function getCouponSearch()
{
    $coupon = request('coupon');

    $data = [
        'coupon-search' => [
            'coupon' => $coupon
        ]
    ];

    $response = Http::post('http://aceqa.grupodp.com.mx:7083/pos/s2credit', $data);

    if ($response->successful()) {
        return $response->json();
    }

    return false;
}
public function searchCustomer()
{
    $customer = request('customer');


    if (!$customer) {
        return false;
    }

    $data = [
        'search-customer' => [
            'data' => $customer
        ]
    ];

    $response = Http::post('http://aceqa.grupodp.com.mx:7083/pos/s2credit', $data);

    if ($response->successful()) {
        return $response->json();
    }

    return false;
}
public function searchRelationship()
{
    $response = Http::post('http://aceqa.grupodp.com.mx:7083/pos/s2credit', [
        'relationship' => [] 
    ]);

    if ($response->successful()) {
        return $response->json();
    }

    return false;
}
public function enviarSMS(Request $request)
{
    $mensaje = $request->input('mensaje');
    $telefonos = $request->input('telefonos');
    
    if (!is_array($telefonos)) {
        $telefonos = [$telefonos];
    }

    $response = Http::post('http://aceqa.grupodp.com.mx:7083/sms/api/EnviarSMS', [
        'mensaje' => $mensaje,
        'telefonos' => $telefonos
    ]);

    if ($response->successful()) {
        return response()->json(['status' => 'success', 'data' => $response->json()]);
    }

    return response()->json(['status' => 'error', 'message' => 'Error al enviar el SMS']);
}


public function getPeps(Request $request)
{
    $idCustomer = $request->input('customer');
    $folio = $request->input('folio');

    $response = Http::post('http://aceqa.grupodp.com.mx:7083/s2credit_api/s1/peps', [
        'idCustomer' => $idCustomer,
        'folio' => $folio
    ]);

    if ($response->successful()) {
        $data = json_decode($response->body());
       
        if ($data->status === '1') {
            return response()->json([
                'status' => $data->status,
                'message' => $data->msn
            ]);
        } else if ($data->status === '0') {
            return response()->json([
                'status' => $data->status,
                'message' => $data->msn
            ]);
        } else {
            return response()->json([
                'status' => $data->status,
                'message' => $data->msn
            ]);
        }
    } else {
        return response()->json([
            'status' => 'error',
            'message' => 'Ocurrió un error al consultar al cliente en lista Peps, favor de volver a intentar.'
        ]);
    }
}
public function obtenerImprimirDocs() {
    return Configuracion::obtenerValorPorParametro('ConfigAIOGlobal.ImprirmirDocs');
}


public function getFechaServidor() {
    $fechaServidor = Configuracion::getFechaServidor();
    return response()->json(['fechaServidor' => $fechaServidor]);
}

public function getBinesBancarios()
{
    try {
        $vcBines = Configuracion::getBinesBancarios();

        if (count($vcBines) == 0) {
            return response()->json(['error' => 'No se encontraron registros de Bines'], 404);
        } else {
            return response()->json($vcBines);
        }

    } catch (\Exception $e) {
        return response()->json(['error' => 'Ocurrió un error al obtener el catálogo de Bines'], 500);
    }
}

public function validarMonto(Request $request)
{
    $montoPrestamo = $request->input('textoSeleccionado');
    $interesResultado = $request->input('interestResult');
    $clienteDisponible = $request->input('customer_available');
    $seguroSeleccionado = $request->input('selectedInsurance');



    $seguroEncontrado = null;
    if (is_array($seguroSeleccionado) && array_key_exists('id_insurance', $seguroSeleccionado)) {
        $seguroEncontrado = $seguroSeleccionado;
    }


    if ($seguroEncontrado != null) {
        $tasaSeguro = $seguroEncontrado['rate'] * $request->input('numeroDeQuincenas');
        $montoTotalTramite = $montoPrestamo + $interesResultado + $tasaSeguro;
    } else {
        $montoTotalTramite = $montoPrestamo + $interesResultado;
    }

    $error = null;
    if ($clienteDisponible !== null && $clienteDisponible < $montoTotalTramite) {
        $error = ['error' => true, 'message' => 'El cliente alcanzó su tope de compra', 'disponible' => $clienteDisponible];
    } else {
        $error = ['error' => false];
    }

    return [
        'error' => $error,
        'montoTotal' => $montoTotalTramite
    ];
}

public function saveLoanController(Request $request)
{
    $folio = $request->input('folio');
    $cliente = $request->input('cliente');
    $distribuidor = $request->input('distribuidor');
    $montoPrestamo = $request->input('montoPrestamo');
    $quincenas = $request->input('quincenas');
    $idServicio = $request->input('idServicio');
    $idBanco = $request->input('idBanco');
    $service_value = $request->input('tarjetaClabe');
    $interest = $request->input('interestResult');
    $identificacion = $request->input('identificacion');
    $inputIdentificacion = $request->input('inputIdentificacion');
    $Plazas = auth()->user()->CodPlaza;
    $vTiendaID = $this->getStores($Plazas);
    // dd($vTiendaID);
    $tienda = $request->input('tienda');
    $seguroId = Configuracion::obtenerValorPorParametro('SeguroID');
  

    $data = [
        "save-loan" => [
            "id_branch" => $tienda,
            "id_store" => $vTiendaID, 
            "id_customer" => $cliente, 
            "id_distributor" => $distribuidor, 
            "id_coupon" => $folio,
            "id_service" => $idServicio, 
            "id_bank" => $idBanco,
            "id_cell_provider" => "", 
            "transfer" => "0",
            "service_value" => $service_value, 
            "secure_code" => "",
            "interest" => $interest, 
            "id_identification" => $identificacion, 
            "identification_value" => $inputIdentificacion, 
            "id_amount" => $montoPrestamo,
            "id_fortnight" => $quincenas, 
            "id_relationship" => "", 
            "id_insurance" => $seguroId, 
            "beneficiary_name" => "",
            "beneficiary_middle_name" => "",
            "beneficiary_last_name" => "",
            "beneficiary_second_last_name" => ""
        ]
    ];           
    try {
        $response = SaveLoan::saveLoan($data);
        return response()->json([
            'status' => 1,
            'msn' => 'El préstamo se guardó correctamente.',
            'data' => $response
        ]);
    } catch (\Exception $e) {
        if ($e->getMessage() == 'Prestamo guardado') {
            return response()->json([
                'status' => 1,
                'msn' => $e->getMessage()
            ]);
        } else {
            return response()->json([
                'status' => 0,
                'msn' => 'Error al guardar el préstamo: ' . $e->getMessage()
            ]);
        }
    }
    
}
public function storeBeneficiary(Request $request)
    {
        $request->validate([
            'id_relationship' => 'required|string',
            'id_purchase' => 'required|string',
            'name' => 'required|string',
            'last_name' => 'required|string',
            'second_last_name' => 'required|string',
        ]);

   
        $beneficiaryData = [
            'id_relationship' => $request->input('id_relationship'),
            'id_purchase' => $request->input('id_purchase'),
            'name' => $request->input('name'),
            'last_name' => $request->input('last_name'),
            'second_last_name' => $request->input('second_last_name'),
        ];

        try {
            $response = SaveLoan::saveBeneficiary($beneficiaryData);

            return response()->json($response);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 0,
                'message' => 'Error al guardar el beneficiario: ' . $e->getMessage(),
            ], 400);
        }
    }
    public function swapTransfer(Request $request)
    {
               
        $numReference = Configuracion::obtenerValorPorParametro('numReference');
        $rawTransferData  = $request->only([
            'sociedad',
            'transferId',
            'description',
            'account',
            'amount',
            'bank',
            'owner',
            'ownerPhone',
        ]);

        $rawTransferData['numReference'] = $numReference;

        $transferData = $rawTransferData;
    $transferData['amount'] = (int) $rawTransferData['amount'];
    try {
        $response = SaveLoan::swapTransfer($transferData);
        return response()->json($response);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 0,
            'message' => $e->getMessage(),
            'data' => [], 
        ]);
    }
    }
    public function getSwapTransfer(Request $request)
    {
               

        $rawTransferData  = $request->only([
            'sociedad',
            'transferId',
        ]);


        $transferData = $rawTransferData;
        // dd($transferData);

    try {
        $response = SaveLoan::getSwapTransfer($transferData);
        return response()->json($response);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 0,
            'message' => $e->getMessage(),
            'data' => [],
        ]);
    }
    }
    public function tokaApi(Request $request)
    {
               
        

        $rawTransferData  = $request->only([
            'sociedad',
            'NumeroTarjeta',
            'Nombre',
            'Paterno',
            'Materno',
            'id',

        ]);

        $transferData = $rawTransferData;

    try {
        $response = SaveLoan::tokaApi($transferData);
        return response()->json($response);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 0,
            'message' => $e->getMessage(),
            'data' => [], 
        ]);
    }
    }
    public function cambioEstado(Request $request)
    {
                      

        $antesTransferData  = $request->only([
            'sociedad',
            'NumeroTarjeta',
            'Estado',
        ]);

        $transferData = $antesTransferData;

    try {
        $response = SaveLoan::cambioEstado($transferData);
        return response()->json($response);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 0,
            'message' => $e->getMessage(),
            'data' => [], 
        ]);
    }
    }
    public function tokaDispersiones(Request $request)
    {
                      

        $antesTransferData  = $request->only([
            'sociedad',
            'NumeroTarjeta',
            'Estado',
        ]);

        $transferData = $antesTransferData;

    try {
        $response = SaveLoan::tokaDispersiones($transferData);
        return response()->json($response);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 0,
            'message' => $e->getMessage(),
            'data' => [], 
        ]);
    }
    }
    public function store(Request $request)
{
    $IDCliente = $request->input('IDCliente');
    $SecFile = "0";
    $NoCuenta = ""; 
    $Importe = $request->input('Importe');
    $Intereses = $request->input('Intereses');
    $DPVale=$request->input('NumFact');
    $Tipo = "1";
    $CodAlmacen = "001";
    $NumFact = $request->input('NumFact');
    $IDAsociado = $request->input('IDAsociado');
    $CodPlaza = "MZT"; //CAMBIAR
    $Oficina = "02"; //CAMBIAR
    $AltaTarjeta = "";
    $NoIFE = $request->input('NoIFE');
    $Fecha = $request->input('Fecha');
    $fechaObj = DateTime::createFromFormat('Y-m-d', $Fecha);
    $fechaObj->setTime(15, 26, 30);
    $fechaObj->setTimezone(new DateTimeZone('UTC')); // Si es necesario, ajusta la zona horaria
    $fechaObj->setTimestamp($fechaObj->getTimestamp() + 183000); // Agregar 183,000 microsegundos
    $fechaStr = $fechaObj->format('Y-m-d H:i:s.u');
    $FolioFIIntereses = "";
    $FolioFIMonto = $request->input('FolioFIMonto'); //DUDA
    $Usuario = $request->input('Usuario');
    $Benef = $request->input('Benef');
    $Banco = $request->input('Banco');
    $Celular = $request->input('Celular');
    $CompañiaCelular = "";
    $Clabe = $request->input('Clabe');
    $Transfer = "0";
    $NumeroTarjeta = $request->input('NumeroTarjeta');
    $ServicioId = $request->input('ServicioId');
    $Codigo = $request->input('Codigo');
    $NombreCliente = $request->input('NombreCliente');
    $Generado = "1";
    $FechaDispersion = date("Y-m-d H:i:s.u", strtotime(now()));
    $CodigoSeguridad = "";
    $RFC = $request->input('RFC');
    $FechNac = $request->input('FechNac'); 
    $Dispersion = $request->input('Dispersion');
    $NoColaborador = auth()->user()->id;
    $Estatus = $request->input('Estatus');
    $Sociedad = $request->input('Sociedad');

    // dump($IDCliente, $SecFile, $NoCuenta, $Importe, $Intereses, $Tipo, $CodAlmacen, $NumFact,
    //     $IDAsociado, $CodPlaza, $Oficina, $AltaTarjeta, $NoIFE, $Fecha, $FolioFIIntereses,
    //     $FolioFIMonto, $Usuario, $Benef, $Banco, $Celular, $CompañiaCelular, $Clabe, $Transfer,
    //     $NumeroTarjeta, $ServicioId, $Codigo, $NombreCliente, $Generado, $FechaDispersion,
    //     $CodigoSeguridad, $RFC, $FechNac, $Dispersion, $NoColaborador, $Estatus, $Sociedad);

    $result = SaveLoan::store(
                $IDCliente, $SecFile, $NoCuenta, $Importe, $Intereses, $DPVale, $Tipo, $CodAlmacen, $NumFact,
                $IDAsociado, $CodPlaza, $Oficina, $AltaTarjeta, $NoIFE, $fechaStr, $FolioFIIntereses,
                $FolioFIMonto, $Usuario, $Benef, $Banco, $Celular, $CompañiaCelular, $Clabe, $Transfer,
                $NumeroTarjeta, $ServicioId, $Codigo, $NombreCliente, $Generado, $FechaDispersion,
                $CodigoSeguridad, $RFC, $FechNac, $Dispersion, $NoColaborador, $Estatus, $Sociedad
    );

    // Comprueba si la operación fue exitosa
    if ($result) {
        return response()->json(['message' => 'Datos guardados correctamente'], 200);
    } else {
        return response()->json(['message' => 'Error al guardar los datos'], 400);
    }
}
public function RegistrarTramite(Request $request)
{
    $currentDate = new DateTime();
    $formattedDate = $currentDate->format('Y-m-d H:i:s.u');
    $noTarjeta = $request->input('numeroTarjeta');
    $noTarjetaVarbinary = pack('H*', dechex($noTarjeta));


    // SP
    $CodPlaza = "MZT"; // Se obtiene del valor de ConfigAIO.Plaza
    $TiendaID = "1"; // Se obtiene del valor de ConfigAIO.Tienda
    $Caja = "1"; // Se obtiene del valor de ConfigAIO.Caja
    $FechaTienda = $formattedDate;
    $SociedadID = $request->input('sociedad');
    $TipoID = "1";
    $DPVale = $request->input('NumFact');
    $DistribuidorID = $request->input('IDAsociado');
    $ClienteID = $request->input('IDCliente');
    $NoPlazo = "10"; //falta el select quincenas
    $Importe = $request->input('importe');
    $Interes = $request->input('intereses');
    $Seguro = "64"; // REVISAR vMontoSeguro
    $VigFechaInicio = "2022-10-06"; //REVISAR vFechaInicio
    $VigFechaFin = "2022-12-20"; //REVISAR vFechaFin

    $ServicioID =  $request->input('idServicio');
    $BancoID = "16"; // Se obtiene del valor BancoID del combo Banco en el formulario
    $Banco = $request->input('Banco');

    $TarjetaClabe = $noTarjetaVarbinary;
    $EstatusDispToka = "1"; // Establecer según el servicio seleccionado en el combo
    $EstatusDispersion ="1";
    $Estatus = $request->input('estatus');

    $NombreDistribuidor = $request->input('distCompleto');
    $NombreCliente = $request->input('NombreCliente');

    $IdentificacionID = "1"; 
    $Identificacion = $request->input('inputIdentificacion');
    $Celular = $request->input('inputCel');

    $Beneficiario = $request->input('Benef') . "-" . $request->input('percentage');
    $NoColaborador = "3456"; // Obtener de la variable User
    // $FotoIdentiFrontral = $request->input('frontIneBase64Image'); // Obtener en base64
    $frontIneBase64Image = $request->input('frontIneBase64Image');
    $imageDataFront = explode(',', $frontIneBase64Image);
    $FotoIdentiFrontral = base64_decode($imageDataFront[1]);
    $backIneBase64Image = $request->input('backIneBase64Image');
    // $backIneBase64Image = $request->input('backIneBase64Image');
    $imageDataBack = explode(',', $backIneBase64Image);
    $FotoIdentiTrasera = base64_decode($imageDataBack[1]);
    $CodigoTramite = "0F82158E-44EF-4A6F-9184-CC6B75ECA5C1"; // Generar con NEWID en SQL Server
    // FVale: Generar y modificar en documento PDF según si es numérico (físico) o electrónico
    $base64Image = $request->input('globalBase64Image');
    $imageData = explode(',', $base64Image);
    $FVale = base64_decode($imageData[1]);

    // $FVale = $request->input('globalBase64Image'); // Obtener en base64 o generar/modificar PDF
    // Añadiendo las variables faltantes
    $UsuarioID = 123; // Reemplazar por el ID de usuario actual
    $Ticket = '0x41'; // Reemplazar por el valor de ticket en formato base64
    $Poliza = '0x41'; // Reemplazar por el valor de póliza en formato base64
    $FuturoABC = '0x41'; // Reemplazar por el valor de FuturoABC en formato base64

    // Asegurarse de que los tipos de datos coincidan con la lista proporcionada
    $Importe = (float) $Importe;
    $Interes = (float) $Interes;
    $Seguro = (float) $Seguro;

    $EstatusDispToka = (bool) $EstatusDispToka;

    $Estatus = (bool) $Estatus;
    try {
        $result = SaveLoan::registrarTramite(
            $CodPlaza, $TiendaID, $Caja, $SociedadID, $TipoID, $DPVale, $DistribuidorID,
            $ClienteID, $NoPlazo, $Importe, $Interes, $Seguro, $VigFechaInicio, $VigFechaFin,
            $ServicioID, $BancoID, $Banco, $TarjetaClabe, $EstatusDispToka, $EstatusDispersion,
            $NombreDistribuidor, $NombreCliente, $IdentificacionID, $Identificacion, $Celular,
            $Beneficiario, $UsuarioID,$Estatus, $decodedImageFront, $FotoIdentiTrasera,
            $FVale, $Ticket, $Poliza, $FuturoABC
        );
    

        if ($result !== false && $result !== null) {
            return response()->json(['message' => 'Datos guardados correctamente'], 200);
        } else {
            return response()->json(['message' => 'Error al guardar los datos'], 400);
        }
    } catch (\Exception $e) {
        Log::error('Error en RegistrarTramite: ' . $e->getMessage() . ' en la línea ' . $e->getLine() . ' del archivo ' . $e->getFile());
        return response()->json(['message' => 'Error al guardar los datos, por favor verifica el log para más información'], 400);
    }

}

}
