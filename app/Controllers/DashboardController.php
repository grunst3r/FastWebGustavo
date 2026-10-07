<?php

namespace App\Controllers;

class DashboardController
{
    public function welcome()
    {
        return view('welcome');
    }

    public function index()
    {
        return view('dashboard');
    }
}
