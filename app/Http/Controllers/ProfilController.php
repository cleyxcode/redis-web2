<?php

namespace App\Http\Controllers;

use App\Models\TonggakSejarah;

class ProfilController extends Controller
{
    public function index()
    {
        $tonggakSejarah = TonggakSejarah::query()->aktif()->ordered()->get();

        return view('pages.profil', compact('tonggakSejarah'));
    }
}
