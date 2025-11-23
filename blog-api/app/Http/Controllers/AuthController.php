<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    // Registration
    public function register(Request $request)
    {
        try {
            $passwordRule = Password::min(8)->letters()->numbers()->mixedCase()->symbols()->uncompromised(0);

            if (app()->environment('production')) {
                $passwordRule = $passwordRule->uncompromised(3);
            }
    
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => ['required', 'confirmed', $passwordRule],
            ]);

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            $token = $user->createToken('api-token', [
                'posts:read',
                'posts:write-own', 
                'profile:read',
                'auth:logout'
            ], now()->addDays(7))->plainTextToken;

            return response()->json([
                'user' => $user,
                'token' => $token
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Registration failed', [
                'exception' => $e->getMessage(),
                'email' => $request->email,
                'ip' => $request->ip()
            ]);

            return response()->json([
                'error' => 'Registration failed. Please try again later.'
            ], 500);
        }
    }

    // Login 
    public function login(Request $request)
    {
        try {
            $allowedFields = ['email', 'password'];
            
            // Checking for additional fields
            $receivedFields = array_keys($request->all());
            $extraFields = array_diff($receivedFields, $allowedFields);
            
            if (!empty($extraFields)) {
                return response()->json([
                    'error' => 'Invalid request format',
                    'invalid_fields' => array_values($extraFields)
                ], 422);
            }
    
            $request->validate([
                'email' => 'required|email',
                'password' => 'required|string|max:255'
            ]);
    
            $user = User::where('email', $request->email)->first();
    
            if (!$user || !Hash::check($request->password, $user->password)) {
                Log::warning('Failed login attempt', [
                    'email' => $request->email,
                    'ip' => $request->ip()
                ]);
    
                return response()->json([
                    'error' => 'The provided credentials are incorrect.'
                ], 401);
            }
    
            // Revoke only expired tokens 
            $this->revokeExpiredTokens($user);
    
           $token = $user->createToken('api-token', [
               'posts:read',
               'posts:write-own', 
               'profile:read',
               'auth:logout'
           ], now()->addDays(7))->plainTextToken;
    
            Log::info('User logged in successfully', [
                'user_id' => $user->id,
                'email' => $user->email,
                'ip' => $request->ip(),
                'device' => $request->userAgent()
            ]);
    
            return response()->json([
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email
                ]
            ]);
    
        } catch (ValidationException $e) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Login system error', [
                'exception' => $e->getMessage(),
                'email' => $request->email,
                'ip' => $request->ip()
            ]);
    
            return response()->json([
                'error' => 'Login failed. Please try again later.'
            ], 500);
        }
    }
    
    private function revokeExpiredTokens(User $user): void
    {
        $user->tokens()
            ->where('expires_at', '<', now())
            ->orWhere('created_at', '<', now()->subDays(90)) 
            ->delete();
        
        $user->tokens()
            ->whereNull('expires_at')
            ->where('created_at', '<', now()->subDays(30))
            ->delete();
    }
    
    public function logout(Request $request)
    {
        try {
            $request->user()->currentAccessToken()->delete();

            Log::info('User logged out', [
                'user_id' => $request->user()->id,
                'ip' => $request->ip()
            ]);

            return response()->json(['message' => 'Successfully logged out']);
        } catch (\Exception $e) {
            Log::error('Logout failed', [
                'exception' => $e->getMessage(),
                'user_id' => $request->user()->id ?? 'unknown',
                'ip' => $request->ip()
            ]);

            return response()->json([
                'error' => 'Logout failed'
            ], 500);
        }
    }
}