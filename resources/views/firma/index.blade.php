@extends('layouts.app')

@section('title', 'Firmad')

@section('content')
<div class="space-y-6">
    
    <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gray-900">Firmad</h1>
        <a href="{{ route('firma.create') }}" 
           class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
            + Lisa firma
        </a>
    </div>
    
    <div class="bg-white rounded-lg shadow overflow-hidden">
        @if($firmad->isEmpty())
        <div class="p-8 text-center text-gray-500">
            <p>Firmasid pole veel lisatud</p>
            <a href="{{ route('firma.create') }}" class="mt-2 text-blue-600 hover:text-blue-800">
                Lisa esimene firma
            </a>
        </div>
        @else
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nimi</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Gmail label</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kirju</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Staatus</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @foreach($firmad as $firma)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4">
                        <div class="font-medium text-gray-900">{{ $firma->nimi }}</div>
                        @if($firma->registrikood)
                        <div class="text-sm text-gray-500">{{ $firma->registrikood }}</div>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500">
                        {{ $firma->gmail_label }}
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500">
                        {{ $firma->kirjad_count }}
                    </td>
                    <td class="px-6 py-4">
                        @if($firma->aktiivne)
                        <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-700">Aktiivne</span>
                        @else
                        <span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-500">Mitteaktiivne</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-right text-sm">
                        <a href="{{ route('firma.show', $firma) }}" class="text-blue-600 hover:text-blue-800">Vaata</a>
                        <a href="{{ route('firma.edit', $firma) }}" class="ml-4 text-gray-600 hover:text-gray-800">Muuda</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>
    
</div>
@endsection
