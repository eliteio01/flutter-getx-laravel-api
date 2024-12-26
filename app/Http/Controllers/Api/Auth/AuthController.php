<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Factory;

class AuthController extends Controller
{
    public function register(RegisterRequest $request)
    {

        try {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->name),
            ]);


            $token = $this->generateToken($user);

            return response()->json([
                'message' => 'User created successfully.',
                'token' => $token,
                'user' => $user
            ], 201);
        } catch (\Exception $e) {

            Log::error('Error during registration: ' . $e->getMessage());

            return response()->json([
                'message' => 'An error occured during registration. pls try again later',

            ], 500);
        }
    }
    public function googleSignin(Request $request)
    {

        $idToken = $request->idToken;

        try {

            $auth = (new Factory)->withServiceAccount(base_path('FIREBASE_DATABASE_URL'))->createAuth();

            $verifiedIdToken = $auth->verifyIdToken($idToken);

            $claims = $verifiedIdToken->claims();
            $email = $claims->get('email');
            $name = $claims->get('name');
            $firebaseUid = $claims->get('sub');


            $user = User::firstOrCreate([
                'email' => $email
            ], ['name' => $name, 'firebase_uid' => $firebaseUid, 'password' => Hash::make(uniqid())]);

            $token = $this->generateToken($user);

            return response()->json([
                'message' => 'Logged in successfully.',
                'token' => $token,
                'user' => $user
            ], 201);
        } catch (\Exception $e) {

            Log::error('Error during registration: ' . $e->getMessage());

            return response()->json([
                'message' => 'An error occured during registration. pls try again later',

            ], 500);
        }
    }
    private function generateToken(User $user)
    {
        return $user->createToken('web-api-token')->accessToken;
    }
}
