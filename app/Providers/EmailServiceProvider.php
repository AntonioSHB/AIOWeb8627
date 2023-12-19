<?php

namespace App\Providers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Log;

class EmailServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind('mailer', function ($app) {
            
        });

        try{
            DB::enableQueryLog();
            
            $configuraciones = DB::table('dbo.Configuracion')
                            ->where('Parametro', 'like', '%mail_%')
                            ->get();
            
            $mailerConfig = [];

            if ($configuraciones) {
                foreach ($configuraciones as $configuracion) {
                    if ($configuracion->Parametro === 'mail_mailer') {
                        $mailerConfig['mail_mailer'] = ($configuracion->Valor == '') ? 'null' : $configuracion->Valor;
                    } elseif ($configuracion->Parametro === 'mail_host') {
                        $mailerConfig['mail_host'] = ($configuracion->Valor == '') ? 'null' : $configuracion->Valor;
                    } elseif ($configuracion->Parametro === 'mail_port') {
                        $mailerConfig['mail_port'] = ($configuracion->Valor == '') ? 'null' : $configuracion->Valor;
                    } elseif ($configuracion->Parametro === 'mail_encryption') {
                        $mailerConfig['mail_encryption'] = ($configuracion->Valor == '') ? 'null' : $configuracion->Valor;
                    } elseif ($configuracion->Parametro === 'mail_username') {
                        $mailerConfig['mail_username'] = ($configuracion->Valor == '') ? 'null' : $configuracion->Valor;
                    } elseif ($configuracion->Parametro === 'mail_password') {
                        $mailerConfig['mail_password'] = ($configuracion->Valor == '') ? 'null' : $configuracion->Valor;
                    } elseif ($configuracion->Parametro === 'mail_from_address') {
                        $mailerConfig['mail_from_address'] = ($configuracion->Valor == '') ? 'null' : $configuracion->Valor;
                    } elseif ($configuracion->Parametro === 'mail_from_name') {
                        $mailerConfig['mail_from_name'] = ($configuracion->Valor == '') ? 'null' : $configuracion->Valor;
                    }
                }

                config([
                    'mail.default' => $mailerConfig['mail_mailer'],
                    'mail.mailers.smtp.host' => $mailerConfig['mail_host'],
                    'mail.mailers.smtp.port' => $mailerConfig['mail_port'],
                    'mail.mailers.smtp.username' => $mailerConfig['mail_username'],
                    'mail.mailers.smtp.password' => $mailerConfig['mail_password'],
                    'mail.mailers.smtp.encryption' => $mailerConfig['mail_encryption'],
                    'mail.from.address' => $mailerConfig['mail_from_address'],
                    'mail.from.name' => $mailerConfig['mail_from_name'],
                ]);

            } 

        }catch(\Throwable $t){
            
            $queries = DB::getQueryLog();

            if (!empty($queries)) {
                $lastQuery = end($queries); // Obtener la última consulta ejecutada
                $bindings = $lastQuery['bindings']; // Parámetros de la consulta
                $requestLog = $lastQuery['query']; // SQL de la consulta
            } else {
                $lastQuery = null;
                $bindings = null;
                $requestLog = null;
            }
            

    
            
            $error = [
                'status' => '0',
                'fecha' => date('Y-m-d H:i:s'),
                'descripcion' => 'Error en la funcion register().',
                'codigoError' => $t->getCode(),
                'msnError' => $t->getMessage(),
                'linea' => $t->getLine(),
                'archivo' => $t->getFile(),
                'requestLog' => $requestLog, // Agregar el SQL al log
                'responseLog' => $bindings, // Agregar los parámetros al log
            ];
            Log::error('Error en la funcion register()', $error);      
            throw new \Exception("DB Connection Error");
                  
        }
       
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
