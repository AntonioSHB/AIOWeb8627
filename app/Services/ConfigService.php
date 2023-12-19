<?php

namespace App\Services;

use App\Models\Configuracion;
use Illuminate\Support\Facades\Log;


class ConfigService
{
    public static function obtenerValorPorParametro($parametro)
    {
        try {

        return Configuracion::where('Parametro', $parametro)->first()->Valor;
    } catch (\Illuminate\Http\Client\ConnectionException $e) {
        $error = [
            'status'=> '0',
            'fecha'=> date('Y-m-d H:i:s'),
            'descripcion' => 'Error de timeout en la función getStores() al consumir el servicio s2credit',
            'codigoError'=> $e->getCode(),
            'msnError' => $e->getMessage(),
            'linea' => $e->getLine(),
            'archivo' => $e->getFile(),
            'requestLog' => $requestData,
            'responseLog' => $response,
        ];    
        Log::error('Timeout en la función getStores() al consumir el servicio s2credit', $error);
        return ['error' => 'Ocurrió un error al obtener catalogo de tiendas, favor de volver a intentar.'];
    } catch (\Throwable $t) {
        $error = [
            'status' => '0',
            'fecha' => date('Y-m-d H:i:s'),
            'descripcion' => "Error al obtener valor por parámetro: $parametro",
            'codigoError' => $t->getCode(),
            'msnError' => $t->getMessage(),
            'linea' => $t->getLine(),
            'archivo' => $t->getFile()
        ];
        Log::error('Error al obtener valor por parámetro', $error);

        // Puedes decidir si retornar null, false, o lanzar la excepción de nuevo, dependiendo de tu lógica de negocio.
        return null;
    }
    }
    
    public static function obtenerUrlS2Credit()
    {
        try
        {

        $urlBroker = self::obtenerValorPorParametro('url_broker');
        $pathPosS2Credit = self::obtenerValorPorParametro('path_pos_s2credit');
        
        return $urlBroker . $pathPosS2Credit;
    } catch (\Illuminate\Http\Client\ConnectionException $e) {
        $error = [
            'status'=> '0',
            'fecha'=> date('Y-m-d H:i:s'),
            'descripcion' => 'Error de timeout en la función obtenerUrlS2Credit() al consumir el servicio s2credit',
            'codigoError'=> $e->getCode(),
            'msnError' => $e->getMessage(),
            'linea' => $e->getLine(),
            'archivo' => $e->getFile(),
            'requestLog' => $requestData,
            'responseLog' => $response,
        ];    
        Log::error('Timeout en la función obtenerUrlS2Credit() al consumir el servicio s2credit', $error);
        return ['error' => 'Ocurrió un error al obtener catalogo de tiendas, favor de volver a intentar.'];
    } catch (\Throwable $t) {
        $error = [
            'status' => '0',
            'fecha' => date('Y-m-d H:i:s'),
            'descripcion' => 'Error al obtener url s2credit',
            'codigoError' => $t->getCode(),
            'msnError' => $t->getMessage(),
            'linea' => $t->getLine(),
            'archivo' => $t->getFile()
        ];
        Log::error('Error al obtener url s2credit', $error);

        return null;
    }
    }
    public static function obtenerUrlEncrypt()
    {
        try
        {
        $urlBroker = self::obtenerValorPorParametro('url_encrypt');
        
        return $urlBroker;
    } catch (\Illuminate\Http\Client\ConnectionException $e) {
        $error = [
            'status'=> '0',
            'fecha'=> date('Y-m-d H:i:s'),
            'descripcion' => 'Error de timeout en la función obtenerUrlEncrypt() al consumir el servicio s2credit',
            'codigoError'=> $e->getCode(),
            'msnError' => $e->getMessage(),
            'linea' => $e->getLine(),
            'archivo' => $e->getFile(),
            'requestLog' => $requestData,
            'responseLog' => $response,
        ];    
        Log::error('Timeout en la función obtenerUrlEncrypt() al consumir el servicio s2credit', $error);
        return ['error' => 'Ocurrió un error al obtener catalogo de tiendas, favor de volver a intentar.'];
    } catch (\Throwable $t) {
        $error = [
            'status' => '0',
            'fecha' => date('Y-m-d H:i:s'),
            'descripcion' => 'Error al obtener url encrypt',
            'codigoError' => $t->getCode(),
            'msnError' => $t->getMessage(),
            'linea' => $t->getLine(),
            'archivo' => $t->getFile()
        ];
        Log::error('Error al obtener url encrypt', $error);

        return null;
    }
    }
    public static function obtenerUrlBeneficiario()
    {
        try
        {
        $urlBeneficiario = self::obtenerValorPorParametro('url_beneficiario');

        return $urlBeneficiario;
    } catch (\Illuminate\Http\Client\ConnectionException $e) {
        $error = [
            'status'=> '0',
            'fecha'=> date('Y-m-d H:i:s'),
            'descripcion' => 'Error de timeout en la función obtenerUrlBeneficiario() al consumir el servicio s2credit',
            'codigoError'=> $e->getCode(),
            'msnError' => $e->getMessage(),
            'linea' => $e->getLine(),
            'archivo' => $e->getFile(),
            'requestLog' => $requestData,
            'responseLog' => $response,
        ];    
        Log::error('Timeout en la función obtenerUrlBeneficiario() al consumir el servicio s2credit', $error);
        return ['error' => 'Ocurrió un error al obtener catalogo de tiendas, favor de volver a intentar.'];
    } catch (\Throwable $t) {
        $error = [
            'status' => '0',
            'fecha' => date('Y-m-d H:i:s'),
            'descripcion' => 'Error al obtener url beneficiario',
            'codigoError' => $t->getCode(),
            'msnError' => $t->getMessage(),
            'linea' => $t->getLine(),
            'archivo' => $t->getFile()
        ];
        Log::error('Error al obtener url beneficiario', $error);

        return null;
    }
    }
}
