<?php

namespace App\Http\Controllers;

use App\Models\Program;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    public function index()
    {
        $programs = Program::withCount('requests')->get();

        // return Admin dashboard view with data
        return view('Admin.dashboard', compact('programs'));
    }
}
