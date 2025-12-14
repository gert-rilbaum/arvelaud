@extends('layouts.app')

@section('title', 'Ülevaade')

@section('content')
<div class="space-y-6">
    
    <h1 class="text-2xl font-bold text-gray-900">Ülevaade</h1>
    
    @if(!$firma)
    <!-- Kui firma pole valitud -->
    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6">
        <h2 class="text-lg font-medium text-yellow-800 mb-2">Vali firma</h2>
        <p class="text-yellow-700 mb-4">
            Alustamiseks vali ülevalt rippmenüüst firma, kelle kirju soovid hallata.
        </p>
        
        @if($firmad->isEmpty())
        <a href="{{ route('firma.create') }}" 
           class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
            + Lisa esimene firma
        </a>
        @endif
    </div>
    
    @else
    <!-- Statistika kaardid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        
        <!-- Uued kirjad -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-blue-100 rounded-md p-3">
                    <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Uued kirjad</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $stats['uusi_kirju'] }}</p>
                </div>
            </div>
            <a href="{{ route('kiri.index', ['staatus' => 'uus']) }}" class="mt-4 block text-sm text-blue-600 hover:text-blue-800">
                Vaata kõiki →
            </a>
        </div>
        
        <!-- Töötlemata arved -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-orange-100 rounded-md p-3">
                    <svg class="h-6 w-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Töötlemata arved</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $stats['tootlemata_arveid'] }}</p>
                </div>
            </div>
            <a href="{{ route('kiri.index', ['manustega' => 1]) }}" class="mt-4 block text-sm text-orange-600 hover:text-orange-800">
                Vaata arveid →
            </a>
        </div>
        
        <!-- Kirju kokku -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-gray-100 rounded-md p-3">
                    <svg class="h-6 w-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Kirju kokku</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $stats['kirju_kokku'] }}</p>
                </div>
            </div>
            <a href="{{ route('kiri.index') }}" class="mt-4 block text-sm text-gray-600 hover:text-gray-800">
                Kõik kirjad →
            </a>
        </div>
        
        <!-- Sisestatud arved -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-green-100 rounded-md p-3">
                    <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Aktivasse sisestatud</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $stats['arveid_sisestatud'] }}</p>
                </div>
            </div>
        </div>
        
    </div>
    
    <!-- Kiirtoimingud -->
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-medium text-gray-900 mb-4">Kiirtoimingud</h2>
        <div class="flex flex-wrap gap-3">
            <form action="{{ route('firma.sync', $firma) }}" method="POST" class="inline">
                @csrf
                <button type="submit" 
                        class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                    <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Sünkroniseeri kirjad
                </button>
            </form>
            
            <a href="{{ route('firma.show', $firma) }}" 
               class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200">
                Firma seaded
            </a>
        </div>
    </div>
    
    @endif
    
</div>
@endsection
