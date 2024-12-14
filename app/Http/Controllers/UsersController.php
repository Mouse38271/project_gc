<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UsersController extends Controller
{
    // Menampilkan halaman registrasi
    public function getRegisterPage()
    {
        return view('register_page');
    }

    // Menampilkan halaman login
    public function getLoginPage()
    {
        return view('login_page');
    }

    // Proses registrasi pengguna
    public function register(Request $request)
    {
        // Validasi input
        $request->validate([
            'login' => 'required|unique:users,login',
            'password' => 'required|min:6',
            'email' => 'required|email|unique:users,email',
        ]);

        // Buat pengguna baru
        $user = new User;
        $user->login = $request->input('login');
        $user->password = bcrypt($request->input('password')); // Enkripsi password
        $user->email = $request->input('email');
        $user->save();

        // Redirect ke halaman login
        return redirect()->route('login_page')->with('success', 'Registration successful! Please log in.');
    }

    // Proses login pengguna
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        // Mencari pengguna berdasarkan email
        $user = User::where('email', $request->input('email'))->first();

        // Periksa jika pengguna ditemukan dan password cocok
        if ($user && Hash::check($request->input('password'), $user->password)) {
            // Login sukses, set autentikasi
            Auth::login($user); // Menambahkan autentikasi pengguna
            // Redirect ke halaman personal
            return redirect()->route('personal_page'); // Mengarahkan ke rute halaman personal
        }

        // Jika login gagal, kembali ke halaman login dengan pesan error
        return back()->withErrors(['message' => 'Invalid credentials']);
    }

    // Menampilkan halaman ganti password
    public function getChangePasswordPage()
    {
        return view('change_password_page');
    }

    // Proses ganti password
    public function changePassword(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'old_password' => 'required|string',
            'new_password' => 'required|string|min:8',
        ]);

        $user = User::where('email', $request->input('email'))->first();

        if ($user && Hash::check($request->input('old_password'), $user->password)) {
            // Enkripsi password baru dan simpan
            $user->password = bcrypt($request->input('new_password'));
            $user->save();

            return redirect()->route('login_page')->with('success', 'Password changed successfully');
        }

        return back()->withErrors(['message' => 'Invalid credentials']);
    }

    // Menampilkan halaman kelas
    public function welcome()
    {
        // Mengirimkan data login pengguna
        $userLogin = Auth::user() ? Auth::user()->login : 'Pengguna Tidak Terdaftar';
        return view('class_page', ['userLogin' => $userLogin]); 
    }

    // Menampilkan halaman tugas
    public function viewTaskPage()
    {
        return view('task-page');
    }

    // Menampilkan halaman personal setelah login
    public function getPersonalPage()
{
    $user = Auth::user();

    if (!$user) {
        return redirect()->route('login_page')->withErrors('Silakan login terlebih dahulu.');
    }

    // Ambil semua tugas dari kelas yang diikuti pengguna
    $tasks = $user->kelas()->with('tasks')->get()
        ->pluck('tasks') // Ambil hanya data tugas dari relasi
        ->flatten() // Flatten array multi-dimensional menjadi satu dimensi
        ->filter(function ($task) {
            return $task !== null; // Hapus tugas yang nilainya null
        });

    // Debugging data

    return view('personal_page', [
        'userLogin' => $user->login,
        'tasks' => $tasks, // Pastikan ini dikirim ke view
        'classes' => $user->kelas()->with('dosen')->get(),
    ]);
}
}
