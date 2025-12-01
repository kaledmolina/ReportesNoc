<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Backoffice Intalnet</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=outfit:300,400,500,600,700" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background: #0f172a;
            overflow-x: hidden;
        }

        /* Liquid Background Animation */
        .liquid-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            overflow: hidden;
        }

        .blob {
            position: absolute;
            filter: blur(80px);
            opacity: 0.6;
            animation: float 10s infinite ease-in-out alternate;
        }

        .blob-1 {
            top: -10%;
            left: -10%;
            width: 50vw;
            height: 50vw;
            background: linear-gradient(135deg, #4f46e5, #818cf8);
            border-radius: 40% 60% 70% 30% / 40% 50% 60% 50%;
            animation-duration: 15s;
        }

        .blob-2 {
            bottom: -10%;
            right: -10%;
            width: 60vw;
            height: 60vw;
            background: linear-gradient(135deg, #ec4899, #c084fc);
            border-radius: 60% 40% 30% 70% / 60% 30% 70% 40%;
            animation-duration: 18s;
        }

        .blob-3 {
            top: 40%;
            left: 40%;
            width: 40vw;
            height: 40vw;
            background: linear-gradient(135deg, #06b6d4, #3b82f6);
            border-radius: 50%;
            animation-duration: 20s;
            opacity: 0.4;
        }

        @keyframes float {
            0% { transform: translate(0, 0) rotate(0deg); }
            100% { transform: translate(50px, 50px) rotate(20deg); }
        }

        /* Glassmorphism Card */
        .glass-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
        }

        /* Animated Feature Cards */
        .feature-card {
            transition: all 0.3s ease;
        }
        .feature-card:hover {
            transform: translateY(-10px);
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.3);
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        }
    </style>
</head>
<body class="antialiased text-white min-h-screen flex flex-col items-center justify-center relative">

    <!-- Background Blobs -->
    <div class="liquid-bg">
        <div class="blob blob-1"></div>
        <div class="blob blob-2"></div>
        <div class="blob blob-3"></div>
    </div>

    <!-- Main Container -->
    <main class="w-full max-w-6xl px-6 py-12 z-10">
        
        <!-- Hero Section -->
        <div class="text-center mb-16 animate-fade-in-up">
            <!-- Logo -->
            <div class="flex justify-center mb-8">
                <img src="{{ asset('images/logo.png') }}" alt="Intalnet Logo" class="h-24 w-auto drop-shadow-2xl hover:scale-105 transition-transform duration-300">
            </div>

            <div class="inline-flex items-center justify-center p-2 mb-6 rounded-full glass-card">
                <span class="px-3 py-1 text-xs font-bold tracking-wider uppercase bg-white/10 rounded-full text-cyan-300">Guía Rápida</span>
                <span class="ml-3 text-sm font-medium text-gray-300">Aprende a usar la plataforma</span>
            </div>
            
            <h1 class="text-5xl md:text-7xl font-bold tracking-tight mb-6 bg-clip-text text-transparent bg-gradient-to-r from-white via-gray-200 to-gray-400 drop-shadow-lg">
                Backoffice <span class="text-indigo-400">Intalnet</span>
            </h1>
            
            <p class="text-lg md:text-xl text-gray-300 max-w-2xl mx-auto leading-relaxed">
                Sigue estos simples pasos para gestionar tus reportes y monitorear la red de manera eficiente.
            </p>

            <div class="mt-10 flex justify-center gap-4">
                    <a href="/admin" class="group relative px-8 py-4 font-bold text-white transition-all duration-300 bg-indigo-600 rounded-full hover:bg-indigo-500 hover:shadow-[0_0_20px_rgba(99,102,241,0.5)] overflow-hidden">
                            <span class="relative z-10 flex items-center gap-2">
                                Ingresar al Sistema
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 group-hover:translate-x-1 transition-transform">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                            </svg>
                        </span>
                    </a>
            </div>
        </div>

        <!-- Usage Guide Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            
            <!-- Step 1: Login -->
            <div class="glass-card p-8 rounded-2xl feature-card group relative overflow-hidden">
                <div class="absolute -right-4 -top-4 text-9xl font-bold text-white/5 z-0">1</div>
                <div class="relative z-10">
                    <div class="w-14 h-14 rounded-xl bg-indigo-500/20 flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8 text-indigo-400">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold mb-3 text-white">Inicia Sesión</h3>
                    <p class="text-gray-400 text-sm leading-relaxed mb-4">
                        Ingresa con tus credenciales asignadas. Si no tienes acceso, contacta al administrador del sistema para crear tu cuenta.
                    </p>
                    @if (Route::has('login'))
                        @auth
                            <a href="{{ url('/dashboard') }}" class="inline-flex items-center text-indigo-400 hover:text-indigo-300 font-medium transition-colors">
                                Ir al Dashboard <span aria-hidden="true" class="ml-1">&rarr;</span>
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium rounded-lg transition-colors shadow-lg shadow-indigo-500/30">
                                Iniciar Sesión
                            </a>
                        @endauth
                    @endif
                </div>
            </div>

            <!-- Step 2: Manage Tickets -->
            <div class="glass-card p-8 rounded-2xl feature-card group relative overflow-hidden">
                <div class="absolute -right-4 -top-4 text-9xl font-bold text-white/5 z-0">2</div>
                <div class="relative z-10">
                    <div class="w-14 h-14 rounded-xl bg-pink-500/20 flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8 text-pink-400">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold mb-3 text-white">Gestiona Tickets</h3>
                    <p class="text-gray-400 text-sm leading-relaxed mb-4">
                        Revisa tus tickets asignados. <strong>Acepta</strong> el caso, inicia el proceso con <strong>Atender</strong>, o <strong>Escala</strong> si es necesario.
                    </p>
                </div>
            </div>

            <!-- Step 3: Resolve & Report -->
            <div class="glass-card p-8 rounded-2xl feature-card group relative overflow-hidden">
                <div class="absolute -right-4 -top-4 text-9xl font-bold text-white/5 z-0">3</div>
                <div class="relative z-10">
                    <div class="w-14 h-14 rounded-xl bg-cyan-500/20 flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8 text-cyan-400">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.125 2.25h-4.5c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125v-9M10.125 2.25h.375a9 9 0 019 9v.375M10.125 2.25A3.375 3.375 0 0113.5 5.625v1.5c0 .621.504 1.125 1.125 1.125h1.5a3.375 3.375 0 013.375 3.375M9 15l2.25 2.25L15 12" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold mb-3 text-white">Resuelve y Reporta</h3>
                    <p class="text-gray-400 text-sm leading-relaxed mb-4">
                        Sube evidencias fotográficas y notas de solución para cerrar el ticket. El sistema generará el reporte automáticamente.
                    </p>
                </div>
            </div>

        </div>

        <!-- Footer -->
        <footer class="mt-20 text-center text-gray-500 text-sm">
            <p>&copy; {{ date('Y') }} Intalnet S.A.S. Todos los derechos reservados.</p>
            <div class="mt-2 space-x-4">
                <a href="#" class="hover:text-white transition-colors">Soporte</a>
                <a href="#" class="hover:text-white transition-colors">Privacidad</a>
                <a href="#" class="hover:text-white transition-colors">Términos</a>
            </div>
        </footer>

    </main>

</body>
</html>
