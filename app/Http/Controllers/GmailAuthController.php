<?php

namespace App\Http\Controllers;

use App\Services\GmailService;
use Illuminate\Http\Request;

class GmailAuthController extends Controller
{
    /**
     * Alusta OAuth voogu
     */
    public function redirect(GmailService $gmail)
    {
        if ($gmail->isAuthenticated()) {
            return redirect()->route('dashboard')
                ->with('info', 'Gmail on juba ühendatud');
        }
        
        return redirect($gmail->getAuthUrl());
    }
    
    /**
     * OAuth callback
     */
    public function callback(Request $request, GmailService $gmail)
    {
        if ($request->has('error')) {
            return redirect()->route('dashboard')
                ->with('error', 'Gmail ühendamine katkestatud: ' . $request->error);
        }
        
        if (!$request->has('code')) {
            return redirect()->route('dashboard')
                ->with('error', 'Gmail ühendamine ebaõnnestus');
        }
        
        try {
            $gmail->authenticate($request->code);
            
            return redirect()->route('dashboard')
                ->with('success', 'Gmail edukalt ühendatud!');
                
        } catch (\Exception $e) {
            return redirect()->route('dashboard')
                ->with('error', 'Gmail ühendamine ebaõnnestus: ' . $e->getMessage());
        }
    }
    
    /**
     * Näita ühenduse staatust
     */
    public function status(GmailService $gmail)
    {
        $isConnected = $gmail->isAuthenticated();
        $labels = [];
        
        if ($isConnected) {
            try {
                $labels = $gmail->getLabels();
            } catch (\Exception $e) {
                // Ignore
            }
        }
        
        return view('gmail.status', compact('isConnected', 'labels'));
    }
    
    /**
     * Katkesta ühendus
     */
    public function disconnect()
    {
        $tokenPath = storage_path('app/google/token.json');
        
        if (file_exists($tokenPath)) {
            unlink($tokenPath);
        }
        
        return redirect()->route('gmail.status')
            ->with('success', 'Gmail ühendus katkestatud');
    }
}
