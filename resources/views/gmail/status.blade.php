@extends('layouts.app')

@section('title', 'Gmail ühendus')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    
    <h1 class="text-2xl font-bold text-gray-900">Gmail ühendus</h1>
    
    <div class="bg-white rounded-lg shadow p-6">
        
        @if($isConnected)
        <!-- Ühendatud -->
        <div class="flex items-center mb-6">
            <div class="flex-shrink-0">
                <svg class="h-12 w-12 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="ml-4">
                <h2 class="text-lg font-medium text-gray-900">Gmail on ühendatud</h2>
                <p class="text-sm text-gray-500">E-kirjade sünkroniseerimine töötab</p>
            </div>
        </div>
        
        @if(!empty($labels))
        <div class="mb-6">
            <h3 class="text-sm font-medium text-gray-700 mb-2">Saadaolevad labelid ({{ count($labels) }})</h3>
            <div class="max-h-48 overflow-y-auto border rounded-md p-2">
                <div class="flex flex-wrap gap-2">
                    @foreach($labels as $label)
                    <span class="px-2 py-1 text-xs bg-gray-100 rounded">{{ $label['name'] }}</span>
                    @endforeach
                </div>
            </div>
        </div>
        @endif
        
        <form action="{{ route('gmail.disconnect') }}" method="POST">
            @csrf
            <button type="submit" 
                    onclick="return confirm('Kas oled kindel, et soovid Gmaili ühenduse katkestada?')"
                    class="px-4 py-2 bg-red-100 text-red-700 rounded-md hover:bg-red-200">
                Katkesta ühendus
            </button>
        </form>
        
        @else
        <!-- Pole ühendatud -->
        <div class="flex items-center mb-6">
            <div class="flex-shrink-0">
                <svg class="h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
            </div>
            <div class="ml-4">
                <h2 class="text-lg font-medium text-gray-900">Gmail pole ühendatud</h2>
                <p class="text-sm text-gray-500">Ühenda Gmail, et alustada kirjade sünkroniseerimist</p>
            </div>
        </div>
        
        <a href="{{ route('gmail.auth') }}" 
           class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
            <svg class="h-5 w-5 mr-2" fill="currentColor" viewBox="0 0 24 24">
                <path d="M24 5.457v13.909c0 .904-.732 1.636-1.636 1.636h-3.819V11.73L12 16.64l-6.545-4.91v9.273H1.636A1.636 1.636 0 0 1 0 19.366V5.457c0-2.023 2.309-3.178 3.927-1.964L5.455 4.64 12 9.548l6.545-4.91 1.528-1.145C21.69 2.28 24 3.434 24 5.457z"/>
            </svg>
            Ühenda Gmail
        </a>
        
        @endif
        
    </div>
    
    <!-- Juhised -->
    <div class="bg-blue-50 rounded-lg p-6">
        <h3 class="text-sm font-medium text-blue-800 mb-2">Seadistamise juhised</h3>
        <ol class="list-decimal list-inside text-sm text-blue-700 space-y-1">
            <li>Mine Google Cloud Console'i ja loo uus projekt (või kasuta olemasolevat)</li>
            <li>Luba Gmail API</li>
            <li>Loo OAuth 2.0 credentials</li>
            <li>Lae alla JSON fail ja salvesta see: <code class="bg-blue-100 px-1 rounded">storage/app/google/credentials.json</code></li>
            <li>Seejärel klõpsa "Ühenda Gmail"</li>
        </ol>
    </div>
    
</div>
@endsection
