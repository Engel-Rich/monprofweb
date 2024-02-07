<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Matieres;
use App\Models\Professeur;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ProfesseursController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index():View
    {
        $profs = Professeur::with('user', 'matiere')->paginate(20);
        // dd($profs);
        return view('screen.profs.index_prof', ['profs' =>$profs ],);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $matieres = Matieres::all();
        return view('screen.profs.create', ['matieres'=>$matieres]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        try {
            $request->validate([
                "matieres_id" => 'integer|exists:matieres,id',
                'rule_id' => 'integer|nullable',
                'name' => 'required|max:50',
                'last_name' => 'nullable|max:30',
                'phone' => 'required|max:14|unique:users,phone',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|min:4',
            ]);
            $userData = [
                'rule_id' => 3,
                'name' => $request->name,
                'last_name' => $request->last_name,
                'phone' => $request->phone,
                'email' => $request->email,
                'password' =>  Hash::make($request->password),
            ];
            $profs_data = [
                "matieres_id" => $request['matieres_id']
            ];
            $unique_token = (string) Str::uuid();
                while (true) {
                    $user = User::where('unique_token', '=', $unique_token)->exists();
                    if ($user) {
                        $unique_token = (string) Str::uuid();
                    } else {
                        $userData['unique_token'] = $unique_token;
                        break;
                    }
                }
                $user = User::create($userData);   
                $profs_data['user_id']= $user->id;
                Professeur::create($profs_data);                
                return redirect()->route('professeur.index')                ;

        } catch (\Throwable $th) {
            return to_route('professeur.create')->withErrors(['error' => $th->getMessage()])->onlyInput('email', 'name', 'last_name', 'phone');
        }
                  
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
