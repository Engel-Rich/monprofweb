<?php

namespace App\Http\Controllers\web;

use App\Http\Controllers\Controller;
use App\Models\Codes;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class CodesController extends Controller
{
    //** */

    function index($status): View
    {

        $codes =  Codes::with('eleve')->orderBy('id', 'desc')->paginate(20);
        $codesactif =  Codes::with('eleve')->where('actif',1)->orderBy('id', 'desc')->paginate(20);
        $codesinactif =  Codes::with('eleve')->where('actif',0)->orderBy('id', 'desc')->paginate(20);
        // dd($codes);
        return $status === 'all' ? view("screen.codes.index_codes", ['codes' => $codes]) :
           ( $status === "actif" ? view("screen.codes.index_codes", ['codes' => $codesactif]) :
            view("screen.codes.index_codes", ['codes' => $codesinactif]));
    }

    public function valideCode(Request $request)
    {
    }
    /**
     * Activations du codes;
     */
}
