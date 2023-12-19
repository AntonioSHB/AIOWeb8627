<?php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;

class EnvConfigServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $configs = DB::table('Configuracion')->get();

        foreach ($configs as $config) {
            $key = strtoupper($config->Parametro);
            $value = $config->Valor;

            $this->setEnvironmentValue($key, $value);
        }
    }

    /**
     * Set the environment value.
     *
     * @param $key
     * @param $value
     * @return void
     */
    private function setEnvironmentValue($key, $value)
    {
        $path = base_path('.env');

        if (file_exists($path)) {
            file_put_contents($path, str_replace(
                "$key=" . env($key),
                "$key=" . $value,
                file_get_contents($path)
            ));
        }
    }
}
