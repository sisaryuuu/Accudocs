<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller; 
use App\Models\FocalPerson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FocalPersonController extends Controller
{
    public function index()
    {
        $user = Auth::guard('focal')->user();
        return view('FocalPerson.dvrequest.index', compact('user'));
    }
}
