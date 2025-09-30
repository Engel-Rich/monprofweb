<?php

namespace App\Http\Controllers;

use App\Models\PayementServices;
use Illuminate\Http\Request;

class PayementServicesController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $result =  PayementServices::where("sens", "IN");
        return json_encode($result, 200);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(PayementServices $payementServices)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(PayementServices $payementServices)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, PayementServices $payementServices)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PayementServices $payementServices)
    {
        //
    }
}
