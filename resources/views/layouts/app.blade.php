<!DOCTYPE html>
<html lang="et">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'ArveLaud') - Raamatupidaja töölaud</title>
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <style>
        [x-cloak] { display: none !important; }
        /* Form element styling */
        input[type="text"], input[type="email"], input[type="password"], input[type="number"],
        textarea, select {
            border: 1px solid #d1d5db !important;
            border-radius: 0.375rem;
            padding: 0.5rem 0.75rem;
            background-color: #fff;
        }
        input:focus, textarea:focus, select:focus {
            border-color: #3b82f6 !important;
            outline: none;
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">
    
    <!-- Navigatsioon -->
    <nav class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <!-- Logo ja navigatsioon -->
                <div class="flex items-center space-x-8">
                    <a href="{{ route('dashboard') }}" class="text-xl font-bold text-blue-600">
                        ArveLaud
                    </a>
                    
                    @auth
                    @php
                        $navFirmaId = session('aktiivne_firma_id');
                        $navStats = null;
                        if ($navFirmaId) {
                            // Ainult aktiivsete kirjade (mitte ignoreeritud/valmis) manused
                            $aktiivsetKirjadIds = \App\Models\Kiri::where('firma_id', $navFirmaId)
                                ->whereIn('staatus', ['uus', 'loetud', 'tootluses'])
                                ->pluck('id');
                            $navStats = [
                                'uusi' => \App\Models\Kiri::where('firma_id', $navFirmaId)->where('staatus', 'uus')->count(),
                                'arveid' => \App\Models\Manus::where('firma_id', $navFirmaId)
                                    ->where('on_arve', true)
                                    ->whereIn('kiri_id', $aktiivsetKirjadIds)
                                    ->count(),
                            ];
                        }
                    @endphp
                    <div class="hidden md:flex space-x-4">
                        <a href="{{ route('dashboard') }}"
                           class="px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('dashboard') ? 'bg-blue-100 text-blue-700' : 'text-gray-600 hover:text-gray-900' }}">
                            Ülevaade
                        </a>
                        <a href="{{ route('kiri.index') }}"
                           class="px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('kiri.*') ? 'bg-blue-100 text-blue-700' : 'text-gray-600 hover:text-gray-900' }} flex items-center gap-1">
                            Postkast
                            @if($navStats && $navStats['uusi'] > 0)
                            <span class="bg-blue-600 text-white text-xs px-1.5 py-0.5 rounded-full">{{ $navStats['uusi'] }}</span>
                            @endif
                        </a>
                        <a href="{{ route('kiri.index', ['manustega' => 1]) }}"
                           class="px-3 py-2 rounded-md text-sm font-medium {{ request('manustega') ? 'bg-orange-100 text-orange-700' : 'text-gray-600 hover:text-gray-900' }} flex items-center gap-1">
                            Arved
                            @if($navStats && $navStats['arveid'] > 0)
                            <span class="bg-orange-500 text-white text-xs px-1.5 py-0.5 rounded-full">{{ $navStats['arveid'] }}</span>
                            @endif
                        </a>
                        <a href="{{ route('firma.index') }}"
                           class="px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('firma.*') ? 'bg-blue-100 text-blue-700' : 'text-gray-600 hover:text-gray-900' }}">
                            Firmad
                        </a>
                        <a href="{{ route('gmail.status') }}"
                           class="px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('gmail.*') ? 'bg-blue-100 text-blue-700' : 'text-gray-600 hover:text-gray-900' }}">
                            Gmail
                        </a>
                    </div>
                    @endauth
                </div>
                
                <!-- Firma valik ja kasutaja -->
                <div class="flex items-center space-x-4">
                    @auth
                    @php $firmad = \App\Models\Firma::aktiivne()->orderBy('nimi')->get(); @endphp
                    <!-- Firma valik -->
                    @if($firmad->count() > 0)
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-gray-500">Firma:</span>
                        <form action="{{ route('vaheta-firma') }}" method="POST">
                            @csrf
                            <select name="firma_id"
                                    onchange="this.form.submit()"
                                    class="block w-48 rounded-md border-gray-300 shadow-sm text-sm font-medium {{ session('aktiivne_firma_id') ? 'bg-blue-50 border-blue-300' : 'bg-yellow-50 border-yellow-300' }}">
                                @if(!session('aktiivne_firma_id'))
                                <option value="">-- Vali firma --</option>
                                @endif
                                @foreach($firmad as $f)
                                <option value="{{ $f->id }}" {{ (int)session('aktiivne_firma_id') === $f->id ? 'selected' : '' }}>
                                    {{ $f->nimi }}
                                    @if($f->uusi_kirju > 0)
                                    ({{ $f->uusi_kirju }})
                                    @endif
                                </option>
                                @endforeach
                            </select>
                        </form>
                    </div>
                    @endif
                    <a href="{{ route('firma.create') }}" class="text-sm text-blue-600 hover:text-blue-800">+ Lisa firma</a>

                    <!-- Kasutaja -->
                    <div class="flex items-center space-x-2 border-l pl-4">
                        <span class="text-sm text-gray-600">{{ auth()->user()->name }}</span>
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="text-sm text-gray-500 hover:text-gray-700">
                                Logi välja
                            </button>
                        </form>
                    </div>
                    @endauth
                </div>
            </div>
        </div>
    </nav>
    
    <!-- Flash teated -->
    @if(session('success') || session('error') || session('info'))
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
        @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
            {{ session('success') }}
        </div>
        @endif
        
        @if(session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
            {{ session('error') }}
        </div>
        @endif
        
        @if(session('info'))
        <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded relative" role="alert">
            {{ session('info') }}
        </div>
        @endif
    </div>
    @endif
    
    <!-- Sisu -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        @yield('content')
    </main>
    
    <!-- Footer -->
    <footer class="bg-white border-t mt-auto">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <p class="text-center text-sm text-gray-500">
                ArveLaud &copy; {{ date('Y') }} RILBAUM-IT OÜ
            </p>
        </div>
    </footer>
    
    @stack('scripts')
</body>
</html>
