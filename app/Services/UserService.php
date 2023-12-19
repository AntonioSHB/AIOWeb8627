<?php

namespace App\Services;

use App\Models\User;
use App\Models\StoreUser;
use Illuminate\Support\Facades\Hash;
use DB;
use Session;
use Illuminate\Support\Facades\Log;

class UserService
{
    private $userModel;
    private $storeUserModel;

    function __construct()
    {
        $this->userModel = new User();
        $this->storeUserModel = new StoreUser();
    }


    public function getUserBkp($arg)
    {
        // Validamos la existencia de parametros
        if( empty($arg) ) return array();

        $query = $this->userModel->on( session("connection") )->select("*");

        foreach ($arg as $key => $value)
        {
            $value = ($key == "password") ? base64_encode($value) : $value;

            $query->where($key, $value);
        }

        return $query->first();
    }

    public function getUser($username, $password)
    {
        $hashedPassword = hash('sha3-256', $password);
    
        // Primero intenta buscar al usuario en la tabla de Usuarios
        $queryUser = $this->userModel->hydrate
        (DB::select("EXEC ValidarInicio @Sesion = ?, @Usuario = ?, @Clave = ?;", 
        array(1, $username, $hashedPassword)));
    
        // Log::info('User from Usuarios: ' . json_encode($queryUser));
    
        $user = $queryUser->first();
        // dd($user);
        $uTip=$user->TipoUsuario;
        // dd($uTip);
        $uStat=$user->status;
        $msn=$user->msn;
        // Si no se encuentra el usuario en la tabla Usuarios

        if($user->TipoUsuario == '2'){
            $user = $this->storeUserModel->find($user->IDuser);
            // dd($user);

        }else{
            $user = $this->userModel->find($user->IDuser);
            // dd($user);
        }

    // dd($user,$uTip,$uStat,$msn);
        return [
            'user' => $user,
            'type' => $uTip == '2' ? 'store_user' : 'user',
            'status' => $uStat, // agregar esta línea,
            'message' => $msn
        ];
    }
    


    public function setSessionConnection(array $data)
    {
        // dd($data);
        $currentData = session('USERDATA', []);
        $mergedData = array_merge($currentData, $data);
        session(['USERDATA' => $mergedData]);
        // dd($mergedData);
        // Almacenar los datos del usuario en la sesión
        if (isset($data['UsuarioID'])) {
            session(['UsuarioID' => $data['UsuarioID']]);
        }
        if (isset($data['UsuarioTiendaID'])) {
            session(['UsuarioTiendaID' => $data['UsuarioTiendaID']]);
        }
        if(isset($data['Fecha'])) {
            session(['Fecha' => $data['Fecha']]);
        }
        if(isset($data['Usuario'])) {
            session(['Usuario' => $data['Usuario']]);
        }
        if(isset($data['Nombre'])) {
            session(['Nombre' => $data['Nombre']]);
        }
        if(isset($data['NoColaborador'])) {
            session(['NoColaborador' => $data['NoColaborador']]);
        }
        if(isset($data['RoleID'])) {
            session(['RoleID' => $data['RoleID']]);
        }
        if(isset($data['Plazas'])) {
            session(['Plazas' => $data['Plazas']]);
        }
        if(isset($data['CodPlaza'])) {
            session(['CodPlaza' => $data['CodPlaza']]);
        }
        if(isset($data['TiendaID'])) {
            session(['TiendaID' => $data['TiendaID']]);
        }
        if(isset($data['Oficina'])) {
            session(['Oficina' => $data['Oficina']]);
        }
        if(isset($data['TiendaOVTA'])) {
            session(['TiendaOVTA' => $data['TiendaOVTA']]);
        }
        if(isset($data['Clave'])) {
            session(['Clave' => $data['Clave']]);
        }
        if(isset($data['Estatus'])) {
            session(['Estatus' => $data['Estatus']]);
        }
        if(isset($data['UltimaClave'])) {
            session(['UltimaClave' => $data['UltimaClave']]);
        }
        // session(['usuarioLogin' => $data]);

        foreach ($mergedData as $key => $value) {
            session([$key => $value]);
        }
        Session::save();

        if (session("USERDATA") == $mergedData) {
            return true;
        }
        
        return false;
    }
    
    public function authenticateUser($username, $password, $route)
    {
        // Log::info('AuthenticateUser inputs:', ['username' => $username, 'password' => $password, 'route' => $route]); // Agregando este log para ver los inputs

        $hashedPassword = hash('sha3-256', $password);
        $queryUser = $this->userModel->hydrate
        (DB::select("EXEC ValidarInicio @Sesion = ?, @Usuario = ?, @Clave = ?;", 
        array(2, $username, $hashedPassword)));
    
        $user = $queryUser->first();
        // Log::info('Query user result:', $user); // Agregando este log para ver el resultado del usuario de la consulta

        if ($user->status == 0) {
            return [
                'authenticated' => false,
                'msn' => $user->msn,
                'user' => null,
                'route' => null
            ];
        } else if ($user->status == 2) {
            return [
                'authenticated' => true,
                'msn' => "Necesita cambiar la contraseña",
                'user' => $user,
                'route' => 'passwordUpdate'
            ];
        }
    
        $status = $user != null;
    
        if($status) {
            $this->setSessionConnection(['route' => $route]);
        }
    
        return [
            'authenticated' => $status, 
            'user' => $user,
            'route' => $route,
        ];
    }
    
    
    
}

?>