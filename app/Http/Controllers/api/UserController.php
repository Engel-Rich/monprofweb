<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
// use App\Http\Requests\UserValidateRequest;
use App\Models\Classe;
use App\Models\Eleve;
use App\Models\OTP;
// use App\Models\Parents;
use App\Models\User;
use App\Services\FileManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum', ['except' => ['login', 'register', 'refresh', 'logout', 'registerParent', 'resetPassword']]);
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
            $exist = Auth::attempt($credential);
            if (!$exist) {
                return response()->json([
                    'status' => false,
                    'data' => null,
                    "error" => 'email ou mot de passe incorrete'
                ], 400);
            }

            $user = Auth::user();

            $isParent = $user->rule_id == 3;
            $token = User::find($user->id)->createToken('MONPROF_WEB')->plainTextToken;
            $refreshToken = $this->createRefreshTokem($user);

            if ($isParent) {
                $parent = \App\Models\Parents::where('user_id', $user->id)->limit(1)->get()[0];
                return response()->json([
                    'auth' => ['type' => 'Bearer', 'token' => $token, 'refresh_token' => $refreshToken],
                    'status' => true,
                    'data' => ['user' => $user, 'parent' => $parent],
                ], 200);
            } else {
                if ($user->user_phone_emei != $request->header('phone-emei')) {
              //      Auth::guard('api')->logout();
                    return response()->json([
                        'status' => false,
                        'data' => null,
                        'type' => 'phone-emei',
                        'error' => 'Vous n\'etes pas autorisé a vous connecter sur ce telephone',
                    ], 422);
                }
                $eleve = Eleve::where('user_id', $user->id)->limit(1)->get()[0];
                $classe = Classe::where("id", '=', $eleve->classe_id)->limit(1)->get()[0];
                if ($user->profile_image != null) {
                    $storeArboressence = "profile/users/$user->phone";
                    $fileService = new FileManager($storeArboressence);
                    $user->profile_image = $fileService->get($user->profile_image);
                }
                return response()->json([
                    'auth' => [
                        'type' => 'Bearer',
                        'token' => $token,
                        'refresh_token' => $refreshToken,
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

    public function updateTocken(Request $request)
    {
        try {
            $request->validate(['fcm_token' => 'string|required']);
            $user = Auth::user();
            $userapp = User::find($user->id);
            $userapp->fcm_token = $request->fcm_token;
            $userapp->save();
            return response()->json([
                'status' => true,
                'data' => 'success'
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'data' => $th->getMessage()
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
                'user_phone_emei' => $request->header('phone-emei'),
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
            $refreshToken = $this->createRefreshTokem($user);
            $token = $user->createToken('MONPROF_WEB')->plainTextToken; //Auth::guard('api')->login($user);
            $eleve_data['user_id'] = $user->id;
            $student = Eleve::create($eleve_data);
            return response()->json([
                'auth' => ['type' => 'Bearer', 'token' => $token, 'refresh_token' => $refreshToken],
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
            $request->validate(['refresh_token' => 'required']);

            $user = User::where('refresh_token', $request->refresh_token)->first();

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'error' => 'Invalid refresh token',
                ], 401);
            }
            // Générer un nouveau token
            $newToken = $user->createToken('auth-token')->plainTextToken;
            // Optionnel : Regénérer le refresh token
            $refreshToken = Str::random(64);
            $user->update(['refresh_token' => $refreshToken]);
            return response()->json([
                'status' => true,
                'data' => [
                    'token' => $newToken,
                    'refresh_token' => $refreshToken,
                ],
            ]);
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

    public function logout(Request $request)
    {
        try {

            $user = $request->user();
            $user->currentAccessToken()->delete();
            $user->refresh_token = null;
            $user->save();
            return response()->json([
                'status' => true,
                'data' => 'success',
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'data' => $th->getMessage(),
            ], 400);
        }
    }

    /**
     * Reset password with OTP
     */

    public function resetPassword(Request $request)
    {
        try {

            $request->validate([
                'phone' => 'required|string|exists:users,phone',
                'type' => 'required|string|in:password,phone_emei',
                'verification_id' => 'required|string',
                'otp' => 'required|string|exists:otps,otp',
            ]);
            if ($request->type == 'password') {
                $request->validate([
                    'password' => 'string|min:6',
                    // 'confirm_password' => 'required|string|same:password',
                ]);
            }
            $otp = OTP::where('phone', $request->phone)
                ->where('otp', $request->otp)
                ->where('verification_id', $request->verification_id)
                ->where('expired_at', '>', now())->first();
            if (!$otp) {
                return response()->json([
                    'status' => false,
                    'data' => null,
                    'error' => 'Code de verification a expiré vous pouvez en demander un autre',
                ], 400);
            }
            $user = User::where('phone', $request->phone)->first();
            $user->refresh_token = null;
            if ($request->type == 'password') {
                $user->password = Hash::make($request->password);
                $user->save();
            } else {
                $user->user_phone_emei = $request->header('phone-emei');
                $user->save();
            }
            $otp->update(['is_used' => true]);
            return response()->json([
                'status' => true,
                'data' => $user,
                'error' => null,
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'data' => null,
                'error' => $th->getMessage(),
            ], 400);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'data' => null,
                'error' => $th->getMessage(),
            ]);
        }
    }


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
                'user_phone_emei' => $request->header('phone-emei'),
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

            $token =  $user->createToken('MONPROF_WEB')->plainTextToken;
            $refreshToken = $this->createRefreshTokem($user);
            $parent_datas['user_id'] = $user->id;
            $student = \App\Models\Parents::create($parent_datas);
            return response()->json([
                'auth' => ['type' => 'Bearer', 'token' => $token, 'refresh_token' => $refreshToken],
                'status' => true,
                'data' => ['user' => $user, 'parent' => $student],
            ], 200);
        } catch (\Throwable $th) {
            // dd($th);
            return response()->json([
                'status' => false,
                'error' => $th->getMessage(),
                'data' => null,
            ], 400,);
        }
    }



    public function updateProfile(Request $request)
    {
        try {
            $request->validate(['image' => 'file|required|mimetypes:image/*',]);
            $user = Auth::user();
            $image = $request->file('image');
            // $extention = $image->extension();
            $storeArboressence = "profile/users/" . $user->phone;
            $fileService = new FileManager($storeArboressence);
            $imageUrl = $fileService->store($image);
            // \Illuminate\Support\Facades\Log::info($imageUrl);
            $user->profile_image = $imageUrl;
            User::find($user->id)->save();
            $user->profile_image = $fileService->get($imageUrl);
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

    private function createRefreshTokem($user): string
    {
        $refreshToken = Str::random(64);
        $user->update(['refresh_token' => $refreshToken]);
        return $refreshToken;
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
