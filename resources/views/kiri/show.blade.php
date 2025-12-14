@extends('layouts.fullscreen')

@section('title', $kiri->teema)

@section('content')
@php
    $koikManused = $vestlus->flatMap(fn($v) => $v->manused);
@endphp

<div class="h-full flex flex-col">

    <!-- Päis -->
    <div class="flex items-center py-2 px-4 bg-white border-b shadow-sm flex-shrink-0">
        <a href="{{ route('kiri.index') }}" class="text-sm text-gray-500 hover:text-gray-700 mr-4">← Tagasi</a>
        <h1 class="text-base font-medium text-gray-900 truncate flex-1">{{ $kiri->teema }}</h1>
        <div class="flex items-center gap-2">
            <!-- Staatus -->
            <form action="{{ route('kiri.staatus', $kiri) }}" method="POST">
                @csrf
                @method('PATCH')
                <select name="staatus" onchange="this.form.submit()" class="rounded border-gray-300 text-sm py-1">
                    <option value="uus" {{ $kiri->staatus == 'uus' ? 'selected' : '' }}>Uus</option>
                    <option value="loetud" {{ $kiri->staatus == 'loetud' ? 'selected' : '' }}>Loetud</option>
                    <option value="tootluses" {{ $kiri->staatus == 'tootluses' ? 'selected' : '' }}>Töötluses</option>
                    <option value="valmis" {{ $kiri->staatus == 'valmis' ? 'selected' : '' }}>Valmis</option>
                    <option value="ignoreeritud" {{ $kiri->staatus == 'ignoreeritud' ? 'selected' : '' }}>Ignoreeritud</option>
                </select>
            </form>
            <!-- Teisalda firmasse -->
            @php $teisedFirmad = \App\Models\Firma::where('id', '!=', $kiri->firma_id)->aktiivne()->orderBy('nimi')->get(); @endphp
            @if($teisedFirmad->isNotEmpty())
            <form action="{{ route('kiri.teisalda', $kiri) }}" method="POST" onsubmit="return confirm('Teisalda kiri valitud firmasse?')">
                @csrf
                <select name="firma_id" onchange="if(this.value) this.form.submit()" class="rounded border-gray-300 text-sm py-1 text-gray-500">
                    <option value="">Teisalda →</option>
                    @foreach($teisedFirmad as $f)
                    <option value="{{ $f->id }}">{{ $f->nimi }}</option>
                    @endforeach
                </select>
            </form>
            @endif
        </div>
    </div>

    @if($duplikaat)
    <!-- Duplikaadi hoiatus -->
    <div class="px-4 py-2 bg-yellow-100 border-b border-yellow-300 flex items-center gap-2 flex-shrink-0">
        <svg class="h-5 w-5 text-yellow-600" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
        </svg>
        <span class="text-sm text-yellow-800">
            <strong>Duplikaat!</strong> See manus on juba varasemas kirjas:
            <a href="{{ route('kiri.show', $duplikaat) }}" class="underline font-medium hover:text-yellow-900">
                #{{ $duplikaat->id }} - {{ Str::limit($duplikaat->teema, 40) }}
            </a>
        </span>
    </div>
    @endif

    <!-- Põhisisu -->
    <div class="flex-1 flex min-h-0">

        <!-- Põhipaneel tab'idega -->
        <div class="flex-1 flex flex-col min-h-0 bg-white">

            <!-- Tab'id -->
            <div class="flex border-b bg-gray-50 overflow-x-auto flex-shrink-0">
                <button onclick="showTab('email')" id="tab-email"
                        class="px-4 py-2 text-sm font-medium border-b-2 border-blue-500 text-blue-600 whitespace-nowrap">
                    E-mail ({{ $vestlus->count() }})
                </button>
                <button onclick="showTab('vasta')" id="tab-vasta"
                        class="px-4 py-2 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700 whitespace-nowrap">
                    Vasta
                </button>
                <button onclick="showTab('edasta')" id="tab-edasta"
                        class="px-4 py-2 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700 whitespace-nowrap">
                    Edasta
                </button>
                @foreach($koikManused as $manus)
                <button onclick="showTab('manus-{{ $manus->id }}')" id="tab-manus-{{ $manus->id }}"
                        class="px-4 py-2 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700 whitespace-nowrap flex items-center gap-1">
                    @if($manus->on_pdf)
                    <svg class="h-3 w-3 text-red-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"/></svg>
                    @endif
                    {{ Str::limit($manus->failinimi, 20) }}
                    @if($manus->on_arve)
                    <span class="px-1 bg-orange-200 text-orange-800 rounded text-xs">Arve</span>
                    @endif
                </button>
                @endforeach
            </div>

            <!-- Tab sisu -->
            <div class="flex-1 min-h-0 relative">

                <!-- E-mail tab -->
                <div id="content-email" class="absolute inset-0 overflow-y-auto">
                    @foreach($vestlus as $v)
                    <div class="border-b {{ $v->id == $kiri->id ? 'bg-blue-50' : '' }}">
                        <div class="px-4 py-2 bg-gray-50 flex justify-between text-xs">
                            <span class="font-medium text-gray-900">{{ $v->saatja_nimi ?: $v->saatja_email }}</span>
                            <span class="text-gray-500">{{ $v->gmail_kuupaev ? $v->gmail_kuupaev->format('d.m.Y H:i') : '' }}</span>
                        </div>
                        <div class="px-4 py-3 text-sm">
                            @if($v->sisu_html)
                            <div class="prose prose-sm max-w-none">{!! $v->sisu_html !!}</div>
                            @else
                            <div class="whitespace-pre-wrap text-gray-700">{{ $v->sisu_text }}</div>
                            @endif
                        </div>
                        @if($v->manused->isNotEmpty())
                        <div class="px-4 py-2 bg-gray-100 text-xs">
                            <span class="text-gray-600">Manused:</span>
                            @foreach($v->manused as $m)
                            <button onclick="showTab('manus-{{ $m->id }}')" class="ml-2 text-blue-600 hover:underline">{{ $m->failinimi }}</button>
                            @endforeach
                        </div>
                        @endif
                    </div>
                    @endforeach
                </div>

                <!-- Vasta tab -->
                <div id="content-vasta" class="absolute inset-0 flex flex-col hidden">
                    <div class="p-4 border-b bg-gray-50">
                        <div class="text-sm text-gray-600">
                            <strong>Saaja:</strong> {{ $kiri->saatja_email }}
                        </div>
                        <div class="text-sm text-gray-600">
                            <strong>Teema:</strong> Re: {{ $kiri->teema }}
                        </div>
                    </div>
                    <form action="{{ route('kiri.sendReply', $kiri) }}" method="POST" class="flex-1 flex flex-col p-4">
                        @csrf
                        <textarea name="sisu" rows="10" placeholder="Kirjuta vastus..." class="flex-1 w-full rounded border-gray-300 text-sm" required></textarea>
                        <div class="mt-3 flex justify-end">
                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Saada vastus</button>
                        </div>
                    </form>
                </div>

                <!-- Edasta tab -->
                <div id="content-edasta" class="absolute inset-0 flex flex-col hidden">
                    <form action="{{ route('kiri.sendReply', $kiri) }}" method="POST" class="flex-1 flex flex-col p-4">
                        @csrf
                        <input type="hidden" name="edasta" value="1">
                        <div class="mb-3">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Saaja e-post</label>
                            <input type="email" name="saaja" placeholder="email@example.com" class="w-full rounded border-gray-300 text-sm" required>
                        </div>
                        <div class="mb-3">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Teema</label>
                            <input type="text" name="teema" value="Fwd: {{ $kiri->teema }}" class="w-full rounded border-gray-300 text-sm">
                        </div>
                        <div class="mb-3">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Lisainfo (valikuline)</label>
                            <textarea name="sisu" rows="4" placeholder="Lisa kommentaar..." class="w-full rounded border-gray-300 text-sm"></textarea>
                        </div>
                        <div class="p-3 bg-gray-100 rounded text-xs text-gray-600 mb-3">
                            <strong>Edastatakse:</strong><br>
                            Saatja: {{ $kiri->saatja_email }}<br>
                            Kuupäev: {{ $kiri->gmail_kuupaev ? $kiri->gmail_kuupaev->format('d.m.Y H:i') : '' }}<br>
                            Teema: {{ $kiri->teema }}
                        </div>
                        <div class="flex justify-end">
                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Edasta</button>
                        </div>
                    </form>
                </div>

                <!-- Manuste tab'id -->
                @foreach($koikManused as $manus)
                <div id="content-manus-{{ $manus->id }}" class="absolute inset-0 flex flex-col hidden">
                    <!-- Manuse päis -->
                    <div class="px-4 py-2 bg-gray-50 border-b flex items-center justify-between flex-shrink-0">
                        <div class="flex items-center gap-2">
                            <span class="font-medium text-sm">{{ $manus->failinimi }}</span>
                            <span class="text-xs text-gray-400">({{ $manus->suurus_loetav }})</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <form action="{{ route('manus.tyyp', $manus) }}" method="POST" class="flex gap-1">
                                @csrf
                                @method('PATCH')
                                <button type="submit" name="on_arve" value="1"
                                        class="px-2 py-1 text-xs rounded {{ $manus->on_arve ? 'bg-orange-500 text-white' : 'bg-gray-200 text-gray-600 hover:bg-orange-100' }}">
                                    Arve
                                </button>
                                <button type="submit" name="on_arve" value="0"
                                        class="px-2 py-1 text-xs rounded {{ !$manus->on_arve ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-600 hover:bg-blue-100' }}">
                                    Muu
                                </button>
                            </form>
                            @if($manus->on_arve)
                            <button onclick="parseInvoice({{ $manus->id }})" class="px-2 py-1 text-xs rounded bg-green-600 text-white hover:bg-green-700">
                                Tuvasta arve
                            </button>
                            @endif
                            <a href="{{ route('manus.show', $manus) }}" target="_blank" class="text-xs text-blue-600 hover:underline">Ava uues aknas</a>
                        </div>
                    </div>
                    <!-- PDF/fail ja arve vorm -->
                    <div class="flex-1 min-h-0 flex">
                        <!-- PDF vaade -->
                        <div class="flex-1 min-h-0" id="pdf-view-{{ $manus->id }}">
                            @if($manus->on_pdf)
                            <iframe src="{{ route('manus.show', $manus) }}" class="w-full h-full border-0"></iframe>
                            @else
                            <div class="flex flex-col items-center justify-center h-full text-gray-500 bg-gray-100">
                                <svg class="h-16 w-16 mb-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"/></svg>
                                <p class="text-sm">{{ $manus->mime_type }}</p>
                                <a href="{{ route('manus.show', $manus) }}" target="_blank" class="mt-2 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Laadi alla</a>
                            </div>
                            @endif
                        </div>
                        <!-- Arve andmete paneel -->
                        <div id="invoice-panel-{{ $manus->id }}" class="w-80 border-l bg-white overflow-y-auto hidden">
                            <div class="p-4">
                                <div class="flex justify-between items-center mb-4">
                                    <h3 class="font-medium text-gray-900">Arve andmed</h3>
                                    <button onclick="closeInvoicePanel({{ $manus->id }})" class="text-gray-400 hover:text-gray-600">&times;</button>
                                </div>
                                <div id="invoice-loading-{{ $manus->id }}" class="text-center py-8 text-gray-500 hidden">
                                    <div class="animate-spin h-8 w-8 border-4 border-blue-500 border-t-transparent rounded-full mx-auto mb-2"></div>
                                    Tuvastan arve andmeid...
                                </div>
                                <div id="invoice-form-{{ $manus->id }}" class="space-y-3 hidden">
                                    <div id="invoice-source-{{ $manus->id }}" class="text-xs text-gray-500 mb-2"></div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600">Arve nr</label>
                                        <input type="text" id="inv-arve_number-{{ $manus->id }}" class="w-full rounded border-gray-300 text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600">Hankija</label>
                                        <input type="text" id="inv-hankija_nimi-{{ $manus->id }}" class="w-full rounded border-gray-300 text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600">Reg. kood</label>
                                        <input type="text" id="inv-hankija_reg_nr-{{ $manus->id }}" class="w-full rounded border-gray-300 text-sm">
                                    </div>
                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <label class="block text-xs font-medium text-gray-600">Kuupäev</label>
                                            <input type="date" id="inv-arve_kuupaev-{{ $manus->id }}" class="w-full rounded border-gray-300 text-sm">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-600">Tähtaeg</label>
                                            <input type="date" id="inv-maksetahtaeg-{{ $manus->id }}" class="w-full rounded border-gray-300 text-sm">
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <label class="block text-xs font-medium text-gray-600">Summa (km-ta)</label>
                                            <input type="number" step="0.01" id="inv-summa_km_ta-{{ $manus->id }}" class="w-full rounded border-gray-300 text-sm">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-600">KM</label>
                                            <input type="number" step="0.01" id="inv-km_summa-{{ $manus->id }}" class="w-full rounded border-gray-300 text-sm">
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600">Kokku</label>
                                        <input type="number" step="0.01" id="inv-summa_kokku-{{ $manus->id }}" class="w-full rounded border-gray-300 text-sm font-medium">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600">Viitenumber</label>
                                        <input type="text" id="inv-viitenumber-{{ $manus->id }}" class="w-full rounded border-gray-300 text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600">IBAN</label>
                                        <input type="text" id="inv-iban-{{ $manus->id }}" class="w-full rounded border-gray-300 text-sm">
                                    </div>
                                    <div class="pt-3 border-t space-y-2">
                                        <button onclick="retryWithAI({{ $manus->id }})" class="w-full px-3 py-2 text-sm bg-purple-600 text-white rounded hover:bg-purple-700">
                                            Kasuta Claude AI-d
                                        </button>
                                        <button disabled class="w-full px-3 py-2 text-sm bg-gray-300 text-gray-500 rounded cursor-not-allowed">
                                            Saada Meriti (tulekul)
                                        </button>
                                    </div>
                                </div>
                                <div id="invoice-error-{{ $manus->id }}" class="text-center py-8 hidden">
                                    <p class="text-red-600 mb-3">Automaatne tuvastamine ebaõnnestus</p>
                                    <button onclick="retryWithAI({{ $manus->id }})" class="px-4 py-2 bg-purple-600 text-white rounded hover:bg-purple-700">
                                        Proovi Claude AI-ga
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach

            </div>
        </div>

        <!-- Külgmenüü -->
        <div class="w-64 bg-gray-50 border-l flex flex-col flex-shrink-0">

            <!-- Ajalugu -->
            <div class="flex-1 overflow-y-auto p-3 min-h-0">
                <h3 class="font-medium text-gray-900 text-sm mb-2">Ajalugu</h3>
                @forelse($tegevused as $tegevus)
                <div class="text-xs border-l-2 border-gray-300 pl-2 mb-2">
                    <div class="text-gray-900">{{ $tegevus->tyyp_nimi }}</div>
                    @if($tegevus->kirjeldus)
                    <div class="text-gray-600 truncate" title="{{ $tegevus->kirjeldus }}">{{ $tegevus->kirjeldus }}</div>
                    @endif
                    <div class="text-gray-400">{{ $tegevus->created_at->format('d.m H:i') }}</div>
                </div>
                @empty
                <p class="text-xs text-gray-500">Tegevusi pole</p>
                @endforelse
            </div>

            <!-- Märkus -->
            <div class="p-3 border-t bg-white flex-shrink-0">
                <h3 class="font-medium text-gray-900 text-sm mb-2">Lisa märkus</h3>
                <form action="{{ route('kiri.markus', $kiri) }}" method="POST">
                    @csrf
                    <textarea name="markus" rows="2" placeholder="Märkus..." class="w-full rounded border-gray-300 text-xs" required></textarea>
                    <button type="submit" class="mt-1 w-full px-2 py-1 bg-gray-200 text-gray-700 rounded text-xs hover:bg-gray-300">Lisa</button>
                </form>
            </div>
        </div>

    </div>
