<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserValidateRequest;
use App\Models\Classe;
use App\Models\Eleve;
// use App\Models\Parents;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login', 'register', 'refresh', 'logout', 'registerParent']]);
    }

    /* Display a listing of the resource.
     */

    // public function getUserByID(Request $request)
    // {
    // }

    /**
     * Show the form for creating a new resource.
     */
    public function login(Request $request)
    {
        try {
            $request->validate([
                'email' => 'email|required',
                'password' => 'required|min:4',
            ]);

            $credential = ["email" => $request->email, 'password' => $request->password];
            $token = Auth::guard('api')->attempt($credential);
            if (!$token) {
                return response()->json([
                    'status' => false,
                    'data' => null,
                    "error" => 'email ou mot de passe incorrete'
                ], 400);
            }

            $user = Auth::guard('api')->user();

            $isParent = $user->rule_id == 3;

            if ($isParent) {
                $parent = \App\Models\Parents::where('user_id', $user->id)->limit(1)->get()[0];
                return response()->json([
                    'auth' => ['type' => 'Bearer', 'token' => $token],
                    'status' => true,
                    'data' => ['user' => $user, 'parent' => $parent],
                ], 200);
            } else {
                $eleve = Eleve::where('user_id', $user->id)->limit(1)->get()[0];
                $classe = Classe::where("id", '=', $eleve->classe_id)->limit(1)->get()[0];
                return response()->json([
                    'auth' => [
                        'type' => 'Bearer',
                        'token' => $token,
                        // 'refresh_token'=>$refreshToken
                    ],
                    'status' => true,
                    'data' => [
                        'user' => $user,
                        'student' => $eleve,
                        "classe" => $classe,
                    ],
                ], 200);
            }
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'data' => null,
                'error' => $th->getMessage(),
            ], 400);
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
                'auth' => ['type' => 'Bearer', 'token' => $token],
                'status' => true,
                'data' => [
                    'user' => $user,
                    'student' => $student,
                    "classe" => Classe::where('id', '=', $student['classe_id'])->limit(1)->get()[0],
                ],
            ], 200);
        } catch (\Throwable $th) {
            // dd($th);
            return response()->json([
                'status' => false,
                'error' => $th->getMessage(),
                'data' => null,
            ], 400);
        }
    }


    public function refresh(Request $request)
    {
        try {
            // return response()->json($request->token);
            // $token = JWTAuth::refresh($request->token);
            $refreshToken = $request->token;
            $token = Auth::guard('api')->refresh($refreshToken);
            return response()->json(
                [
                    'status' => true,
                    'data' => [
                        'token' => $token,
                        'type' => 'Bearer',
                    ]
                ],
            );
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'error' => $th->getMessage(),
            ], 401);
        }
    }

    // public function refresh(Request $request)
    // {
    //     return response()->json([
    //         'status' => true,
    //         'user' => Auth::guard('api')->user(),
    //         'auth' => [
    //             'token' => Auth::guard('api')->refresh(),
    //             'type' => 'Bearer',
    //         ]
    //     ]);
    // }

    public function registerParent(Request $request)
    {
        try {
            $request->validate([
                'sexe' => 'required',
                'profession' => 'required|max:60',
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

            $parent_datas = [
                'sexe' => $request['sexe'],
                'profession' => $request['profession'],
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
            $parent_datas['user_id'] = Auth::guard('api')->user()->id;
            $student = \App\Models\Parents::create($parent_datas);
            return response()->json([
                'auth' => ['type' => 'Bearer', 'token' => $token],
                'status' => true,
                'data' => ['user' => $user, 'parent' => $student],
            ], 200);
        } catch (\Throwable $th) {
            // dd($th);
            return response()->json([
                'status' => false,
                'error' => $th->getMessage(),
                'data' => null,
            ], 400);
        }
    }



    public function updateProfile(Request $request)
    {
        try {
            $request->validate(['image' => 'file|required|mimetypes:image/*',]);
            $user = Auth::guard('api')->user();
            $image = $request->file('image');
            $extention = $image->extension();
            $storeArboressence = "profile/users/" . str_replace('@', '_', $user->email);
            $imageUrl = $image->store($storeArboressence . '.' . $extention, 'public');
            \Illuminate\Support\Facades\Log::info($imageUrl);
            $user->profile_image = asset("storage/$imageUrl");
            $user->save();
            return response()->json([
                'status' => true,
                'error' => null,
                'data' => $user,
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'error' => $th->getMessage(),
                'data' => null,
            ], 400);
        }
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
