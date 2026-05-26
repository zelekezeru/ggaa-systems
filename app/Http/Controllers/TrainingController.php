<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;

class TrainingController extends Controller
{
    /**
     * Display the training landing index hub.
     */
    public function index()
    {
        return Inertia::render('Training/Index');
    }

    /**
     * Display the Employee training guide module.
     */
    public function employees()
    {
        return Inertia::render('Training/Employees');
    }

    /**
     * Display the Admin & Manager training guide module.
     */
    public function admins()
    {
        return Inertia::render('Training/Admins');
    }

    /**
     * Display the Finance & Bookkeeper training guide module.
     */
    public function finances()
    {
        return Inertia::render('Training/Finances');
    }
}
