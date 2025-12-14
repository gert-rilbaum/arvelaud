@extends("layouts.app")

@section("title", $firma->nimi)

@section("content")
<div class="space-y-6">
    
    <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gray-900">{{ $firma->nimi }}</h1>
        <div class="flex gap-2">
            <form action="{{ route("firma.sync", $firma) }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                    Sünkroniseeri kirjad
                </button>
            </form>
            <a href="{{ route("firma.edit", $firma) }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200">
                Muuda
            </a>
        </div>
    </div>
    
    <!-- Firma info -->
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-medium text-gray-900 mb-4">Firma andmed</h2>
        <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <dt class="text-sm font-medium text-gray-500">Nimi</dt>
                <dd class="text-gray-900">{{ $firma->nimi }}</dd>
            </div>
            @if($firma->registrikood)
            <div>
                <dt class="text-sm font-medium text-gray-500">Registrikood</dt>
                <dd class="text-gray-900">{{ $firma->registrikood }}</dd>
            </div>
            @endif
            <div>
                <dt class="text-sm font-medium text-gray-500">Gmail labelid</dt>
                <dd class="text-gray-900">
                    @if($firma->gmail_labels && count($firma->gmail_labels) > 0)
                        @foreach($firma->gmail_labels as $label)
                        <span class="inline-block px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded mr-1 mb-1">{{ $label }}</span>
                        @endforeach
                    @else
                        <span class="text-gray-400">-</span>
                    @endif
                </dd>
            </div>
            @if($firma->email)
            <div>
                <dt class="text-sm font-medium text-gray-500">Email</dt>
                <dd class="text-gray-900">{{ $firma->email }}</dd>
            </div>
            @endif
            @if($firma->telefon)
            <div>
                <dt class="text-sm font-medium text-gray-500">Telefon</dt>
                <dd class="text-gray-900">{{ $firma->telefon }}</dd>
            </div>
            @endif
            <div>
                <dt class="text-sm font-medium text-gray-500">Staatus</dt>
                <dd>
                    @if($firma->aktiivne)
                        <span class="px-2 py-1 text-xs bg-green-100 text-green-800 rounded">Aktiivne</span>
                    @else
                        <span class="px-2 py-1 text-xs bg-gray-100 text-gray-800 rounded">Mitteaktiivne</span>
                    @endif
                </dd>
            </div>
        </dl>
    </div>
    
    <!-- Statistika -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-sm text-gray-500">Kirju kokku</p>
            <p class="text-2xl font-bold text-gray-900">{{ $firma->kirjad()->count() }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-sm text-gray-500">Uusi kirju</p>
            <p class="text-2xl font-bold text-blue-600">{{ $firma->kirjad()->where("staatus", "uus")->count() }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-sm text-gray-500">Manuseid</p>
            <p class="text-2xl font-bold text-gray-900">{{ $firma->manused()->count() }}</p>
        </div>
    </div>
    
    <!-- Viimased kirjad -->
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-medium text-gray-900 mb-4">Viimased kirjad</h2>
        @if($firma->kirjad()->count() > 0)
            <ul class="divide-y divide-gray-200">
                @foreach($firma->kirjad()->latest("gmail_kuupaev")->take(10)->get() as $kiri)
                <li class="py-3">
                    <a href="{{ route("kiri.show", $kiri) }}" class="flex justify-between hover:bg-gray-50 -mx-2 px-2 py-1 rounded">
                        <div>
                            <p class="font-medium text-gray-900">{{ $kiri->teema }}</p>
                            <p class="text-sm text-gray-500">{{ $kiri->saatja_nimi ?: $kiri->saatja_email }}</p>
                        </div>
                        <div class="text-sm text-gray-500">
                            {{ $kiri->gmail_kuupaev ? $kiri->gmail_kuupaev->format("d.m.Y H:i") : "" }}
                        </div>
                    </a>
                </li>
                @endforeach
            </ul>
            <a href="{{ route("kiri.index") }}" class="mt-4 block text-sm text-blue-600 hover:text-blue-800">
                Vaata kõiki kirju →
            </a>
        @else
            <p class="text-gray-500">Kirju pole veel. Klõpsa "Sünkroniseeri kirjad" et tuua kirjad Gmailist.</p>
        @endif
    </div>
    
</div>
@endsection
