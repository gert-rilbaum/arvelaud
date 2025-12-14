@extends('layouts.app')

@section('title', 'Postkast - ' . $firma->nimi)

@section('content')
<div class="space-y-6">
    
    <!-- Päis -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Postkast</h1>
            <p class="text-sm text-gray-500">{{ $firma->nimi }}</p>
        </div>
        
        <form action="{{ route('firma.sync', $firma) }}" method="POST">
            @csrf
            <button type="submit" 
                    class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                Sünkroniseeri
            </button>
        </form>
    </div>
    
    <!-- Kiirfiltrid -->
    <div class="flex gap-2">
        <a href="{{ route('kiri.index') }}"
           class="px-4 py-2 rounded-md text-sm font-medium {{ !request('staatus') || request('staatus') == 'aktiivsed' ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50' }}">
            Aktiivsed
        </a>
        <a href="{{ route('kiri.index', ['staatus' => 'uus']) }}"
           class="px-4 py-2 rounded-md text-sm font-medium {{ request('staatus') == 'uus' ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50' }}">
            Uued
        </a>
        <a href="{{ route('kiri.index', ['staatus' => 'valmis']) }}"
           class="px-4 py-2 rounded-md text-sm font-medium {{ request('staatus') == 'valmis' ? 'bg-green-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50' }}">
            Valmis
        </a>
        <a href="{{ route('kiri.index', ['staatus' => 'ignoreeritud']) }}"
           class="px-4 py-2 rounded-md text-sm font-medium {{ request('staatus') == 'ignoreeritud' ? 'bg-gray-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50' }}">
            Ignoreeritud
        </a>
        <a href="{{ route('kiri.index', ['staatus' => 'koik']) }}"
           class="px-4 py-2 rounded-md text-sm font-medium {{ request('staatus') == 'koik' ? 'bg-gray-800 text-white' : 'bg-white text-gray-700 hover:bg-gray-50' }}">
            Kõik
        </a>
    </div>

    <!-- Filtrid -->
    <div class="bg-white rounded-lg shadow p-4">
        <form action="{{ route('kiri.index') }}" method="GET" class="flex flex-wrap gap-4 items-end">

            <!-- Otsing -->
            <div class="flex-1 min-w-64">
                <label class="block text-sm font-medium text-gray-700 mb-1">Otsi</label>
                <input type="text"
                       name="otsing"
                       value="{{ request('otsing') }}"
                       placeholder="Teema, saatja, sisu..."
                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>

            <!-- Suund -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Suund</label>
                <select name="suund" class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Kõik</option>
                    <option value="sisse" {{ request('suund') == 'sisse' ? 'selected' : '' }}>Sissetulevad</option>
                    <option value="valja" {{ request('suund') == 'valja' ? 'selected' : '' }}>Väljaminevad</option>
                </select>
            </div>

            <!-- Manustega -->
            <div class="flex items-center">
                <label class="flex items-center">
                    <input type="checkbox"
                           name="manustega"
                           value="1"
                           {{ request('manustega') ? 'checked' : '' }}
                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    <span class="ml-2 text-sm text-gray-700">Ainult manustega</span>
                </label>
            </div>

            <div class="flex gap-2">
                <button type="submit" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200">
                    Filtreeri
                </button>
                <a href="{{ route('kiri.index') }}" class="px-4 py-2 text-gray-500 hover:text-gray-700">
                    Tühjenda
                </a>
            </div>
        </form>
    </div>
    
    <!-- Kirjad -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        @if($kirjad->isEmpty())
        <div class="p-8 text-center text-gray-500">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
            </svg>
            <p class="mt-2">Kirju ei leitud</p>
        </div>
        @else
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Saatja</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Teema</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kuupäev</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Staatus</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"></th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach($kirjad as $kiri)
                <tr class="{{ $kiri->staatus == 'uus' ? 'bg-blue-50' : '' }} hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            @if($kiri->suund == 'valja')
                            <span class="text-gray-400 mr-2">→</span>
                            @endif
                            <div>
                                <div class="text-sm font-medium text-gray-900 {{ $kiri->staatus == 'uus' ? 'font-bold' : '' }}">
                                    {{ $kiri->saatja_nimi ?: $kiri->saatja_email }}
                                </div>
                                @if($kiri->saatja_nimi)
                                <div class="text-xs text-gray-500">{{ $kiri->saatja_email }}</div>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center">
                            <a href="{{ route('kiri.show', $kiri) }}" 
                               class="text-sm text-gray-900 hover:text-blue-600 {{ $kiri->staatus == 'uus' ? 'font-bold' : '' }}">
                                {{ Str::limit($kiri->teema, 60) }}
                            </a>
                            @if($kiri->on_manuseid)
                            <svg class="ml-2 h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                            </svg>
                            @endif
                        </div>
                        <div class="text-xs text-gray-500 mt-1">{{ Str::limit($kiri->sisu_eelvaade, 80) }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ $kiri->gmail_kuupaev->format('d.m.Y H:i') }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @php
                        $staatusColors = [
                            'uus' => 'bg-blue-100 text-blue-800',
                            'loetud' => 'bg-gray-100 text-gray-800',
                            'tootluses' => 'bg-yellow-100 text-yellow-800',
                            'valmis' => 'bg-green-100 text-green-800',
                            'ignoreeritud' => 'bg-gray-100 text-gray-400',
                        ];
                        @endphp
                        <span class="px-2 py-1 text-xs rounded-full {{ $staatusColors[$kiri->staatus] ?? 'bg-gray-100' }}">
                            {{ ucfirst($kiri->staatus) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-3">
                        @if($kiri->staatus !== 'ignoreeritud')
                        <form action="{{ route('kiri.staatus', $kiri) }}" method="POST" class="inline">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="staatus" value="ignoreeritud">
                            <button type="submit" class="text-gray-400 hover:text-gray-600" title="Ignoreeri">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                                </svg>
                            </button>
                        </form>
                        @endif
                        <a href="{{ route('kiri.show', $kiri) }}" class="text-blue-600 hover:text-blue-900">
                            Ava
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        
        <!-- Pagination -->
        <div class="px-6 py-4 border-t">
            {{ $kirjad->withQueryString()->links() }}
        </div>
        @endif
    </div>
    
</div>
@endsection
