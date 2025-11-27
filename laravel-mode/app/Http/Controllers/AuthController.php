<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    /**
     * Show login form
     */
    public function showLogin()
    {
        if (Auth::check()) {
            return $this->redirectByUserType();
        }
        return view('auth.login');
    }

    /**
     * Process login
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)
            ->where('is_active', true)
            ->first();

        if ($user && Hash::check($request->password, $user->password)) {
            Auth::login($user, $request->filled('remember'));

            // Set additional session data
            session([
                'user_id' => $user->user_id,
                'user_email' => $user->email,
                'user_name' => $user->first_name . ' ' . $user->last_name,
                'user_phone' => $user->phone,
                'user_address' => $user->address,
                'user_type' => $user->user_type,
                'user_location_id' => $user->location_id,
            ]);

            return $this->redirectByUserType();
        }

        return back()->withErrors([
            'email' => 'Email atau password salah.',
        ])->withInput($request->only('email'));
    }

    /**
     * Show registration form
     */
    public function showRegister()
    {
        if (Auth::check()) {
            return $this->redirectByUserType();
        }
        return view('auth.register');
    }

    /**
     * Process registration
     */
    public function register(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|string|max:15',
            'password' => 'required|min:6|confirmed',
        ]);

        DB::beginTransaction();
        try {
            // Create user
            $user = User::create([
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'phone' => $request->phone,
                'address' => $request->address,
                'user_type' => 'customer',
                'is_active' => true,
            ]);

            // Assign Customer role (role_id = 3)
            DB::table('user_roles')->insert([
                'user_id' => $user->user_id,
                'role_id' => 3, // Customer role
            ]);

            DB::commit();

            // Auto login
            Auth::login($user);

            session([
                'user_id' => $user->user_id,
                'user_email' => $user->email,
                'user_name' => $user->first_name . ' ' . $user->last_name,
                'user_phone' => $user->phone,
                'user_address' => $user->address,
                'user_type' => 'customer',
            ]);

            return redirect()->route('home')->with('success', 'Registrasi berhasil! Selamat datang.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Registrasi gagal. Silakan coba lagi.'])->withInput();
        }
    }

    /**
     * Logout
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Anda telah logout.');
    }

    /**
     * Redirect user based on their type
     */
    private function redirectByUserType()
    {
        $user = Auth::user();

        if ($user->user_type == 'customer') {
            return redirect()->route('home');
        } else {
            // Staff or owner to dashboard
            return redirect()->route('staff.dashboard');
        }
    }
}
