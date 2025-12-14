<?php

namespace App\Http\Controllers;

use App\Models\Firma;
use App\Services\GmailService;
use Illuminate\Http\Request;

class FirmaController extends Controller
{
    /**
     * Firmade nimekiri
     */
    public function index()
    {
        $firmad = Firma::orderBy('nimi')
            ->withCount(['kirjad', 'manused'])
            ->get();
            
        return view('firma.index', compact('firmad'));
    }
    
    /**
     * Uue firma vorm
     */
    public function create(GmailService $gmail)
    {
        $labels = [];
        if ($gmail->isAuthenticated()) {
            $labels = $gmail->getLabels();
            // Sorteeri tähestikuliselt nime järgi
            usort($labels, fn($a, $b) => strcasecmp($a['name'], $b['name']));
        }

        $usedLabels = $this->getUsedLabels();

        return view('firma.create', compact('labels', 'usedLabels'));
    }
    
    /**
     * Salvesta uus firma
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nimi' => 'required|string|max:255',
            'registrikood' => 'nullable|string|max:20',
            'gmail_labels' => 'required|array|min:1',
            'gmail_labels.*' => 'string|max:255',
            'email' => 'nullable|email|max:255',
            'telefon' => 'nullable|string|max:50',
            'aadress' => 'nullable|string|max:500',
            'merit_api_id' => 'nullable|string|max:255',
            'merit_api_key' => 'nullable|string|max:255',
        ]);

        // Kontrolli et labelid pole juba kasutuses
        $existingLabels = $this->getUsedLabels();
        $conflicts = array_intersect($validated['gmail_labels'], $existingLabels);
        if (!empty($conflicts)) {
            return back()->withInput()->withErrors([
                'gmail_labels' => 'Järgmised labelid on juba kasutuses: ' . implode(', ', $conflicts)
            ]);
        }

        $firma = Firma::create($validated);

        // Seadista see kohe aktiivseks
        session(['aktiivne_firma_id' => $firma->id]);

        return redirect()->route('firma.show', $firma)
            ->with('success', 'Firma lisatud');
    }
    
    /**
     * Firma detailvaade
     */
    public function show(Firma $firma)
    {
        $firma->loadCount(['kirjad', 'manused']);
        
        $viimasedKirjad = $firma->kirjad()
            ->latest('gmail_kuupaev')
            ->take(10)
            ->get();
            
        $viimasedTegevused = $firma->tegevused()
            ->with(['kiri', 'manus', 'user'])
            ->latest()
            ->take(20)
            ->get();
        
        return view('firma.show', compact('firma', 'viimasedKirjad', 'viimasedTegevused'));
    }
    
    /**
     * Firma muutmise vorm
     */
    public function edit(Firma $firma, GmailService $gmail)
    {
        $labels = [];
        if ($gmail->isAuthenticated()) {
            $labels = $gmail->getLabels();
            // Sorteeri tähestikuliselt nime järgi
            usort($labels, fn($a, $b) => strcasecmp($a['name'], $b['name']));
        }

        $usedLabels = $this->getUsedLabels($firma->id);

        return view('firma.edit', compact('firma', 'labels', 'usedLabels'));
    }
    
    /**
     * Uuenda firma
     */
    public function update(Request $request, Firma $firma)
    {
        $validated = $request->validate([
            'nimi' => 'required|string|max:255',
            'registrikood' => 'nullable|string|max:20',
            'gmail_labels' => 'required|array|min:1',
            'gmail_labels.*' => 'string|max:255',
            'email' => 'nullable|email|max:255',
            'telefon' => 'nullable|string|max:50',
            'aadress' => 'nullable|string|max:500',
            'merit_api_id' => 'nullable|string|max:255',
            'merit_api_key' => 'nullable|string|max:255',
            'aktiivne' => 'boolean',
        ]);

        // Kontrolli et labelid pole juba kasutuses (v.a. selle firma omad)
        $existingLabels = $this->getUsedLabels($firma->id);
        $conflicts = array_intersect($validated['gmail_labels'], $existingLabels);
        if (!empty($conflicts)) {
            return back()->withInput()->withErrors([
                'gmail_labels' => 'Järgmised labelid on juba kasutuses: ' . implode(', ', $conflicts)
            ]);
        }

        $firma->update($validated);

        return redirect()->route('firma.show', $firma)
            ->with('success', 'Firma uuendatud');
    }

    /**
     * Hangi kõik kasutuses olevad labelid
     */
    private function getUsedLabels(?int $excludeFirmaId = null): array
    {
        $query = Firma::whereNotNull('gmail_labels');
        if ($excludeFirmaId) {
            $query->where('id', '!=', $excludeFirmaId);
        }

        $labels = [];
        foreach ($query->get() as $firma) {
            $labels = array_merge($labels, $firma->gmail_labels ?? []);
        }
        return $labels;
    }
    
    /**
     * Kustuta firma
     */
    public function destroy(Firma $firma)
    {
        $firma->delete();
        
        // Kui see oli aktiivne firma, eemalda sessioonist
        if (session('aktiivne_firma_id') == $firma->id) {
            session()->forget('aktiivne_firma_id');
        }
        
        return redirect()->route('firma.index')
            ->with('success', 'Firma kustutatud');
    }
    
    /**
     * Sünkroniseeri firma kirjad Gmailist
     */
    public function sync(Firma $firma, GmailService $gmail)
    {
        if (!$gmail->isAuthenticated()) {
            return redirect()->route('gmail.auth')
                ->with('error', 'Gmail pole ühendatud');
        }
        
        $count = $gmail->syncKirjadByLabel($firma);
        
        return redirect()->back()
            ->with('success', "Sünkroniseeritud {$count} uut kirja");
    }
}
