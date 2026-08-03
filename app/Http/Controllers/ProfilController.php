<?php

namespace App\Http\Controllers;

class ProfilController extends Controller
{
    public function index()
    {
        // $profil & $settings diisi LayoutComposer
        return view('pages.profil');
    }
}
