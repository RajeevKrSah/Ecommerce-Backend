<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    /**
     * Register a new user
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-zA-Z\s]+$/',
                'min:2'
            ],
            'email' => [
                'required',
                'string',
                'email:rfc,dns',
                'max:255',
                'unique:users',
                'regex:/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/'
            ],
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/',
            ],
        ], [
            'name.regex' => 'Name can only contain letters and spaces.',
            'email.email' => 'Please provide a valid email address.',
            'email.unique' => 'This email is already registered.',
            'password.regex' => 'Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character.',
            'password.min' => 'Password must be at least 8 characters long.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = User::create([
                'name' => trim($request->name),
                'email' => strtolower(trim($request->email)),
                'password' => Hash::make($request->password),
                'email_verified_at' => null,
            ]);

            // Assign default 'user' role
            $user->assignRole('user');

            // Create token
            $token = $user->createToken('auth_token', ['*'], now()->addMinutes(config('sanctum.expiration')))->plainTextToken;

            Log::info('User registered successfully', [
                'user_id' => $user->id,
                'email' => $user->email,
                'role' => 'user',
            ]);

            return response()->json([
                'message' => 'Registration successful',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'email_verified_at' => $user->email_verified_at,
                    ],
                    'role' => 'user',
                    'token' => $token,
                ],
                'access_token' => $token,
                'token_type' => 'Bearer',
                'expires_in' => config('sanctum.expiration') * 60,
            ], 201);

        } catch (\Exception $e) {
            Log::error('Registration failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Registration failed. Please try again.'
            ], 500);
        }
    }

    /**
     * User login (for regular users)
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email:rfc', 'max:255'],
            'password' => ['required', 'string', 'min:6'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Invalid credentials format',
                'errors' => $validator->errors()
            ], 422);
        }

        $email = strtolower(trim($request->email));
        $user = User::where('email', $email)->first();

        // Check if account is locked
        if ($user && $user->isLocked()) {
            return response()->json([
                'message' => 'Account is temporarily locked due to multiple failed login attempts. Please try again later.',
                'locked_until' => $user->locked_until,
            ], 423);
        }

        $credentials = [
            'email' => $email,
            'password' => $request->password
        ];

        if (!Auth::attempt($credentials)) {
            // Increment failed attempts
            if ($user) {
                $user->incrementFailedAttempts();
            }

            Log::warning('Failed login attempt', [
                'email' => $email,
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'message' => 'Invalid credentials'
            ], 401);
        }

        $user = Auth::user();

        // Reset failed attempts on successful login
        $user->resetFailedAttempts();

        // Revoke all existing tokens
        $user->tokens()->delete();

        // Create new token
        $token = $user->createToken('auth_token', ['*'], now()->addMinutes(config('sanctum.expiration')))->plainTextToken;

        $role = $user->getPrimaryRole();

        Log::info('User logged in successfully', [
            'user_id' => $user->id,
            'email' => $user->email,
            'role' => $role,
            'ip' => $request->ip(),
        ]);

        return response()->json([
            'message' => 'Login successful',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'email_verified_at' => $user->email_verified_at,
                ],
                'role' => $role,
                'token' => $token,
            ],
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => config('sanctum.expiration') * 60,
        ]);
    }

    /**
     * Admin login (for admin and super_admin only)
     */
    public function adminLogin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email:rfc', 'max:255'],
            'password' => ['required', 'string', 'min:6'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Invalid credentials format',
                'errors' => $validator->errors()
            ], 422);
        }

        $email = strtolower(trim($request->email));
        $user = User::where('email', $email)->first();

        // Check if account is locked
        if ($user && $user->isLocked()) {
            Log::warning('Admin login attempt on locked account', [
                'email' => $email,
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'message' => 'Account is temporarily locked due to multiple failed login attempts. Please try again later.',
                'locked_until' => $user->locked_until,
            ], 423);
        }

        $credentials = [
            'email' => $email,
            'password' => $request->password
        ];

        if (!Auth::attempt($credentials)) {
            // Increment failed attempts
            if ($user) {
                $user->incrementFailedAttempts();
            }

            Log::warning('Failed admin login attempt', [
                'email' => $email,
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'message' => 'Invalid credentials'
            ], 401);
        }

        $user = Auth::user();

        // Check if user has admin or super_admin role
        if (!$user->hasAnyRole(['admin', 'super_admin'])) {
            Log::warning('Non-admin user attempted admin login', [
                'user_id' => $user->id,
                'email' => $user->email,
                'role' => $user->getPrimaryRole(),
                'ip' => $request->ip(),
            ]);

            Auth::logout();

            return response()->json([
                'message' => 'Unauthorized. Admin access required.'
            ], 403);
        }

        // Reset failed attempts on successful login
        $user->resetFailedAttempts();

        // Revoke all existing tokens
        $user->tokens()->delete();

        // Create new token
        $token = $user->createToken('admin_auth_token', ['*'], now()->addMinutes(config('sanctum.expiration')))->plainTextToken;

        $role = $user->getPrimaryRole();

        Log::info('Admin logged in successfully', [
            'user_id' => $user->id,
            'email' => $user->email,
            'role' => $role,
            'ip' => $request->ip(),
        ]);

        return response()->json([
            'message' => 'Admin login successful',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'email_verified_at' => $user->email_verified_at,
                ],
                'role' => $role,
                'token' => $token,
            ],
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => config('sanctum.expiration') * 60,
        ]);
    }

    /**
     * Logout user
     */
    public function logout(Request $request)
    {
        try {
            $request->user()->currentAccessToken()->delete();
            
            Log::info('User logged out', [
                'user_id' => $request->user()->id,
            ]);

            return response()->json([
                'message' => 'Logged out successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Logout failed'
            ], 500);
        }
    }

    /**
     * Logout from all devices
     */
    public function logoutAll(Request $request)
    {
        try {
            $request->user()->tokens()->delete();
            
            Log::info('User logged out from all devices', [
                'user_id' => $request->user()->id,
            ]);

            return response()->json([
                'message' => 'Logged out from all devices successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Logout failed'
            ], 500);
        }
    }

    /**
     * Get user profile
     */
    public function profile(Request $request)
    {
        $user = $request->user();
        $role = $user->getPrimaryRole();

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $role,
                'permissions' => $user->getAllPermissions()->pluck('name'),
                'email_verified_at' => $user->email_verified_at,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ]
        ]);
    }

    /**
     * Refresh token
     */
    public function refresh(Request $request)
    {
        try {
            $user = $request->user();
            
            // Delete current token
            $request->user()->currentAccessToken()->delete();
            
            // Create new token
            $token = $user->createToken('auth_token', ['*'], now()->addMinutes(config('sanctum.expiration')))->plainTextToken;
            
            return response()->json([
                'message' => 'Token refreshed successfully',
                'access_token' => $token,
                'token_type' => 'Bearer',
                'expires_in' => config('sanctum.expiration') * 60,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Token refresh failed'
            ], 500);
        }
    }
}