<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserValidateRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class Usercontroller extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function login()
    {
        return view('screen.auth.login');
    }
    public function register()
    {
        return view('screen.auth.register');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function signIn(Request $request)
    {
        try {
            $request->validate([
                'email' => 'email|required',
                'password' => 'required|min:4'
            ]);
            
            $credential = ["email" => $request->email, 'password'=>$request->password];
            if (Auth::attempt($credential)) {
                $request->session()->regenerate();
                return redirect()->intended(route('index'));
            }
            return to_route('auth.login')->withErrors([
                'email' => "Email ou mot de passe incorrecte",
            ])->onlyInput('email');
        } catch (\Throwable $th) {
            // dd($th);
            return to_route('auth.login')->withErrors([
                'error' => $th->getMessage(), 
            ])->onlyInput('email');
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(UserValidateRequest $request)
    {
        try {
            $userData = $request->all();
            $userData['password'] = Hash::make($request->password);            
            $unique_token = (string) Str::uuid();
            while (true) {
                $user = User::where('unique_token', '=', $unique_token)->exists();
                if($user){
                    $unique_token = (string) Str::uuid();
                }else{
                    $userData['unique_token'] = $unique_token;
                    break;                    
                }                
            }
            
            User::create($userData);
            $credential  = $request->only('email', "password");
            if (Auth::attempt($credential)) {
                $request->session()->regenerate();
                return redirect()->intended(route('index'));
            }
            return to_route('auth.register')->withErrors([])->onlyInput('email', 'name', 'last_name', 'phone');
        } catch (\Throwable $th) {
            // dd($th);
            return to_route('auth.register')->withErrors(['error' => $th->getMessage()])->onlyInput('email', 'name', 'last_name', 'phone');
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
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('auth.login');

    }
}
