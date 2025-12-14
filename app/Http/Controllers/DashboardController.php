<?php

namespace App\Http\Controllers;

use App\Models\Firma;
use App\Models\Kiri;
use App\Models\Manus;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Pealeht - ülevaade
     */
    public function index(Request $request)
    {
        $firmaId = session('aktiivne_firma_id');
        $firma = $firmaId ? Firma::find($firmaId) : null;
        
        $firmad = Firma::aktiivne()->orderBy('nimi')->get();
        
        // Kui firma valitud, näita statistikat
        $stats = null;
        if ($firma) {
            // Ainult aktiivsete kirjade manused
            $aktiivsetKirjadIds = Kiri::where('firma_id', $firma->id)
                ->whereIn('staatus', ['uus', 'loetud', 'tootluses'])
                ->pluck('id');

            $stats = [
                'uusi_kirju' => Kiri::where('firma_id', $firma->id)->where('staatus', 'uus')->count(),
                'tootlemata_arveid' => Manus::where('firma_id', $firma->id)
                    ->where('on_arve', true)
                    ->whereIn('kiri_id', $aktiivsetKirjadIds)
                    ->count(),
                'kirju_kokku' => Kiri::where('firma_id', $firma->id)
                    ->whereIn('staatus', ['uus', 'loetud', 'tootluses'])
                    ->count(),
                'arveid_sisestatud' => Manus::where('firma_id', $firma->id)
                    ->where('arve_staatus', 'sisestatud')
                    ->count(),
            ];
        }
        
        return view('dashboard.index', compact('firmad', 'firma', 'stats'));
    }
    
    /**
     * Vaheta aktiivne firma
     */
    public function vahetaFirma(Request $request)
    {
        $request->validate([
            'firma_id' => 'required|exists:firmad,id',
        ]);

        session(['aktiivne_firma_id' => (int) $request->firma_id]);

        $firma = Firma::find($request->firma_id);

        // Suuna postkasti, mitte tagasi - väldib cache probleeme ja vale firma kirjade vaatamist
        return redirect()->route('kiri.index')->with('success', "Firma vahetatud: {$firma->nimi}");
    }
}
