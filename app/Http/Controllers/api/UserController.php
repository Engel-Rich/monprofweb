<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserValidateRequest;
use App\Models\Classe;
use App\Models\Eleve;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login', 'register', 'refresh', 'logout']]);
    }

    /* Display a listing of the resource.
     */

     public function getUserByID(Request $request)  {
         
     }

    /**
     * Show the form for creating a new resource.
     */
    public function login(Request $request)
    {
        try {
            $request->validate([
                'email' => 'email|required',
                'password' => 'required|min:4',
                'parent' =>'booleen|nullable'
            ]);

            $credential = ["email" => $request->email, 'password' => $request->password];
            $token = Auth::guard('api')->attempt($credential);
            if (!$token) {
                return response()->json([
                    'status' => false,
                    'data' => null,
                    "error" => 'email ou mot de passe incorrete'
                ], 401);
            }
            $user = Auth::guard('api')->user();
            $eleve = Eleve::where('user_id',$user->id)->limit(1)->get()[0];
            $classe = Classe::where("id",'=', $eleve->classe_id)->limit(1)->get()[0];
            return response()->json([
                'auth' => ['type' => 'bearer', 'token' => $token],
                'status' => true,
                'data' => [
                    'user' => $user,
                    'student' => $eleve ,
                    "classe" => $classe,
                ],
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'data' => null,
                'error' => $th->getMessage(),
            ], );
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function register(Request $request)
    {
        try {
            $request->validate([
                'sexe' => 'required',
                'etablissement' => 'required|max:100',
                "classe_id" => 'integer|exists:classes,id',
                'rule_id' => 'integer|nullable',
                'name' => 'required|max:50',
                'last_name' => 'nullable|max:30',
                'phone' => 'required|max:14|unique:users,phone',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|min:4',
            ]);
            $userData = [
                'rule_id' => 2,
                'name' => $request->name,
                'last_name' => $request->last_name,
                'phone' => $request->phone,
                'email' => $request->email,
                'password' =>  Hash::make($request->password),
            ];

            $eleve_data = [
                'sexe' => $request['sexe'],
                'etablissement' => $request['etablissement'],
                "classe_id" => $request['classe_id']
            ];
            // $userData['password'] = Hash::make($request->password);            
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

            $token = Auth::guard('api')->login($user);
            $eleve_data['user_id'] = Auth::guard('api')->user()->id;
            $student = Eleve::create($eleve_data);
            return response()->json([
                'auth' => ['type' => 'bearer', 'token' => $token],
                'status' => true,
                'data' => [
                    'user' => $user, 
                    'student' => $student,
                    "classe" => Classe::where('id','=',$student['classe_id'])->limit(1)->get()[0],
                ],
            ], 200);
        } catch (\Throwable $th) {
            // dd($th);
            return response()->json([
                'status' => false,
                'error' => $th->getMessage(),
                'data' => null,
            ], );
        }
    }


    // public function refresh(Request $request)
    // {
    //     return response()->json([
    //         'status' => true,
    //         'user' => Auth::guard('api')->user(),
    //         'auth' => [
    //             'token' => Auth::guard('api')->refresh(),
    //             'type' => 'bearer',
    //         ]
    //     ]);
    // }




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
