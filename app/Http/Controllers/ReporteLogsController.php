<?php

namespace App\Http\Controllers;

use App\Services\SessionService;
use Illuminate\Http\Request;
use App\Services\ReporteLogsService;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

class ReporteLogsController  extends Controller
{
    protected $sessionService;
    protected $request;
    protected $reporteLogsService;


    public function __construct(Request $request, SessionService $sessionService, ReporteLogsService $reporteLogsService)
    {
        $this->request = $request;
        $this->sessionService = $sessionService;
        $this->reporteLogsService = $reporteLogsService;


    }
    public function index()
    {        
        $data["title"] = "Reporte de logs.";
        $modulos = [];
        $plazas = [];
        $sessionLifetime = $this->sessionService->getSessionLifetime($this->request);

        // dd(session()->all());
        try {
            $sessionLifetime = $this->sessionService->getSessionLifetime($this->request);
            // dd($sessionLifetime);
            $modulos = $this->reporteLogsService->getModulos();
            // dd($modulos);
            $plazas = $this->reporteLogsService->getPlazas();
            // dd($modulos);
    
            
        } catch (\Throwable $t) {
            $error = [
                'status' => '0',
                'fecha' => date('Y-m-d H:i:s'),
                'descripcion' => 'Error en la función index() del Controlador ReporteLogsController',
                'codigoError' => $t->getCode(),
                'msnError' => $t->getMessage(),
                'linea' => $t->getLine(),
                'archivo' => $t->getFile()
            ];
            Log::error('Error en la función index() del Controlador ReporteLogsController', $error);
            
            $error = 'Ocurrió un error al conectar con la base de datos SQL. Contacte al administrador.';

            return view('home.aplicaciones.reporteLogs.index', compact('error', 'modulos','sessionLifetime','plazas'))->with($data);
        }
        return view('home.aplicaciones.reporteLogs.index', compact('sessionLifetime','modulos','plazas'))->with($data);

    }
    public function apiGetTiendas(Request $request, $codPlaza)
{
    try {
        $tiendas = $this->reporteLogsService->getTienda($codPlaza);
        if(empty($tiendas))
        {
            return response()->json(['error' => 'No se encontraron tiendas para la plaza seleccionada.'], 404);
        }

        return response()->json($tiendas);
    } catch (\Exception $e) {
        return response()->json(['error' => 'No se pudieron recuperar las tiendas.'], 500);
    }
}
public function getLogs(Request $request)
{
    try {
        $logs = $this->reporteLogsService->ConsultarLogs(); 
        // dd($logs);
        return response()->json(['data' => $logs], 200);
    } catch (\Exception $e) {
        return response()->json(['error' => 'Ocurrió un error al conectar con la base de datos SQL. Contacte al administrador.'], 400);
    }
}

public function exportar(Request $request)
{
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $usuario = session('Usuario');

    // Agregar logo
    $drawing = new Drawing();
    $drawing->setName('Logo');
    $drawing->setDescription('Logo');
    $drawing->setPath(public_path('assets/logoCompleto.png')); 
    $drawing->setCoordinates('A1');
    $drawing->setWidth(200); // Ancho en píxeles
    $drawing->setHeight(100); // Alto en píxeles
    $drawing->setWorksheet($sheet);
    // Agregar las cabeceras
    $sheet->setCellValue('A1', 'DP AIO');
    $sheet->mergeCells('A1:O1');
    $sheet->getStyle('A1:O1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('A1:O1')->getFont()->setBold(true)->setSize(16);
    
    $sheet->setCellValue('A2', 'Reporte Logs');
    $sheet->mergeCells('A2:O2');
    $sheet->getStyle('A2:O2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('A2:O2')->getFont()->setBold(true)->setSize(16);

    $sheet->setCellValue('A3', 'Elaborado por: ' . $usuario); // Reemplaza session('Usuario') con la forma correcta de acceder a la sesión
    $sheet->mergeCells('A3:O3');
    $sheet->getStyle('A3:O3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    $sheet->getStyle('A3')->getFont()->setBold(true);

    // Tus cabeceras desde la fila 5
    $sheet->setCellValue('A5', 'ID');
    $sheet->setCellValue('B5', 'IP');
    $sheet->setCellValue('C5', 'Fecha y Hora');
    $sheet->setCellValue('D5', 'CodigoPlaza');
    $sheet->setCellValue('E5', 'Plaza');
    $sheet->setCellValue('F5', 'IDUsuario');
    $sheet->setCellValue('G5', 'Usuario');
    $sheet->setCellValue('H5', 'IDTienda');
    $sheet->setCellValue('I5', 'Tienda');
    $sheet->setCellValue('J5', 'Operacion');
    $sheet->setCellValue('K5', 'DPVale');
    $sheet->setCellValue('L5', 'Modulo');
    $sheet->setCellValue('M5', 'Servicio');
    $sheet->setCellValue('N5', 'Request');
    $sheet->setCellValue('O5', 'Response');


    // Rellenar las filas con datos desde la fila 6
    $datos = $request->input('datos');
    // dd($datos);
    $row = 6;
    foreach ($datos as $dato) {
        $sheet->setCellValue('A' . $row, $dato['ID']);
        $sheet->setCellValue('B' . $row, $dato['IP']);
        $sheet->setCellValue('C' . $row, $dato['FechaHora']);
        $sheet->setCellValue('D' . $row, $dato['CodPlaza']);
        $sheet->setCellValue('E' . $row, $dato['Plaza']);
        $sheet->setCellValue('F' . $row, $dato['UsuarioID']);
        $sheet->setCellValue('G' . $row, $dato['Usuario']);
        $sheet->setCellValue('H' . $row, $dato['TiendaID']);
        $sheet->setCellValue('I' . $row, $dato['Tienda']);
        $sheet->setCellValue('J' . $row, $dato['Operacion']);
        $sheet->setCellValue('K' . $row, $dato['DPVale']);
        $sheet->setCellValue('L' . $row, $dato['Modulo']);
        $sheet->setCellValue('M' . $row, $dato['Servicio']);
        $sheet->setCellValue('N' . $row, $dato['RequestLog']);
        $sheet->setCellValue('O' . $row, $dato['ResponseLog']);
        $row++;
    }
    foreach (range('A', 'O') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }
    $writer = new Xlsx($spreadsheet);
    $temp_file = tempnam(sys_get_temp_dir(), 'reporte_logs_');
    $writer->save($temp_file);

    $content = file_get_contents($temp_file);
    unlink($temp_file);

    return response()->json(['fileContent' => base64_encode($content)]);
}
}
