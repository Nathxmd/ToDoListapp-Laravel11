<?php

namespace App\Http\Controllers\About;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AboutController extends Controller
{
    public function index() {
        $nama = 'Nathan Mahesa';
        $data = ['nama' => $nama];
        return view('about', $data);
    }
}