</div>

<script>
function showTab(tabId) {
    document.querySelectorAll('[id^="content-"]').forEach(el => el.classList.add('hidden'));
    document.querySelectorAll('[id^="tab-"]').forEach(el => {
        el.classList.remove('border-blue-500', 'text-blue-600');
        el.classList.add('border-transparent', 'text-gray-500');
    });
    document.getElementById('content-' + tabId).classList.remove('hidden');
    const tab = document.getElementById('tab-' + tabId);
    tab.classList.add('border-blue-500', 'text-blue-600');
    tab.classList.remove('border-transparent', 'text-gray-500');
}

// Arve tuvastamine
async function parseInvoice(manusId) {
    const panel = document.getElementById('invoice-panel-' + manusId);
    const loading = document.getElementById('invoice-loading-' + manusId);
    const form = document.getElementById('invoice-form-' + manusId);
    const error = document.getElementById('invoice-error-' + manusId);

    // Näita paneel ja loading
    panel.classList.remove('hidden');
    loading.classList.remove('hidden');
    form.classList.add('hidden');
    error.classList.add('hidden');

    try {
        const response = await fetch('/manus/' + manusId + '/parse');
        const result = await response.json();

        loading.classList.add('hidden');

        if (result.success && result.data) {
            fillInvoiceForm(manusId, result.data);
            form.classList.remove('hidden');
        } else {
            error.classList.remove('hidden');
        }
    } catch (e) {
        loading.classList.add('hidden');
        error.classList.remove('hidden');
    }
}

