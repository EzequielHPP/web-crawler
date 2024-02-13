<?php

namespace App\Http\Controllers;

use App\Models\Crawls;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomepageController extends Controller
{
    /**
     * Show the application homepage.
     *
     * @return View
     */
    final public function index(): View
    {
        return view('pages.home');
    }

    /**
     * Redirect all other routes to the homepage
     *
     * @return RedirectResponse
     */
    final public function redirect(): RedirectResponse
    {
        return redirect()->route('home');
    }
}
