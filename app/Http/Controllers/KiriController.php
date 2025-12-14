<?php

namespace App\Http\Controllers;

use App\Models\Firma;
use App\Models\Kiri;
use App\Models\Manus;
use App\Models\Tegevus;
use App\Services\GmailService;
use App\Services\InvoiceParserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class KiriController extends Controller
{
    /**
     * Kirjade nimekiri (aktiivse firma kontekstis)
     */
    public function index(Request $request)
    {
        $firmaId = session('aktiivne_firma_id');
        if (!$firmaId) {
            return redirect()->route('dashboard')
                ->with('error', 'Palun vali kõigepealt firma');
        }
        
        $firma = Firma::findOrFail($firmaId);
        
        $query = Kiri::where('firma_id', $firma->id)
            ->with(['manused']);
        
        // Filtrid
        if ($request->filled('staatus')) {
            if ($request->staatus === 'aktiivsed') {
                $query->whereIn('staatus', ['uus', 'loetud', 'tootluses']);
            } elseif ($request->staatus === 'koik') {
                // Näita kõiki, filtrit ei lisa
            } else {
                $query->where('staatus', $request->staatus);
            }
        } else {
            // Vaikimisi näita ainult aktiivseid (peida valmis ja ignoreeritud)
            $query->whereIn('staatus', ['uus', 'loetud', 'tootluses']);
        }
        
        if ($request->filled('suund')) {
            $query->where('suund', $request->suund);
        }
        
        if ($request->filled('manustega')) {
            $query->where('on_manuseid', true);
        }
        
        if ($request->filled('otsing')) {
            $otsing = $request->otsing;
            $query->where(function($q) use ($otsing) {
                $q->where('teema', 'like', "%{$otsing}%")
                  ->orWhere('saatja_email', 'like', "%{$otsing}%")
                  ->orWhere('saatja_nimi', 'like', "%{$otsing}%")
                  ->orWhere('sisu_text', 'like', "%{$otsing}%");
            });
        }
        
        $kirjad = $query->latest('gmail_kuupaev')->paginate(25);
        
        return view('kiri.index', compact('firma', 'kirjad'));
    }
    
    /**
     * Kirja vaade (koos vestlusega)
     */
    public function show(Kiri $kiri)
    {
        $this->authorize($kiri);

        // Märgi loetuks
        if ($kiri->staatus === 'uus') {
            $kiri->update(['staatus' => 'loetud']);
            Tegevus::lisa($kiri->firma_id, 'kiri_loetud', null, $kiri->id);
        }

        // Lae sama vestluse kirjad
        $vestlus = Kiri::where('gmail_thread_id', $kiri->gmail_thread_id)
            ->where('firma_id', $kiri->firma_id)
            ->with(['manused'])
            ->orderBy('gmail_kuupaev', 'asc')
            ->get();

        // Tegevuste ajalugu
        $tegevused = Tegevus::where('kiri_id', $kiri->id)
            ->orWhereIn('manus_id', $kiri->manused->pluck('id'))
            ->with(['user'])
            ->latest()
            ->get();

        // Duplikaatide tuvastus - näita hoiatust ainult hilisemal kirjal, viita varasemale
        $duplikaat = null;
        if ($kiri->manused->isNotEmpty()) {
            $manus = $kiri->manused->first();
            $duplikaat = Kiri::where('firma_id', $kiri->firma_id)
                ->where('id', '<', $kiri->id)  // Ainult varasem kiri (väiksem ID)
                ->whereHas('manused', function($q) use ($manus) {
                    $q->where('failinimi', $manus->failinimi)
                      ->where('suurus', $manus->suurus);
                })
                ->first();
        }

        return view('kiri.show', compact('kiri', 'vestlus', 'tegevused', 'duplikaat'));
    }
    
    /**
     * Vastamise vorm
     */
    public function reply(Kiri $kiri)
    {
        $this->authorize($kiri);
        
        return view('kiri.reply', compact('kiri'));
    }
    
    /**
     * Saada vastus
     */
    public function sendReply(Request $request, Kiri $kiri, GmailService $gmail)
    {
        $this->authorize($kiri);
        
        $request->validate([
            'sisu' => 'required|string',
        ]);
        
        $uusKiri = $gmail->sendReply($kiri, $request->sisu);
        
        if ($uusKiri) {
            // Märgi algne kiri töödeldud
            $kiri->update(['staatus' => 'valmis']);
            
            return redirect()->route('kiri.show', $kiri)
                ->with('success', 'Vastus saadetud');
        }
        
        return redirect()->back()
            ->with('error', 'Vastuse saatmine ebaõnnestus');
    }
    
    /**
     * Muuda kirja staatust
     */
    public function updateStaatus(Request $request, Kiri $kiri)
    {
        $this->authorize($kiri);
        
        $request->validate([
            'staatus' => 'required|in:uus,loetud,tootluses,valmis,ignoreeritud',
        ]);
        
        $vanaStaatus = $kiri->staatus;
        $kiri->update(['staatus' => $request->staatus]);
        
        Tegevus::lisa(
            $kiri->firma_id,
            'staatus_muudetud',
            "Staatus muudetud: {$vanaStaatus} → {$request->staatus}",
            $kiri->id
        );
        
        return redirect()->back()
            ->with('success', 'Staatus uuendatud');
    }
    
    /**
     * Lisa märkus
     */
    public function lisaMarkus(Request $request, Kiri $kiri)
    {
        $this->authorize($kiri);
        
        $request->validate([
            'markus' => 'required|string|max:1000',
        ]);
        
        Tegevus::lisa(
            $kiri->firma_id,
            'markus_lisatud',
            $request->markus,
            $kiri->id
        );
        
        return redirect()->back()
            ->with('success', 'Märkus lisatud');
    }
    
    /**
     * Teisalda kiri teise firmasse
     */
    public function teisalda(Request $request, Kiri $kiri)
    {
        $this->authorize($kiri);

        $request->validate([
            'firma_id' => 'required|exists:firmad,id',
        ]);

        $uusFirmaId = (int) $request->firma_id;
        $vanaFirma = $kiri->firma;
        $uusFirma = Firma::findOrFail($uusFirmaId);

        // Uuenda kirja firma
        $kiri->update(['firma_id' => $uusFirmaId]);

        // Uuenda kõik manused
        $kiri->manused()->update(['firma_id' => $uusFirmaId]);

        // Lisa tegevus
        Tegevus::lisa(
            $uusFirmaId,
            'kiri_teisaldatud',
            "Teisaldatud firmast: {$vanaFirma->nimi}",
            $kiri->id
        );

        // Vaheta aktiivne firma ja suuna kirjale
        session(['aktiivne_firma_id' => $uusFirmaId]);

        return redirect()->route('kiri.show', $kiri)
            ->with('success', "Kiri teisaldatud firmasse: {$uusFirma->nimi}");
    }

    /**
     * Kontrolli kas kasutajal on õigus seda kirja näha
     */
    private function authorize(Kiri $kiri): void
    {
        $firmaId = session('aktiivne_firma_id');

        if ($kiri->firma_id !== (int) $firmaId) {
            abort(403, 'Sul pole õigust seda kirja vaadata');
        }
    }

    /**
     * Näita manust (lae Gmailist ja serveeri)
     */
    public function showManus(Manus $manus, GmailService $gmail)
    {
        // Kontrolli õigusi
        if ($manus->firma_id != session('aktiivne_firma_id')) {
            abort(403);
        }

        // Lae manus Gmailist
        $path = $gmail->downloadAttachment($manus);

        if (!$path || !Storage::exists($path)) {
            abort(404, 'Manust ei õnnestunud laadida');
        }

        // Kasuta õiget MIME tüüpi - kui on PDF laiend, siis application/pdf
        $contentType = $manus->mime_type;
        if ($manus->on_pdf && $contentType !== 'application/pdf') {
            $contentType = 'application/pdf';
        }

        return response()->file(Storage::path($path), [
            'Content-Type' => $contentType,
            'Content-Disposition' => 'inline; filename="' . $manus->failinimi . '"'
        ]);
    }

    /**
     * Muuda manuse tüüpi (arve/muu)
     */
    public function updateManusTyyp(Request $request, Manus $manus)
    {
        if ($manus->firma_id != session('aktiivne_firma_id')) {
            abort(403);
        }

        $request->validate([
            'on_arve' => 'required|boolean',
        ]);

        $manus->update(['on_arve' => $request->on_arve]);

        Tegevus::lisa(
            $manus->firma_id,
            $request->on_arve ? 'manus_arve' : 'manus_muu',
            $manus->failinimi,
            null,
            $manus->id
        );

        return redirect()->back()->with('success', 'Manuse tüüp uuendatud');
    }

    /**
     * Parsi arve andmed manusest (ilma AI-ta)
     */
    public function parseManus(Manus $manus, InvoiceParserService $parser)
    {
        if ($manus->firma_id != session('aktiivne_firma_id')) {
            abort(403);
        }

        // Proovi parsida ilma AI-ta
        $result = $parser->parse($manus, useAI: false);

        return response()->json([
            'success' => $result !== null,
            'data' => $result,
            'message' => $result ? 'Arve andmed tuvastatud' : 'Automaatne tuvastamine ebaõnnestus',
        ]);
    }

    /**
     * Parsi arve andmed manusest (Claude API-ga)
     */
    public function parseManusWithAI(Manus $manus, InvoiceParserService $parser)
    {
        if ($manus->firma_id != session('aktiivne_firma_id')) {
            abort(403);
        }

        // Proovi parsida koos AI-ga
        $result = $parser->parse($manus, useAI: true);

        return response()->json([
            'success' => $result !== null,
            'data' => $result,
            'message' => $result ? 'Arve andmed tuvastatud (' . ($result['allikas'] ?? 'unknown') . ')' : 'Tuvastamine ebaõnnestus',
        ]);
    }
}
