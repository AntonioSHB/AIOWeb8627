<?php

namespace App\Http\Controllers\Home;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use ZipArchive;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Carbon;
use Aws\S3\S3Client;

class DocumentoController extends Controller {
    
    public $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function imprimirDocumentos($template, $arg){
        $response = array();
        $enviarBucket = false;

        $zip = new ZipArchive();
    
        try{
            $folderName = $this->generateFolderName();
    
            $filename = public_path("assets/Templates/" . $template . ".docx");
            $newfilename = $folderName . $template . "_TEMPLATE_" . $arg["SURTASE"] . ".docx";
            $copyfilename = public_path("assets/Templates/" . $template . "_TEMPLATE_" . $arg["SURTASE"] . ".docx");
    
            $tempFile = tempnam(sys_get_temp_dir(), 'doc');
    
            copy($filename, $tempFile);
    
            if ($zip->open($tempFile, ZipArchive::CREATE)!==TRUE) {
                $response['success'] = false;
                $response['message'] = "Cannot open $tempFile :(";
                return response()->json($response);
            }
            
            $xml = $zip->getFromName('word/document.xml');
             
            foreach ($arg as $key => $value) {
                Log::info("Replacing '{$key}' with '{$value}'");

                $xml = str_replace('{' . $key . '}', (empty($arg[$key]) ? "" : $arg[$key]), $xml);
            }
            
            if ($zip->addFromString('word/document.xml', $xml)) {
                $response['success'] = true;
                $response['message'] = 'File written!';
            } else {
                $response['success'] = false;
                $response['message'] = 'File not written. Go back and add write permissions to this folder!';
            }
            
            $zip->close();
    
            try {
                Storage::disk('s3')->put($newfilename, file_get_contents($tempFile));
                copy($tempFile, $copyfilename);
    
                Log::info("Archivo subido a S3 correctamente");
                
                $enviarBucket = true; // Establece EnviarBucket a 1 si la subida a S3 tiene éxito
                session(['enviarBucket' => $enviarBucket]);

                // Generar una URL prefirmada para el archivo
                $client = new S3Client([
                    'version' => 'latest',
                    'region'  => env('AWS_DEFAULT_REGION'),
                    'credentials' => [
                        'key'    => env('AWS_ACCESS_KEY_ID'),
                        'secret' => env('AWS_SECRET_ACCESS_KEY'),
                    ],
                ]);
    
                $expiry = "+10 minutes";
    
                $command = $client->getCommand('GetObject', [
                    'Bucket' => env('AWS_BUCKET'),
                    'Key'    => $newfilename
                ]);
    
                $request = $client->createPresignedRequest($command, $expiry);
    
                // Obtén la URL prefirmada
                $presignedUrl = (string) $request->getUri();
    
            } catch (\Exception $e) {
                $enviarBucket = false; // Establece EnviarBucket a 0 si la subida a S3 falla
                session(['enviarBucket' => $enviarBucket]);

                Log::error("Error al subir archivo a S3: " . $e->getMessage());
                $response['success'] = false;
                $response['message'] = "Error al subir archivo a S3: " . $e->getMessage();
                return response()->json($response);
            }           
    
            unlink($tempFile);
    
            // Agrega URL de Amazon S3
            $response['newfilename'] = $presignedUrl;
    
        }catch(\Throwable $t){
            $error = [
                'status'=> '0',
                'fecha'=> date('Y-m-d H:i:s'),
                'descripcion' => 'Error en la funcion imprimirDocumentos()',                
                'codigoError'=> $t->getCode(),
                'msnError' => $t->getMessage(),
                'linea' => $t->getLine(),
                'archivo' => $t->getFile()
            ];    
            Log::error('Error en la funcion imprimirDocumentos()', $error);
        }
    
        return response()->json($response);        
    }
    
    
    public function handleRequest(Request $request) {
        $documentos = $request->input('documentos');
        $fileUrls = [];

        foreach ($documentos as $documento) {
            $template = $documento['template'];
            $arg = $documento['arg'];
    
            $response = $this->imprimirDocumentos($template, $arg);
            $responseData = $response->getData();
            if ($responseData->success) {
                $fileUrls[] = $responseData->newfilename;
            }
        }
        return response()->json(['success' => true, 'fileUrls' => $fileUrls]);
    }

    public function convertFileToBase64($filepath) {
        try{
            $fileData = file_get_contents($filepath);
            $base64File = base64_encode($fileData);
        }catch(\Throwable $t){
            $error = [
                'status'=> '0',
                'fecha'=> date('Y-m-d H:i:s'),
                'descripcion' => 'Error en la funcion convertFileToBase64()',                
                'codigoError'=> $t->getCode(),
                'msnError' => $t->getMessage(),
                'linea' => $t->getLine(),
                'archivo' => $t->getFile()
            ];    
            Log::error('Error en la funcion convertFileToBase64()', $error);
        }        
        return $base64File;
    }

    public function generateFolderName() {
        $date = Carbon::now();
        $date->setLocale('es');
        $folderName = 'aio_qas/' . $date->year . '/' . ucfirst($date->isoFormat('MMMM')) . '/' . $date->format('Ymd') . '/';
        return $folderName;
    }
}
?>
