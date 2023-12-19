<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RoleRuta;
use App\Services\SessionService;

class HomeController extends Controller
{
    protected $sessionService;
    protected $request;

    public function __construct(Request $request, SessionService $sessionService)
    {
        $this->request = $request;
        $this->sessionService = $sessionService;
    }

    public function index()
    {
        $data["sessionLifetime"] = $this->sessionService->getSessionLifetime($this->request);
        $data["title"] = "BIENVENIDO.";
        // dd(session()->all());

	//dd(config('filesystems.disks.s3.region'));
        return view('home')->with($data);
    }

    public function getMenu(Request $request)
    {
        // Obtenemos el ID del rol de la sesión
        $roleId = session('RoleID');

        // Consultamos la base de datos para obtener los menús para este rol
        $menus = RoleRuta::where('roles', $roleId)->get();
        // dd($menus);

        // Pasamos los menús a la vista
        return view('menu', ['menus' => $menus]);
    }
}
