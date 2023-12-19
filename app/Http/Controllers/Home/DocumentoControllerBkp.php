<?php

namespace App\Http\Controllers\Home;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use ZipArchive;
use Illuminate\Support\Facades\Log;


class DocumentoController extends Controller {
        
    public $request; // se agrega esta linea porque el constructor estaba marcando un error linea 17

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function imprimirDocumentos($template, $arg){
        $response = array();
    
        $zip = new ZipArchive();
    
        try{
            $filename = public_path("assets/Templates/" . $template . "_TEMPLATE.docx");
            $newfilename = public_path("assets/Templates/" . $template . "_TEMPLATE_" . $arg["SURTASE"] . ".docx");
        
            if (!copy($filename, $newfilename)) {
                $response['success'] = false;
                $response['message'] = 'Failed to copy';
                return response()->json($response);
            }
        
            if ($zip->open($newfilename, ZipArchive::CREATE)!==TRUE) {
                $response['success'] = false;
                $response['message'] = "Cannot open $newfilename :(";
                return response()->json($response);
            }
        
            $xml = $zip->getFromName('word/document.xml');
        
            foreach ($arg as $key => $value) {
                $xml = str_replace("$\{".$key."\}", (empty($arg[$key]) ? "" : $arg[$key]), $xml);
            }
        
            if ($zip->addFromString('word/document.xml', $xml)) {
                $response['success'] = true;
                $response['message'] = 'File written!';
            } else {
                $response['success'] = false;
                $response['message'] = 'File not written. Go back and add write permissions to this folder!l';
            }
        
            $zip->close();
            $response['newfilename'] = $newfilename;

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
        // error_log(print_r($documentos, true)); // Utilizando error_log()
        // Log::info($documentos); // Utilizando Laravel Log
        $base64Files = [];

        foreach ($documentos as $documento) {
            $template = $documento['template'];
            $arg = $documento['arg'];
    
            $newfilename = $this->imprimirDocumentos($template, $arg);
            $fileUrl = str_replace(public_path(), '', $newfilename);
            $fileUrls[] = $fileUrl;
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
    
    
}
?>
