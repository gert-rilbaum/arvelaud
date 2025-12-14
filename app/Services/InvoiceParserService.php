<?php

namespace App\Services;

use App\Models\Manus;
use App\Models\Kiri;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class InvoiceParserService
{
    /**
     * Parsi arve andmed - proovi esmalt XML, siis OCR, siis Claude API
     */
    public function parse(Manus $manus, bool $useAI = true): ?array
    {
        $ocrResult = null;

        // 1. Kontrolli kas samas kirjas on XML manus
        $xmlManus = $this->findXmlAttachment($manus->kiri);
        if ($xmlManus) {
            $result = $this->parseXml($xmlManus);
            if ($result && $this->isCompleteResult($result)) {
                $result['allikas'] = 'xml';
                $result['xml_manus_id'] = $xmlManus->id;
                return $result;
            }
        }

        // 2. Proovi PDF teksti regex-parsimist
        if ($manus->on_pdf) {
            $ocrResult = $this->parsePdfText($manus);
            if ($ocrResult && $this->isCompleteResult($ocrResult)) {
                $ocrResult['allikas'] = 'ocr';
                return $ocrResult;
            }
        }

        // 3. Kui OCR ei andnud täielikku tulemust ja AI lubatud, kasuta Claude API-t
        if ($useAI && $manus->on_pdf) {
            $result = $this->parseWithClaude($manus);
            if ($result) {
                $result['allikas'] = 'claude';
                return $result;
            }
        }

        // 4. Kui AI ei õnnestunud, tagasta OCR tulemus (isegi kui osaliselt täidetud)
        if ($ocrResult && $this->isValidResult($ocrResult)) {
            $ocrResult['allikas'] = 'ocr';
            return $ocrResult;
        }

        return null;
    }

    /**
     * Kontrolli kas tulemus on täielik (arve number JA summa)
     */
    private function isCompleteResult(array $result): bool
    {
        return !empty($result['arve_number']) && ($result['summa_kokku'] ?? 0) > 0;
    }

    /**
     * Kontrolli kas tulemus on vähemalt osaliselt kasutatav
     */
    private function isValidResult(array $result): bool
    {
        // Vähemalt arve number või summa peab olema
        return !empty($result['arve_number']) || ($result['summa_kokku'] ?? 0) > 0;
    }

    /**
     * Leia XML manus samast kirjast
     */
    private function findXmlAttachment(Kiri $kiri): ?Manus
    {
        return $kiri->manused()
            ->where(function($q) {
                $q->where('mime_type', 'application/xml')
                  ->orWhere('mime_type', 'text/xml')
                  ->orWhere('failinimi', 'like', '%.xml');
            })
            ->first();
    }

    /**
     * Parsi PDF teksti regex mustritega
     */
    public function parsePdfText(Manus $manus): ?array
    {
        try {
            $pdfContent = $this->getAttachmentContent($manus);
            if (!$pdfContent) {
                return null;
            }

            // Ekstrakti tekst PDF-ist
            $text = $this->extractTextFromPdf($pdfContent);
            if (!$text || strlen($text) < 50) {
                return null;
            }

            return $this->parseInvoiceText($text);

        } catch (\Exception $e) {
            Log::error('PDF text parsing error: ' . $e->getMessage(), [
                'manus_id' => $manus->id,
            ]);
            return null;
        }
    }

    /**
     * Ekstrakti tekst PDF-ist
     */
    private function extractTextFromPdf(string $pdfContent): ?string
    {
        try {
            // Kasuta Smalot PDF Parser'it
            $parser = new \Smalot\PdfParser\Parser();
            $pdf = $parser->parseContent($pdfContent);
            return $pdf->getText();
        } catch (\Exception $e) {
            Log::debug('PDF parser failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Parsi arve andmed tekstist regex mustritega
     */
    private function parseInvoiceText(string $text): array
    {
        $result = [
            'arve_number' => null,
            'arve_kuupaev' => null,
            'maksetahtaeg' => null,
            'hankija_nimi' => null,
            'hankija_reg_nr' => null,
            'hankija_kmkr' => null,
            'summa_km_ta' => null,
            'km_summa' => null,
            'summa_kokku' => null,
            'valuuta' => 'EUR',
            'viitenumber' => null,
            'iban' => null,
        ];

        // Säilita originaaltekst kuupäevade järjekorra jaoks
        $originalText = $text;

        // Normaliseeri tekst (üks tühik kõikide whitespace asemel)
        $text = preg_replace('/\s+/', ' ', $text);

        // Arve number - prioriteedi järjekorras (täpsemad mustrid enne)
        $patterns = [
            '/[Aa]rve\s+(\d{6,12})/u',  // "Arve 81715027" - täpne muster
            '/[Aa]rve\s*(?:nr\.?|number|no\.?)\s*[:\s]*([A-Z0-9\-\/]+)/u',
            '/[Aa]rvenr(\d+)/u',  // Collapsed: Arvenr11273196
            '/[Ii]nvoice\s*(?:no\.?|number)[:\s]*([A-Z0-9\-\/]+)/u',
            '/[Oo]stu[\-\s]*müügi\s*leping\s*nr\.?\s*([A-Z0-9\-\/]+)/u',  // Ostu-müügileping nr.4017458
            '/[Ll]eping\s*(?:nr\.?|number)\s*([A-Z0-9\-\/]+)/u',  // Leping nr
            '/[Tt]ellimus\s*(?:nr\.?|number)\s*([A-Z0-9\-\/]+)/u',  // Tellimus nr
            // NB: "Reg. Nr" ei tohiks arve numbriks sobida
        ];
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $m)) {
                $result['arve_number'] = trim($m[1]);
                break;
            }
        }

        // Kuupäevad - leia kõik kuupäevad tekstist ja määra konteksti järgi
        // Formaat: otsi "Kuupäev" ja "Maksetähtpäev" labeli järel tulevaid kuupäevi
        preg_match_all('/(\d{1,2}\.\d{1,2}\.\d{4})/u', $originalText, $allDates);

        if (!empty($allDates[1])) {
            // Esimene kuupäev on tavaliselt arve kuupäev
            $result['arve_kuupaev'] = $this->normalizeDate($allDates[1][0]);

            // Teine kuupäev on tavaliselt maksetähtaeg (kui on kaks kuupäeva järjest)
            if (count($allDates[1]) >= 2) {
                $result['maksetahtaeg'] = $this->normalizeDate($allDates[1][1]);
            }
        }

        // Proovi ka spetsiifilisi mustreid kuupäevadele
        $datePatterns = [
            '/[Aa]rve\s*kuup[äa]ev[:\s]*(\d{1,2}[.\/-]\d{1,2}[.\/-]\d{2,4})/u',
            '/[Kk]uup[äa]ev[:\s]*(\d{1,2}[.\/-]\d{1,2}[.\/-]\d{2,4})/u',
        ];
        foreach ($datePatterns as $pattern) {
            if (preg_match($pattern, $text, $m)) {
                $result['arve_kuupaev'] = $this->normalizeDate($m[1]);
                break;
            }
        }

        // Maksetähtaeg - spetsiifilised mustrid
        $duePatterns = [
            '/[Mm]akse\s*t[äa]ht\s*(?:aeg|p[äa]ev)[:\s]*(\d{1,2}[.\/-]\d{1,2}[.\/-]\d{2,4})/u',
            '/[Mm]akset[äa]ht(?:aeg|p[äa]ev)[:\s]*(\d{1,2}[.\/-]\d{1,2}[.\/-]\d{2,4})/u',  // Collapsed
            '/[Tt]asumise\s*t[äa]htaeg[:\s]*(\d{1,2}[.\/-]\d{1,2}[.\/-]\d{2,4})/u',  // Tasumise tähtaeg 18.12.2025
            '/[Tt]asuda[:\s]*(\d{1,2}[.\/-]\d{1,2}[.\/-]\d{2,4})/u',
            '/[Dd]ue\s*[Dd]ate[:\s]*(\d{1,2}[.\/-]\d{1,2}[.\/-]\d{2,4})/u',
        ];
        foreach ($duePatterns as $pattern) {
            if (preg_match($pattern, $text, $m)) {
                $result['maksetahtaeg'] = $this->normalizeDate($m[1]);
                break;
            }
        }

        // Viitenumber - erinevad mustrid
        $refPatterns = [
            '/[Vv]iite\s*(?:nr|number)[:\s]*(\d{7,20})/u',
            '/[Vv]iitenumber\s*(\d{7,20})/u',
            '/[Vv]iitenumber(\d{7,20})/u',  // Collapsed
            '/[Vv]iitenumbrit[:\s]*(\d{7,20})/u',  // "viitenumbrit: 10106817150273"
            '/[Rr]eference[:\s]*(\d{7,20})/u',
        ];
        foreach ($refPatterns as $pattern) {
            if (preg_match($pattern, $text, $m)) {
                $result['viitenumber'] = $m[1];
                break;
            }
        }

        // Kui viitenumbrit pole veel leitud, proovi positsiooni järgi (kolmas number järjest)
        if (!$result['viitenumber'] && count($allDates[1] ?? []) >= 2) {
            // Otsi number pärast teist kuupäeva
            $pattern = '/' . preg_quote($allDates[1][1], '/') . '\s*(\d{6,10})/u';
            if (preg_match($pattern, $originalText, $m)) {
                $result['viitenumber'] = $m[1];
            }
        }

        // Registrikood (8 numbrit) - hankija/müüja oma
        // Otsime "Rg-kood" mustrit
        if (preg_match('/[Rr]g[\-\s]*kood\s*(\d{8})/u', $text, $m)) {
            $result['hankija_reg_nr'] = $m[1];
        } elseif (preg_match('/[Rr]eg\.?\s*(?:kood|nr|code)[:\s]*(\d{8})/u', $text, $m)) {
            $result['hankija_reg_nr'] = $m[1];
        }

        // KMKR number (EE + 9 numbrit, mitte osa IBAN-ist)
        // Muster peab olema järgitud mittedigiti või lõpuga, et mitte tabada IBAN-i algust
        preg_match_all('/(EE\d{9})(?!\d)/u', $text, $allKmkr);
        if (count($allKmkr[1] ?? []) >= 2) {
            // Teine KMKR on müüja/hankija oma
            $result['hankija_kmkr'] = $allKmkr[1][1];
        } elseif (count($allKmkr[1] ?? []) === 1) {
            $result['hankija_kmkr'] = $allKmkr[1][0];
        }

        // IBAN (EE + 18-20 tähemärki)
        if (preg_match('/(EE\d{18,20})/u', $text, $m)) {
            $result['iban'] = $m[1];
        }

        // Summad - ka collapsed variandid (Summakm-ta22%254,30, Arvekokku(EUR)310,25)
        $sumPatterns = [
            // Arve kokku - erinevad variandid
            '/[Aa]rve\s*kokku\s*\(?(?:EUR)?\)?\s*(\d+[.,]\d{2})/u' => 'summa_kokku',
            '/[Aa]rvekokku\s*\(?(?:EUR)?\)?\s*(\d+[.,]\d{2})/u' => 'summa_kokku',  // Collapsed
            '/[Kk]okku\s*\(?(?:EUR)?\)?\s*(\d+[.,]\d{2})/u' => 'summa_kokku',
            '/[Tt]otal[:\s]*(\d+[.,]\d{2})/u' => 'summa_kokku',
            '/[Tt]asuda[:\s]*(\d+[.,]\d{2})/u' => 'summa_kokku',

            // Käibemaks / KM
            '/[Kk]äibemaks\s*\d+%?\s*(\d+[.,]\d{2})/u' => 'km_summa',
            '/KM\s*\d+%?\s*(\d+[.,]\d{2})/u' => 'km_summa',
            '/VAT[:\s]*(\d+[.,]\d{2})/u' => 'km_summa',

            // Summa km-ta (ilma käibemaksuta)
            '/[Ss]umma\s*km[\-\s]*ta\s*\d+%?\s*(\d+[.,]\d{2})/u' => 'summa_km_ta',
            '/[Ss]ummakm[\-\s]*ta\s*\d+%?\s*(\d+[.,]\d{2})/u' => 'summa_km_ta',  // Collapsed
            '/[Kk]äibemaksuta[:\s]*(\d+[.,]\d{2})/u' => 'summa_km_ta',  // "Käibemaksuta: 109.98"
            '/[Ii]lma\s*KM[:\s]*(\d+[.,]\d{2})/u' => 'summa_km_ta',
            '/[Nn]eto[:\s]*(\d+[.,]\d{2})/u' => 'summa_km_ta',
        ];

        foreach ($sumPatterns as $pattern => $field) {
            if (!$result[$field] && preg_match($pattern, $text, $m)) {
                $result[$field] = (float)str_replace(',', '.', $m[1]);
            }
        }

        // Kui summa_kokku on olemas aga teised puudu, proovi arvutada
        if ($result['summa_kokku'] && !$result['km_summa'] && !$result['summa_km_ta']) {
            // Eelda 22% KM
            $result['summa_km_ta'] = round($result['summa_kokku'] / 1.22, 2);
            $result['km_summa'] = round($result['summa_kokku'] - $result['summa_km_ta'], 2);
        }

        // Kui ainult km_summa ja summa_km_ta olemas, arvuta kokku
        if (!$result['summa_kokku'] && $result['km_summa'] && $result['summa_km_ta']) {
            $result['summa_kokku'] = round($result['summa_km_ta'] + $result['km_summa'], 2);
        }

        return $result;
    }

    /**
     * Normaliseeri kuupäev YYYY-MM-DD formaati
     */
    private function normalizeDate(string $date): ?string
    {
        // Proovi erinevaid formaate
        $formats = ['d.m.Y', 'd/m/Y', 'd-m-Y', 'Y-m-d', 'Y/m/d'];
        foreach ($formats as $format) {
            $d = \DateTime::createFromFormat($format, $date);
            if ($d) {
                return $d->format('Y-m-d');
            }
        }

        // Proovi lühikest aastat
        $formats = ['d.m.y', 'd/m/y', 'd-m-y'];
        foreach ($formats as $format) {
            $d = \DateTime::createFromFormat($format, $date);
            if ($d) {
                return $d->format('Y-m-d');
            }
        }

        return null;
    }

    /**
     * Parsi Eesti e-arve XML
     */
    public function parseXml(Manus $manus): ?array
    {
        try {
            // Lae XML sisu
            $xmlContent = $this->getAttachmentContent($manus);
            if (!$xmlContent) {
                return null;
            }

            $xml = simplexml_load_string($xmlContent);
            if (!$xml) {
                return null;
            }

            // Proovi erinevaid e-arve formaate
            return $this->parseEstonianEInvoice($xml)
                ?? $this->parseFinvoice($xml)
                ?? $this->parseGenericInvoiceXml($xml);

        } catch (\Exception $e) {
            Log::error('XML parsing error: ' . $e->getMessage(), [
                'manus_id' => $manus->id,
            ]);
            return null;
        }
    }

    /**
     * Parsi Eesti e-arve standard (EVK formaat)
     */
    private function parseEstonianEInvoice(\SimpleXMLElement $xml): ?array
    {
        // Registreeri namespace'id
        $namespaces = $xml->getNamespaces(true);

        // Proovi leida arve elemendid
        $invoice = $xml->Invoice ?? $xml->E_Invoice->Invoice ?? null;
        if (!$invoice) {
            return null;
        }

        $invoiceInfo = $invoice->InvoiceInformation ?? $invoice->InvoiceHeader ?? null;
        $sellerInfo = $invoice->InvoiceParties->SellerParty ?? $invoice->SellerParty ?? null;
        $total = $invoice->InvoiceSumGroup ?? $invoice->InvoiceTotal ?? null;

        if (!$invoiceInfo) {
            return null;
        }

        return [
            'arve_number' => (string)($invoiceInfo->InvoiceNumber ?? $invoiceInfo->DocumentNumber ?? ''),
            'arve_kuupaev' => (string)($invoiceInfo->InvoiceDate ?? $invoiceInfo->DocumentDate ?? ''),
            'maksetahtaeg' => (string)($invoiceInfo->DueDate ?? $invoiceInfo->PaymentDueDate ?? ''),
            'hankija_nimi' => (string)($sellerInfo->Name ?? $sellerInfo->PartyName ?? ''),
            'hankija_reg_nr' => (string)($sellerInfo->RegNumber ?? $sellerInfo->RegistrationNumber ?? ''),
            'hankija_kmkr' => (string)($sellerInfo->VATRegNumber ?? ''),
            'summa_km_ta' => (float)($total->InvoiceSum ?? $total->TotalWithoutVAT ?? 0),
            'km_summa' => (float)($total->TotalVATSum ?? $total->VATAmount ?? 0),
            'summa_kokku' => (float)($total->TotalSum ?? $total->TotalWithVAT ?? $total->GrandTotal ?? 0),
            'valuuta' => (string)($invoiceInfo->Currency ?? 'EUR'),
            'viitenumber' => (string)($invoiceInfo->ReferenceNumber ?? $invoice->PaymentInfo->ReferenceNumber ?? ''),
            'iban' => (string)($invoice->PaymentInfo->PayToAccount ?? $sellerInfo->AccountNumber ?? ''),
        ];
    }

    /**
     * Parsi Finvoice formaat
     */
    private function parseFinvoice(\SimpleXMLElement $xml): ?array
    {
        if ($xml->getName() !== 'Finvoice') {
            return null;
        }

        $invoiceDetails = $xml->InvoiceDetails ?? null;
        $seller = $xml->SellerPartyDetails ?? null;

        if (!$invoiceDetails) {
            return null;
        }

        return [
            'arve_number' => (string)($invoiceDetails->InvoiceNumber ?? ''),
            'arve_kuupaev' => (string)($invoiceDetails->InvoiceDate ?? ''),
            'maksetahtaeg' => (string)($invoiceDetails->PaymentTermsDetails->InvoiceDueDate ?? ''),
            'hankija_nimi' => (string)($seller->SellerPartyName ?? ''),
            'hankija_reg_nr' => (string)($seller->SellerPartyIdentifier ?? ''),
            'hankija_kmkr' => (string)($seller->SellerPartyVatIdentifier ?? ''),
            'summa_km_ta' => (float)($invoiceDetails->InvoiceTotalVatExcludedAmount ?? 0),
            'km_summa' => (float)($invoiceDetails->InvoiceTotalVatAmount ?? 0),
            'summa_kokku' => (float)($invoiceDetails->InvoiceTotalVatIncludedAmount ?? 0),
            'valuuta' => 'EUR',
            'viitenumber' => (string)($xml->PaymentDetails->PaymentReferenceNumber ?? ''),
            'iban' => (string)($xml->PaymentDetails->PaymentAccountDetails->AccountNumber ?? ''),
        ];
    }

    /**
     * Proovi parsida üldine XML struktuur
     */
    private function parseGenericInvoiceXml(\SimpleXMLElement $xml): ?array
    {
        // Otsi levinumaid välju
        $result = [
            'arve_number' => '',
            'arve_kuupaev' => '',
            'hankija_nimi' => '',
            'summa_kokku' => 0,
        ];

        // Rekursiivne otsing
        $this->searchXmlFields($xml, $result);

        // Kui leidsime vähemalt arve numbri ja summa
        if ($result['arve_number'] || $result['summa_kokku'] > 0) {
            return $result;
        }

        return null;
    }

    /**
     * Otsi XML-ist tuntud välju rekursiivselt
     */
    private function searchXmlFields(\SimpleXMLElement $xml, array &$result): void
    {
        foreach ($xml->children() as $name => $child) {
            $nameLower = strtolower($name);

            if (strpos($nameLower, 'invoicenumber') !== false || $nameLower === 'number') {
                $result['arve_number'] = (string)$child;
            } elseif (strpos($nameLower, 'invoicedate') !== false || $nameLower === 'date') {
                $result['arve_kuupaev'] = (string)$child;
            } elseif (strpos($nameLower, 'sellername') !== false || strpos($nameLower, 'suppliername') !== false) {
                $result['hankija_nimi'] = (string)$child;
            } elseif (strpos($nameLower, 'totalsum') !== false || strpos($nameLower, 'grandtotal') !== false) {
                $result['summa_kokku'] = (float)$child;
            }

            // Rekursioon
            if ($child->count() > 0) {
                $this->searchXmlFields($child, $result);
            }
        }
    }

    /**
     * Parsi PDF Claude API-ga
     */
    public function parseWithClaude(Manus $manus): ?array
    {
        try {
            $apiKey = config('services.anthropic.api_key');
            if (!$apiKey) {
                Log::warning('Claude API key not configured');
                return null;
            }

            // Lae PDF sisu
            $pdfContent = $this->getAttachmentContent($manus);
            if (!$pdfContent) {
                return null;
            }

            // Encode base64
            $pdfBase64 = base64_encode($pdfContent);

            // Saada Claude API-le
            $response = Http::timeout(60)
                ->withHeaders([
                    'x-api-key' => $apiKey,
                    'anthropic-version' => '2023-06-01',
                    'content-type' => 'application/json',
                ])
                ->post('https://api.anthropic.com/v1/messages', [
                    'model' => 'claude-sonnet-4-20250514',
                    'max_tokens' => 1024,
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => [
                                [
                                    'type' => 'document',
                                    'source' => [
                                        'type' => 'base64',
                                        'media_type' => 'application/pdf',
                                        'data' => $pdfBase64,
                                    ],
                                ],
                                [
                                    'type' => 'text',
                                    'text' => $this->getClaudePrompt(),
                                ],
                            ],
                        ],
                    ],
                ]);

            if (!$response->successful()) {
                Log::error('Claude API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return null;
            }

            $content = $response->json('content.0.text');
            return $this->parseClaudeResponse($content);

        } catch (\Exception $e) {
            Log::error('Claude parsing error: ' . $e->getMessage(), [
                'manus_id' => $manus->id,
            ]);
            return null;
        }
    }

    /**
     * Claude API prompt arve parsimiseks
     */
    private function getClaudePrompt(): string
    {
        return <<<'PROMPT'
Analüüsi seda arvet ja tagasta JSON formaadis järgmised andmed:

{
    "arve_number": "arve number",
    "arve_kuupaev": "YYYY-MM-DD",
    "maksetahtaeg": "YYYY-MM-DD või null",
    "hankija_nimi": "müüja/hankija ettevõtte nimi",
    "hankija_reg_nr": "registrikood või null",
    "hankija_kmkr": "KMKR number või null",
    "summa_km_ta": 0.00,
    "km_summa": 0.00,
    "summa_kokku": 0.00,
    "valuuta": "EUR",
    "viitenumber": "viitenumber või null",
    "iban": "pangakonto või null"
}

Tagasta AINULT JSON, mitte midagi muud. Kui mõnda välja ei leia, jäta null või 0.
PROMPT;
    }

    /**
     * Parsi Claude vastus JSON-iks
     */
    private function parseClaudeResponse(?string $content): ?array
    {
        if (!$content) {
            return null;
        }

        // Eemalda võimalikud markdown code block märgid
        $content = preg_replace('/```json\s*/', '', $content);
        $content = preg_replace('/```\s*$/', '', $content);
        $content = trim($content);

        $data = json_decode($content, true);
        if (!$data || !is_array($data)) {
            Log::warning('Failed to parse Claude response as JSON', ['content' => $content]);
            return null;
        }

        // Normaliseeri andmed
        return [
            'arve_number' => $data['arve_number'] ?? '',
            'arve_kuupaev' => $data['arve_kuupaev'] ?? '',
            'maksetahtaeg' => $data['maksetahtaeg'] ?? null,
            'hankija_nimi' => $data['hankija_nimi'] ?? '',
            'hankija_reg_nr' => $data['hankija_reg_nr'] ?? null,
            'hankija_kmkr' => $data['hankija_kmkr'] ?? null,
            'summa_km_ta' => (float)($data['summa_km_ta'] ?? 0),
            'km_summa' => (float)($data['km_summa'] ?? 0),
            'summa_kokku' => (float)($data['summa_kokku'] ?? 0),
            'valuuta' => $data['valuuta'] ?? 'EUR',
            'viitenumber' => $data['viitenumber'] ?? null,
            'iban' => $data['iban'] ?? null,
        ];
    }

    /**
     * Hangi manuse sisu (lae Gmailist kui vaja)
     */
    private function getAttachmentContent(Manus $manus): ?string
    {
        // Kui fail on juba salvestatud
        if ($manus->salvestatud_path && Storage::exists($manus->salvestatud_path)) {
            return Storage::get($manus->salvestatud_path);
        }

        // Lae Gmailist
        $gmail = app(GmailService::class);
        $path = $gmail->downloadAttachment($manus);

        if ($path && Storage::exists($path)) {
            return Storage::get($path);
        }

        return null;
    }
}
