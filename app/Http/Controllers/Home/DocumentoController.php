<?php

namespace App\Http\Controllers\Home;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use ZipArchive;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Carbon;
use Aws\S3\S3Client;
use PhpOffice\PhpWord\TemplateProcessor; 
use App\Models\Configuracion;


class DocumentoController extends Controller {
    
    public $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function imprimirDocumentos($template, $arg){
        $response = array();
        $enviarBucket = false;
        $nameSuffix = "";

        if ($template === "I1") {
            $nameSuffix = "-1";
        } else if ($template === "I2") {
            $nameSuffix = "-2";
        }
        if ($template === "I1" || $template === "I2") {
            $template = "I";
        }
        $response = null;
        try{
            $folderName = $this->generateFolderName($template,$arg);
    
            $filename = public_path("assets/Templates/" . $template . ".docx");
            $newfilename = $folderName . $template . $arg["DPVale"] . $nameSuffix . ".docx";
            $copyfilename = public_path("assets/Templates/" . $template . $arg["DPVale"] . $nameSuffix . ".docx");
            $pdfFilename = $folderName . $template . $arg["DPVale"] . $nameSuffix . ".pdf";
            $pdfCopyFilename = public_path("assets/Templates/" . $template . $arg["DPVale"] . $nameSuffix . ".pdf");
            

            // Crea una nueva instancia de TemplateProcessor
            $templateProcessor = new TemplateProcessor($filename);

            // Reemplaza las variables en el documento
            foreach ($arg as $key => $value) {
                
                // Comprueba si el valor es una imagen en base64
                if (substr($value, 0, 11) == 'data:image/') {
                    // Extrae la parte de base64 de la cadena
                    $base64Image = substr($value, strpos($value, ',') + 1);
                    // Decodifica la imagen en base64
                    $image = base64_decode($base64Image);
            
                    // Crea un archivo temporal y guarda la imagen en él
                    $tempImageFilename = tempnam(sys_get_temp_dir(), 'phpword');

                    file_put_contents($tempImageFilename, $image);
            
                    // Establece la imagen en el documento
                    if ($key == 'Firma' || $key == 'FIRMA') {
                        $templateProcessor->setImageValue($key, array('path' => $tempImageFilename)); // no especificamos dimensiones para mantener el tamaño original
                    } else {
                        $templateProcessor->setImageValue($key, array('path' => $tempImageFilename, 'width' => 300, 'height' => 300, 'ratio' => false));
                    }
                    unlink($tempImageFilename);

                } else {
                    $templateProcessor->setValue($key, (empty($arg[$key]) ? "" : $arg[$key]));
                }
            }
            
            // Guarda el nuevo documento
            $templateProcessor->saveAs($copyfilename);

            $os = strtoupper(substr(PHP_OS, 0, 3));

            if ($os === 'WIN') {
                // Comando para Windows
                $output = shell_exec('"C:\Program Files\LibreOffice\program\soffice.exe" --headless --convert-to pdf ' . 
                    escapeshellarg($copyfilename) . ' --outdir ' . escapeshellarg(dirname($pdfFilename)));
            } else {
               
                $output = shell_exec('env HOME=/tmp /opt/libreoffice7.6/program/soffice --headless --convert-to pdf ' . 
                escapeshellarg($copyfilename) . ' --outdir ' . escapeshellarg(dirname($pdfFilename)));

                    
            }
            copy($pdfFilename, $pdfCopyFilename);

            try {
                Storage::disk('s3')->put($pdfFilename, file_get_contents($pdfFilename));
                // Log::info("Archivo subido a S3 correctamente");
                
                $enviarBucket = true; // Establece EnviarBucket a 1 si la subida a S3 tiene éxito
                session(['enviarBucket' => $enviarBucket]);

                // Generar una URL prefirmada para el archivo
                $client = new S3Client([
                    'version' => 'latest',
                    'region'  => 'us-west-2',
                    'credentials' => [
                        'key'    => env('AWS_ACCESS_KEY_ID'),
                        'secret' => env('AWS_SECRET_ACCESS_KEY'),
                    ],
                ]);
    
                $expiry = "+10 minutes";
    
                $command = $client->getCommand('GetObject', [
                    'Bucket' => env('AWS_BUCKET'),
                    'Key'    => $pdfFilename
                ]);
    
                $request = $client->createPresignedRequest($command, $expiry);
    
                // Obtén la URL prefirmada
                $presignedUrl = (string) $request->getUri();
                // Elimina el archivo PDF local una vez que se haya subido a S3 con éxito
                if (file_exists($pdfFilename)) {
                    unlink($pdfFilename);
                }
                
                // Elimina el archivo Word local una vez que se haya subido a S3 con éxito
                if (file_exists($copyfilename)) {
                    unlink($copyfilename);
                }
    
            } catch (\Exception $e) {
                $enviarBucket = false; // Establece EnviarBucket a 0 si la subida a S3 falla
                session(['enviarBucket' => $enviarBucket]);

                Log::error("Error al subir archivo a S3: " . $e->getMessage());
                $response['success'] = false;
                $response['message'] = "Error al subir archivo a S3: " . $e->getMessage();
                return response()->json($response);
            }           
    
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
                'archivo' => $t->getFile(),
                'requestLog' => $this->request->all(),
                'responseLog' => $response,
            ];    
            Log::error('Error en la funcion imprimirDocumentos()', $error);
        }
    
        return response()->json($response);        
    }
    
    
    public function handleRequest(Request $request) {
        
        $documentos = $request->input('documentos');
        // log::info("Documentos: " . json_encode($documentos));
        $fileUrls = [];

        foreach ($documentos as $documento) {
            if (isset($documento['template'])) {
                $template = $documento['template'];
                $arg = $documento['arg'];
        
                $response = $this->imprimirDocumentos($template, $arg);
                // log::info("Response: " . json_encode($response));
                $responseData = $response->getData();
                // log::info("ResponseData: " . json_encode($responseData));
                // if (isset($responseData->success) && $responseData->success) {
                //     $fileUrls[] = $responseData->newfilename;
                // }
                if (isset($responseData->newfilename)) {
                    $fileUrls[] = $responseData->newfilename;
                }
                
            } else {
                // Aquí puedes manejar o registrar el error, si es necesario.
                // Por ejemplo, puedes agregar un log para rastrear la entrada problemática:
                Log::error("Documento sin 'template'", ["documento" => $documento]);
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


    public function generateFolderName($template, $arg) {
        $ruta = $arg['TRAMITE'];
        $folder = Configuracion::rutaS3($ruta);
        $date = Carbon::now();
        $date->setLocale('es');
    
        $documentosMap = Configuracion::getDocumentosMap();
    
        $codigo = substr($template, 0, 1); // Obtiene la primera letra de $template (por ejemplo, "C" de "C1")
    
        // Si el template es "I1" o "I2", asigna directamente 'IDENTIFICACION'
        if (in_array($template, ['I', 'I'])) {
            $folderName = 'IDENTIFICACION/';
        } else {
            $folderName = $documentosMap[$codigo] ?? ''; 
            $folderName .= '/'; // Solo añade "/"
        }
    
        $folder .= '/' . $date->year . '/' . ucfirst($date->isoFormat('MMMM')) . '/' . $date->format('Ymd') . '/' . $folderName;
        return $folder;
    }
    
}
?>
