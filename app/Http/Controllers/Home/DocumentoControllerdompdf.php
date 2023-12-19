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
use PhpOffice\PhpWord\IOFactory;
use Dompdf\Dompdf;


class DocumentoController extends Controller {
    
    public $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function imprimirDocumentos($template, $arg){
        $response = array();
        $enviarBucket = false;

        try{
            $folderName = $this->generateFolderName();
    
            $filename = public_path("assets/Templates/" . $template . ".docx");
            $newfilename = $folderName . $template . $arg["DPVale"] . ".docx";
            $copyfilename = public_path("assets/Templates/" . $template . $arg["DPVale"] . ".docx");
            $pdfFilename = $folderName . $template . $arg["DPVale"] . ".pdf";

            // Crea una nueva instancia de TemplateProcessor
            $templateProcessor = new TemplateProcessor($filename);

            // Reemplaza las variables en el documento
            foreach ($arg as $key => $value) {
                Log::info("Replacing '{$key}' with '{$value}'");

                $templateProcessor->setValue($key, (empty($arg[$key]) ? "" : $arg[$key]));
            }
            
            // Guarda el nuevo documento
            $templateProcessor->saveAs($copyfilename);
            Log::info("Archivo generado correctamente");

            // shell_exec('"C:\Program Files\LibreOffice\program\soffice.exe" --headless --convert-to pdf ' . 
            // escapeshellarg($copyfilename) . ' --outdir ' . escapeshellarg(dirname($pdfFilename)));
                        // Convierte el documento a HTML
                        $tempHtmlFilePath = tempnam(sys_get_temp_dir(), 'html'); // Crear un archivo temporal
                        $phpWord = IOFactory::load($copyfilename);
                        $xmlWriter = IOFactory::createWriter($phpWord, 'HTML');
                        $xmlWriter->save($tempHtmlFilePath); // Guardar la salida HTML en el archivo temporal
                        
                        $htmlOutput = file_get_contents($tempHtmlFilePath); // Leer el contenido del archivo temporal
                        
                        $dompdf = new Dompdf();
                        $dompdf->loadHtml($htmlOutput);
                        $dompdf->render();
                        $pdfOutput = $dompdf->output();
                        
                        file_put_contents($pdfFilename, $pdfOutput);
                        
                        unlink($tempHtmlFilePath); // Eliminar el archivo temporal

            try {
                Storage::disk('s3')->put($pdfFilename, file_get_contents($pdfFilename));
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
                    'Key'    => $pdfFilename
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
            if (isset($responseData->success) && $responseData->success) {
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
