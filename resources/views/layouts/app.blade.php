<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'MovilTech') - Sistema de Gestión</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <!-- TailwindCSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Alpine.js -->
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Alpine.js Cloak -->
    <style>
        [x-cloak] { display: none !important; }
    </style>
    
    @stack('styles')
</head>
<body class="bg-gray-100" x-data="{ sidebarOpen: false }" x-cloak>
    <div class="min-h-screen flex">
        <!-- Overlay para móvil -->
        <div x-show="sidebarOpen"
             x-cloak
             @click="sidebarOpen = false"
             x-transition:enter="transition-opacity ease-linear duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-linear duration-300"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-gray-600 bg-opacity-75 z-40 lg:hidden"
             style="display: none;"></div>
        
        <!-- Sidebar -->
        <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
               class="fixed inset-y-0 left-0 z-50 w-64 bg-gray-800 text-white transform transition-transform duration-300 ease-in-out lg:translate-x-0 lg:static lg:inset-0 flex-shrink-0 flex flex-col">
            <div class="flex items-center justify-between p-4 lg:justify-center">
                <div>
                    <h1 class="text-xl lg:text-2xl font-bold text-center">MovilTech</h1>
                    <p class="text-gray-400 text-xs lg:text-sm text-center">Sistema de Gestión</p>
                </div>
                <button @click="sidebarOpen = false" class="lg:hidden text-gray-400 hover:text-white">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <nav class="mt-4 lg:mt-8 flex-1 overflow-y-auto">
                <div class="px-4 mb-4">
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Menú Principal</p>
                </div>
                
                <a href="{{ route('dashboard') }}" @click="sidebarOpen = false" class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-700 hover:text-white transition-colors {{ request()->routeIs('dashboard') ? 'bg-gray-700 text-white' : '' }}">
                    <i class="fas fa-tachometer-alt w-5"></i>
                    <span class="ml-3">Dashboard</span>
                </a>
                
                @can('view_products')
                <a href="{{ route('products.index') }}" @click="sidebarOpen = false" class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-700 hover:text-white transition-colors {{ request()->routeIs('products.*') ? 'bg-gray-700 text-white' : '' }}">
                    <i class="fas fa-boxes w-5"></i>
                    <span class="ml-3">Inventario</span>
                </a>
                @endcan
                
                @can('view_categories')
                <a href="{{ route('categories.index') }}" @click="sidebarOpen = false" class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-700 hover:text-white transition-colors {{ request()->routeIs('categories.*') ? 'bg-gray-700 text-white' : '' }}">
                    <i class="fas fa-tags w-5"></i>
                    <span class="ml-3">Categorías</span>
                </a>
                @endcan
                
                <a href="{{ route('customers.index') }}" @click="sidebarOpen = false" class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-700 hover:text-white transition-colors {{ request()->routeIs('customers.*') ? 'bg-gray-700 text-white' : '' }}">
                    <i class="fas fa-users w-5"></i>
                    <span class="ml-3">Clientes</span>
                </a>
                
                @can('generate_invoices')
                <a href="{{ route('electronic-invoices.index') }}" @click="sidebarOpen = false" class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-700 hover:text-white transition-colors {{ request()->routeIs('electronic-invoices.*') ? 'bg-gray-700 text-white' : '' }}">
                    <i class="fas fa-file-invoice-dollar w-5"></i>
                    <span class="ml-3">Facturas Electrónicas</span>
                </a>
                @endcan
                
                @can('view_reports')
                <a href="{{ route('reports.index') }}" @click="sidebarOpen = false" class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-700 hover:text-white transition-colors {{ request()->routeIs('reports.*') ? 'bg-gray-700 text-white' : '' }}">
                    <i class="fas fa-chart-bar w-5"></i>
                    <span class="ml-3">Reportes</span>
                </a>
                @endcan

                @can('manage_roles')
                <div class="px-4 mt-4 mb-2">
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Administración</p>
                </div>
                <a href="{{ route('company-tax-settings.edit') }}" @click="sidebarOpen = false" class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-700 hover:text-white transition-colors {{ request()->routeIs('company-tax-settings.*') ? 'bg-gray-700 text-white' : '' }}">
                    <i class="fas fa-building w-5"></i>
                    <span class="ml-3">Configuración Fiscal</span>
                </a>
                @endcan
            </nav>

            <nav class="px-4 pt-4 border-t border-gray-700">
                <div class="px-4 mb-4">
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Configuración</p>
                </div>
                
                @can('manage_roles')
                <a href="{{ route('company-tax-settings.edit') }}" @click="sidebarOpen = false" class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-700 hover:text-white transition-colors {{ request()->routeIs('company-tax-settings.*') ? 'bg-gray-700 text-white' : '' }}">
                    <i class="fas fa-building w-5"></i>
                    <span class="ml-3">Datos Fiscales</span>
                </a>
                @endcan
            </nav>
        </aside>
        
        <!-- Main Content -->
        <div class="flex-1 flex flex-col min-w-0">
            <!-- Header -->
            <header class="bg-white border-b border-gray-100 sticky top-0 z-30">
                <div class="flex items-center justify-between px-4 sm:px-6 py-3 lg:py-4">
                    <div class="flex items-center space-x-3 lg:space-x-0">
                        <button @click="sidebarOpen = true" class="lg:hidden text-gray-600 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-blue-500 p-2 rounded-lg">
                            <i class="fas fa-bars text-xl"></i>
                        </button>
                        <div>
                            <h1 class="text-xl sm:text-2xl font-bold text-gray-900">@yield('header', 'Dashboard')</h1>
                            @hasSection('subheader')
                                <p class="text-xs sm:text-sm text-gray-500 mt-1 hidden sm:block">@yield('subheader')</p>
                            @endif
                        </div>
                    </div>
                    
                    <div class="flex items-center space-x-2 sm:space-x-4">
                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = !open" class="flex items-center space-x-2 sm:space-x-3 px-2 sm:px-3 py-2 rounded-xl border border-gray-200 hover:border-gray-300 hover:bg-gray-50 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <div class="flex items-center justify-center w-8 h-8 sm:w-9 sm:h-9 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 text-white text-xs sm:text-sm font-semibold shadow-sm">
                                    {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                                </div>
                                <div class="hidden sm:flex flex-col items-start">
                                    <span class="text-sm font-medium text-gray-900">{{ Auth::user()->name }}</span>
                                    <span class="text-xs text-gray-500">{{ Auth::user()->roles->first()->name ?? 'Usuario' }}</span>
                                </div>
                                <i class="fas fa-chevron-down text-gray-400 text-xs transition-transform duration-200 hidden sm:block" :class="{ 'rotate-180': open }"></i>
                            </button>
                            
                            <div x-show="open"
                                 @click.away="open = false"
                                 x-transition:enter="transition ease-out duration-100"
                                 x-transition:enter-start="transform opacity-0 scale-95"
                                 x-transition:enter-end="transform opacity-100 scale-100"
                                 x-transition:leave="transition ease-in duration-75"
                                 x-transition:leave-start="transform opacity-100 scale-100"
                                 x-transition:leave-end="transform opacity-0 scale-95"
                                 class="absolute right-0 mt-2 w-56 sm:w-64 bg-white rounded-xl shadow-xl py-2 z-50 border border-gray-100">
                                <div class="px-4 py-4 border-b border-gray-100">
                                    <p class="text-sm font-semibold text-gray-900">{{ Auth::user()->name }}</p>
                                    <p class="text-xs text-gray-500 mt-1 truncate">{{ Auth::user()->email }}</p>
                                </div>
                                <div class="px-4 py-3 border-b border-gray-100">
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs font-medium text-gray-500 uppercase tracking-wider">Rol</span>
                                        <span class="px-2.5 py-1 rounded-full bg-gray-100 text-gray-700 text-xs font-semibold">
                                            {{ Auth::user()->roles->first()->name ?? 'Sin rol' }}
                                        </span>
                                    </div>
                                </div>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="w-full text-left px-4 py-2.5 text-sm text-gray-700 hover:bg-red-50 hover:text-red-600 transition-colors duration-200 flex items-center space-x-2 group">
                                        <i class="fas fa-sign-out-alt text-gray-400 group-hover:text-red-600 transition-colors"></i>
                                        <span>Cerrar sesión</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </header>
            
            <!-- Page Content -->
            <main class="flex-1 p-4 sm:p-6">
                <!-- Session Messages -->
                <div class="max-w-7xl mx-auto">
                    @if(session('success'))
                        <div x-data="{ show: true }" 
                             x-show="show" 
                             x-init="setTimeout(() => show = false, 5000)"
                             x-transition:enter="transition ease-out duration-300"
                             x-transition:enter-start="opacity-0 transform -translate-y-2"
                             x-transition:enter-end="opacity-100 transform translate-y-0"
                             x-transition:leave="transition ease-in duration-200"
                             x-transition:leave-start="opacity-100 transform translate-y-0"
                             x-transition:leave-end="opacity-0 transform -translate-y-2"
                             class="mb-6 flex items-center p-4 text-emerald-800 rounded-2xl bg-emerald-50 border border-emerald-100 shadow-sm">
                            <div class="flex-shrink-0 w-10 h-10 flex items-center justify-center rounded-xl bg-emerald-100 text-emerald-600 mr-4">
                                <i class="fas fa-check-circle text-lg"></i>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-bold">{{ session('success') }}</p>
                            </div>
                            <button @click="show = false" class="ml-auto text-emerald-400 hover:text-emerald-600 transition-colors">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    @endif
                    
                    @if(session('error'))
                        <div x-data="{ show: true }" 
                             x-show="show"
                             class="mb-6 flex items-center p-4 text-red-800 rounded-2xl bg-red-50 border border-red-100 shadow-sm">
                            <div class="flex-shrink-0 w-10 h-10 flex items-center justify-center rounded-xl bg-red-100 text-red-600 mr-4">
                                <i class="fas fa-exclamation-circle text-lg"></i>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-bold">{{ session('error') }}</p>
                            </div>
                            <button @click="show = false" class="ml-auto text-red-400 hover:text-red-600 transition-colors">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    @endif
                    
                    @if($errors->any())
                        <div x-data="{ show: true }" 
                             x-show="show"
                             class="mb-6 p-4 text-red-800 rounded-2xl bg-red-50 border border-red-100 shadow-sm">
                            <div class="flex items-center mb-3">
                                <div class="flex-shrink-0 w-10 h-10 flex items-center justify-center rounded-xl bg-red-100 text-red-600 mr-4">
                                    <i class="fas fa-exclamation-triangle text-lg"></i>
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm font-bold">Por favor corrige los siguientes errores:</p>
                                </div>
                                <button @click="show = false" class="ml-auto text-red-400 hover:text-red-600 transition-colors">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            <ul class="ml-14 list-disc list-inside text-sm space-y-1">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
                
                @yield('content')
            </main>
        </div>
    </div>
    
    @stack('scripts')
</body>
</html>