// Claude AI-ga proovi
async function retryWithAI(manusId) {
    const panel = document.getElementById('invoice-panel-' + manusId);
    const loading = document.getElementById('invoice-loading-' + manusId);
    const form = document.getElementById('invoice-form-' + manusId);
    const error = document.getElementById('invoice-error-' + manusId);

    panel.classList.remove('hidden');
    loading.classList.remove('hidden');
    form.classList.add('hidden');
    error.classList.add('hidden');

    try {
        const response = await fetch('/manus/' + manusId + '/parse', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });
        const result = await response.json();

        loading.classList.add('hidden');

        if (result.success && result.data) {
            fillInvoiceForm(manusId, result.data);
            form.classList.remove('hidden');
        } else {
            error.classList.remove('hidden');
            error.innerHTML = '<p class="text-red-600">AI tuvastamine ebaõnnestus</p>';
        }
    } catch (e) {
        loading.classList.add('hidden');
        error.classList.remove('hidden');
    }
}

// Täida vorm andmetega
function fillInvoiceForm(manusId, data) {
    const fields = ['arve_number', 'hankija_nimi', 'hankija_reg_nr', 'arve_kuupaev',
                    'maksetahtaeg', 'summa_km_ta', 'km_summa', 'summa_kokku', 'viitenumber', 'iban'];

    fields.forEach(field => {
        const input = document.getElementById('inv-' + field + '-' + manusId);
        if (input && data[field] !== null && data[field] !== undefined) {
            input.value = data[field];
        }
    });

    // Näita allikat
    const sourceLabels = { 'xml': 'XML e-arve', 'ocr': 'PDF tekst', 'claude': 'Claude AI' };
    const sourceEl = document.getElementById('invoice-source-' + manusId);
    if (sourceEl && data.allikas) {
        sourceEl.textContent = 'Allikas: ' + (sourceLabels[data.allikas] || data.allikas);
    }
}

// Sulge paneel
function closeInvoicePanel(manusId) {
    document.getElementById('invoice-panel-' + manusId).classList.add('hidden');
}
</script>
@endsection
