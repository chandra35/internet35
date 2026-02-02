<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class LoginController extends Controller implements HasMiddleware
{
    protected ActivityLogService $activityLog;

    public static function middleware(): array
    {
        return [
            new Middleware('guest', except: ['logout']),
        ];
    }

    public function __construct(ActivityLogService $activityLog)
    {
        $this->activityLog = $activityLog;
    }

    /**
     * Show the login form
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Handle login request
     */
    public function login(Request $request)
    {
        $request->validate([
            'login' => 'required|string',
            'password' => 'required|string|min:6',
        ], [
            'login.required' => 'Email atau ID Pelanggan wajib diisi.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal 6 karakter.',
        ]);

        $login = $request->input('login');
        $password = $request->input('password');
        $remember = $request->filled('remember');

        // Determine if login is email or customer_id
        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'customer_id';
        
        // Find user by email or customer_id (via customerProfile relationship)
        $user = null;
        if ($field === 'email') {
            $user = User::where('email', $login)->first();
        } else {
            // Find by customer_id
            $user = User::whereHas('customerProfile', function($q) use ($login) {
                $q->where('customer_id', $login);
            })->first();
        }

        if ($user && Auth::attempt(['email' => $user->email, 'password' => $password], $remember)) {
            $user = Auth::user();

            // Check if user is active
            if (!$user->is_active) {
                Auth::logout();
                throw ValidationException::withMessages([
                    'email' => ['Akun Anda telah dinonaktifkan. Silakan hubungi administrator.'],
                ]);
            }

            $request->session()->regenerate();

            // Log activity
            $this->activityLog->logLogin();

            // Return JSON for AJAX request
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Login berhasil!',
                    'redirect' => $this->redirectTo(),
                ]);
            }

            return redirect()->intended($this->redirectTo());
        }

        // Log failed login attempt
        $this->activityLog->log('login_failed', 'auth', 'Failed login attempt for: ' . $request->login);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'Email/ID Pelanggan atau password salah.',
            ], 422);
        }

        throw ValidationException::withMessages([
            'login' => ['Email/ID Pelanggan atau password salah.'],
        ]);
    }

    /**
     * Handle logout request
     */
    public function logout(Request $request)
    {
        // Log activity before logout
        $this->activityLog->logLogout();

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Logout berhasil!',
                'redirect' => route('login'),
            ]);
        }

        return redirect()->route('login')->with('success', 'Anda telah berhasil logout.');
    }

    /**
     * Get the redirect path after login
     */
    protected function redirectTo(): string
    {
        $user = Auth::user();

        if ($user->hasRole('client')) {
            return route('client.dashboard');
        }

        return route('admin.dashboard');
    }
}
