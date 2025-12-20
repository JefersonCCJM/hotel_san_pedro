<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - Hotel San Pedro</title>
    
    @include('partials.seo', [
        'title' => 'Registro',
        'description' => 'Crea tu cuenta en el sistema de gestión hotelera de Hotel San Pedro. Accede a funcionalidades de reservaciones y administración.'
    ])
    
    <!-- TailwindCSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        .login-bg {
            background-image: url('{{ asset('assets/img/backgrounds/login-bg.jpeg') }}');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }
        .login-overlay {
            background: rgba(0, 0, 0, 0.4);
            backdrop-filter: blur(8px);
        }
        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-slide-up {
            animation: slideUp 0.6s cubic-bezier(0.16, 1, 0.3, 1);
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center login-bg relative overflow-y-auto py-6 sm:py-12">
    <div class="absolute inset-0 login-overlay fixed"></div>

    <div class="max-w-lg w-full px-4 z-10 animate-slide-up my-auto">
        <div class="relative">
            <!-- Badge -->
            <div class="absolute -top-6 left-1/2 -translate-x-1/2 z-20">
                <div class="bg-slate-900/90 text-white px-5 py-2.5 rounded-2xl flex items-center shadow-2xl text-[10px] sm:text-xs font-bold whitespace-nowrap border border-white/10 backdrop-blur-md tracking-wider">
                    <i class="fas fa-hotel mr-2 text-sm text-slate-300"></i>
                    SISTEMA DE GESTIÓN HOTELERA
                </div>
            </div>

            <!-- Register Card -->
            <div class="glass-card pt-10 pb-8 px-6 sm:px-12 rounded-[2.5rem] shadow-2xl overflow-hidden">
                <!-- Logo -->
                <div class="text-center mb-6">
                    <div class="inline-flex items-center justify-center w-14 h-14 bg-slate-100 rounded-2xl mb-3">
                        <i class="fas fa-user-plus text-slate-800 text-2xl"></i>
                    </div>
                    <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Crear Cuenta</h1>
                    <p class="mt-1 text-slate-500 text-sm font-medium italic">Hotel San Pedro</p>
                </div>

                <form class="space-y-4" method="POST" action="{{ route('register') }}">
                    @csrf
                    
                    <!-- Name Field -->
                    <div>
                        <label for="name" class="flex items-center text-xs font-semibold text-slate-700 mb-1.5 ml-1">
                            <i class="fas fa-id-card mr-2 text-slate-400"></i>
                            Nombre Completo
                        </label>
                        <div class="relative group">
                            <input id="name" name="name" type="text" required
                                   class="block w-full pl-4 pr-12 py-3 bg-slate-50/50 border border-slate-200 rounded-2xl text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-slate-900/5 focus:border-slate-900 transition-all duration-200 text-sm"
                                   placeholder="Tu nombre completo"
                                   value="{{ old('name') }}">
                        </div>
                        @error('name')
                            <p class="mt-1.5 text-xs text-red-500 flex items-center font-medium">
                                <i class="fas fa-circle-exclamation mr-1.5"></i>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <!-- Email Field -->
                    <div>
                        <label for="email" class="flex items-center text-xs font-semibold text-slate-700 mb-1.5 ml-1">
                            <i class="fas fa-envelope-open mr-2 text-slate-400"></i>
                            Correo Electrónico
                        </label>
                        <div class="relative group">
                            <input id="email" name="email" type="email" required autocomplete="email"
                                   class="block w-full pl-4 pr-12 py-3 bg-slate-50/50 border border-slate-200 rounded-2xl text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-slate-900/5 focus:border-slate-900 transition-all duration-200 text-sm"
                                   placeholder="correo@ejemplo.com"
                                   value="{{ old('email') }}">
                        </div>
                        @error('email')
                            <p class="mt-1.5 text-xs text-red-500 flex items-center font-medium">
                                <i class="fas fa-circle-exclamation mr-1.5"></i>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>
                    
                    <!-- Password Field -->
                    <div>
                        <label for="password" class="flex items-center text-xs font-semibold text-slate-700 mb-1.5 ml-1">
                            <i class="fas fa-shield-alt mr-2 text-slate-400"></i>
                            Contraseña
                        </label>
                        <div class="relative group">
                            <input id="password" name="password" type="password" required autocomplete="new-password"
                                   class="block w-full pl-4 pr-12 py-3 bg-slate-50/50 border border-slate-200 rounded-2xl text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-slate-900/5 focus:border-slate-900 transition-all duration-200 text-sm"
                                   placeholder="••••••••">
                        </div>
                        @error('password')
                            <p class="mt-1.5 text-xs text-red-500 flex items-center font-medium">
                                <i class="fas fa-circle-exclamation mr-1.5"></i>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <!-- Confirm Password Field -->
                    <div>
                        <label for="password_confirmation" class="flex items-center text-xs font-semibold text-slate-700 mb-1.5 ml-1">
                            <i class="fas fa-check-double mr-2 text-slate-400"></i>
                            Confirmar Contraseña
                        </label>
                        <div class="relative group">
                            <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password"
                                   class="block w-full pl-4 pr-12 py-3 bg-slate-50/50 border border-slate-200 rounded-2xl text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-slate-900/5 focus:border-slate-900 transition-all duration-200 text-sm"
                                   placeholder="••••••••">
                        </div>
                    </div>
                    
                    <!-- Submit Button -->
                    <div class="pt-2">
                        <button type="submit" 
                                class="w-full flex justify-center items-center py-3.5 px-4 border border-transparent text-sm font-bold rounded-2xl text-white bg-slate-900 hover:bg-slate-800 focus:outline-none focus:ring-4 focus:ring-slate-900/10 shadow-xl shadow-slate-900/20 transition-all duration-200 active:scale-[0.98]">
                            <i class="fas fa-user-plus mr-2"></i>
                            Crear Cuenta
                        </button>
                    </div>
                </form>

                <!-- Divider -->
                <div class="relative my-6">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-slate-100"></div>
                    </div>
                    <div class="relative flex justify-center text-[10px]">
                        <span class="px-4 bg-white/50 text-slate-400 font-medium backdrop-blur-sm uppercase tracking-wider">¿Ya tienes cuenta?</span>
                    </div>
                </div>

                <div class="text-center">
                    <a href="{{ route('login') }}" class="inline-flex items-center justify-center px-6 py-2.5 text-xs font-bold text-slate-700 bg-slate-100 rounded-xl border border-slate-200 hover:bg-slate-200 transition-all duration-200 w-full sm:w-auto">
                        <i class="fas fa-sign-in-alt mr-2 text-[10px]"></i>
                        Inicia sesión aquí
                    </a>
                </div>

                <!-- Information Section -->
                <div class="mt-6 pt-5 border-t border-slate-100">
                    <div class="bg-slate-50 rounded-2xl p-3 flex items-start space-x-3">
                        <div class="flex-shrink-0 mt-0.5">
                            <i class="fas fa-info-circle text-slate-400 text-xs"></i>
                        </div>
                        <p class="text-[10px] text-slate-500 font-medium leading-relaxed">
                            Rol asignado automáticamente: <span class="text-slate-900 font-bold">"Cliente"</span>.
                        </p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="mt-6 text-center">
            <p class="text-white/60 text-[10px] font-semibold tracking-widest uppercase">
                &copy; {{ date('Y') }} HOTEL SAN PEDRO
            </p>
        </div>
    </div>
</body>
</html>
