<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EncryptService 
{
    private $configService;

    public function __construct(ConfigService $configService)
    {
        $this->configService = $configService;
    }
    public function getEncrypt() {
        try {
            DB::enableQueryLog();
            $currentDateFromDB = DB::select('SELECT GETDATE() as currentDate')[0]->currentDate;
            $dateString = (new \DateTime($currentDateFromDB))->format('Y-m-d H:i:s');
            
            $results = DB::select('EXEC dbo.getEncrypt @Fecha=:dateParam', ['dateParam' => $dateString]);
            return $results;
        } catch (\Exception $e) {
            $queries = DB::getQueryLog();
            $lastQuery = end($queries); // Obtener la última consulta ejecutada
            $bindings = $lastQuery['bindings']; // Parámetros de la consulta
            $requestLog = $lastQuery['query']; // SQL de la consulta
            $error = [
                'status' => '0',
                'fecha' => date('Y-m-d H:i:s'),
                'descripcion' => 'Error en la funcion getEncrypt().',
                'codigoError' => $e->getCode(),
                'msnError' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'requestLog' => $requestLog, // Agregar el SQL al log
                'responseLog' => $bindings, // Agregar los parámetros al log
            ];
            Log::error('Error en la funcion getEncrypt()', $error);
            return null;
        }
    }

    public function Encrypt($encriptar=0,$nombre, $tarjetaClabe) {
        $url = $this->configService->obtenerUrlEncrypt();
        // dd($nombre, $tarjetaClabe, $url);
        // $requestData = [
        //     "encriptar" => $encriptar, 
        //     "nombre" => $nombre,
        //     "datos" => [
        //         ["id" => "1", "data" => $tarjetaClabe]
        //     ]];
        $requestData = [
            "Encriptacion" => [
                [
                    "encriptar" => $encriptar, 
                    "nombre" => $nombre,
                    "datos" => [
                        ["id" => "1", "data" => $tarjetaClabe]
                    ]
                ],
                // Si hay más "Encriptacion" puedes agregarlos aquí
            ]
        ];
            $response = null;
        try {
        //     $response = Http::withoutVerifying()->post($url, [
        //         "encriptar" => $encriptar,
        //         "nombre" => $nombre,
        //         "datos" => [
        //             ["id" => "1", "data" => $tarjetaClabe]
        //         ]
        //    ]);
        // $response = Http::withoutVerifying()->post($url, $requestData);


        $response = Http::withoutVerifying()->post($url, $requestData);
            return $response->json();
        } catch (\Throwable $t) {
            $error = [
                'status' => '0',
                'fecha' => date('Y-m-d H:i:s'),
                'descripcion' => 'Error en la funcion Encrypt() al consumir el servicio.',
                'codigoError' => $t->getCode(),
                'msnError' => $t->getMessage(),
                'linea' => $t->getLine(),
                'archivo' => $t->getFile(),
                'requestLog' => $requestData,
                'responseLog' => $response
            ];    
            
            Log::error('Error en la funcion Encrypt() al consumir el servicio Encrypt', $error);
            
            return null;
        }
    }
}
