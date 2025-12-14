@extends('layouts.app')

@section('title', 'Lisa firma')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    
    <div>
        <a href="{{ route('firma.index') }}" class="text-sm text-gray-500 hover:text-gray-700">
            ← Tagasi firmade nimekirja
        </a>
    </div>
    
    <h1 class="text-2xl font-bold text-gray-900">Lisa uus firma</h1>
    
    <form action="{{ route('firma.store') }}" method="POST" class="bg-white rounded-lg shadow p-6 space-y-6">
        @csrf
        
        @if($errors->any())
        <div class="bg-red-50 border border-red-200 rounded-md p-4">
            <ul class="list-disc list-inside text-sm text-red-600">
                @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif
        
        <!-- Põhiandmed -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Firma nimi *</label>
                <input type="text" 
                       name="nimi" 
                       value="{{ old('nimi') }}"
                       required
                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Registrikood</label>
                <input type="text" 
                       name="registrikood" 
                       value="{{ old('registrikood') }}"
                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
        </div>
        
        <!-- Gmail labelid -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Gmail labelid *</label>
            @if(!empty($labels))
            <div class="max-h-48 overflow-y-auto border border-gray-300 rounded-md p-2 bg-white">
                @foreach($labels as $label)
                @php $isUsed = in_array($label['name'], $usedLabels ?? []); @endphp
                <label class="flex items-center py-1 {{ $isUsed ? 'opacity-50' : 'hover:bg-gray-50' }}">
                    <input type="checkbox"
                           name="gmail_labels[]"
                           value="{{ $label['name'] }}"
                           {{ $isUsed ? 'disabled' : '' }}
                           {{ in_array($label['name'], old('gmail_labels', [])) ? 'checked' : '' }}
                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    <span class="ml-2 text-sm {{ $isUsed ? 'text-gray-400' : 'text-gray-700' }}">
                        {{ $label['name'] }}
                        @if($isUsed)<span class="text-xs text-red-500">(kasutuses)</span>@endif
                    </span>
                </label>
                @endforeach
            </div>
            <p class="mt-1 text-xs text-gray-500">Vali üks või mitu labelit</p>
            @else
            <input type="text"
                   name="gmail_labels[]"
                   value="{{ old('gmail_labels.0') }}"
                   placeholder="nt: inbox-kodukinnisvara"
                   required
                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            <p class="mt-1 text-sm text-gray-500">
                <a href="{{ route('gmail.auth') }}" class="text-blue-600 hover:text-blue-800">Ühenda Gmail</a>, et näha labelite nimekirja
            </p>
            @endif
        </div>
        
        <!-- Kontaktandmed -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">E-post</label>
                <input type="email" 
                       name="email" 
                       value="{{ old('email') }}"
                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Telefon</label>
                <input type="text" 
                       name="telefon" 
                       value="{{ old('telefon') }}"
                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
        </div>
        
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Aadress</label>
            <textarea name="aadress" 
                      rows="2"
                      class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('aadress') }}</textarea>
        </div>
        
        <!-- Merit API (valikuline) -->
        <div class="border-t pt-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Merit Aktiva (valikuline)</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">API ID</label>
                    <input type="text" 
                           name="merit_api_id" 
                           value="{{ old('merit_api_id') }}"
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">API võti</label>
                    <input type="text" 
                           name="merit_api_key" 
                           value="{{ old('merit_api_key') }}"
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
            </div>
        </div>
        
        <div class="flex justify-end space-x-4 pt-4 border-t">
            <a href="{{ route('firma.index') }}" class="px-4 py-2 text-gray-700 hover:text-gray-900">
                Tühista
            </a>
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                Lisa firma
            </button>
        </div>
    </form>
    
</div>
@endsection
