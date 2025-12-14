<?php

namespace App\Services;

use Google\Client;
use Google\Service\Gmail;
use Google\Service\Gmail\Message;
use App\Models\Firma;
use App\Models\Kiri;
use App\Models\Manus;
use App\Models\Tegevus;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class GmailService
{
    private Client $client;
    private Gmail $service;
    
    public function __construct()
    {
        $this->client = new Client();
        $this->client->setAuthConfig(storage_path('app/google/credentials.json'));
        $this->client->setAccessType('offline');
        $this->client->setPrompt('consent');
        $this->client->addScope(Gmail::GMAIL_MODIFY);
        
        // Lae salvestatud token
        $tokenPath = storage_path('app/google/token.json');
        if (file_exists($tokenPath)) {
            $accessToken = json_decode(file_get_contents($tokenPath), true);
            $this->client->setAccessToken($accessToken);
            
            // Uuenda token kui aegunud
            if ($this->client->isAccessTokenExpired()) {
                if ($this->client->getRefreshToken()) {
                    $this->client->fetchAccessTokenWithRefreshToken($this->client->getRefreshToken());
                    file_put_contents($tokenPath, json_encode($this->client->getAccessToken()));
                }
            }
        }
        
        $this->service = new Gmail($this->client);
    }
    
    /**
     * Kas on autenditud?
     */
    public function isAuthenticated(): bool
    {
        return !$this->client->isAccessTokenExpired();
    }
    
    /**
     * Hangi autentimise URL
     */
    public function getAuthUrl(): string
    {
        return $this->client->createAuthUrl();
    }
    
    /**
     * Autendi koodiga (OAuth callback)
     */
    public function authenticate(string $code): void
    {
        $accessToken = $this->client->fetchAccessTokenWithAuthCode($code);
        $this->client->setAccessToken($accessToken);
        
        // Salvesta token
        $tokenPath = storage_path('app/google/token.json');
        if (!is_dir(dirname($tokenPath))) {
            mkdir(dirname($tokenPath), 0700, true);
        }
        file_put_contents($tokenPath, json_encode($accessToken));
    }
    
    /**
     * Hangi kõik labelid
     */
    public function getLabels(): array
    {
        $results = $this->service->users_labels->listUsersLabels('me');
        $labels = [];
        
        foreach ($results->getLabels() as $label) {
            $labels[] = [
                'id' => $label->getId(),
                'name' => $label->getName(),
            ];
        }
        
        return $labels;
    }
    
    /**
     * Sünkroniseeri uued kirjad labeli järgi
     */
    public function syncKirjadByLabel(Firma $firma, int $maxResults = 50): int
    {
        $count = 0;
        $labels = $firma->gmail_labels ?? [];

        if (empty($labels)) {
            return 0;
        }

        foreach ($labels as $label) {
            try {
                // Otsi kirjad selle labeli alt (kasutame query't, mis töötab nii ID kui nimega)
                $labelQuery = 'label:' . str_replace(' ', '-', $label);
                $results = $this->service->users_messages->listUsersMessages('me', [
                    'q' => $labelQuery,
                    'maxResults' => $maxResults,
                ]);

                if (!$results->getMessages()) {
                    continue;
                }

                foreach ($results->getMessages() as $messageRef) {
                    // Kontrolli kas juba olemas
                    if (Kiri::where('gmail_message_id', $messageRef->getId())->exists()) {
                        continue;
                    }

                    // Tõmba täielik kiri
                    $message = $this->service->users_messages->get('me', $messageRef->getId(), [
                        'format' => 'full'
                    ]);

                    // Parsi ja salvesta
                    $kiri = $this->parseAndSaveMessage($firma, $message);
                    if ($kiri) {
                        $count++;
                    }
                }

            } catch (\Exception $e) {
                Log::error('Gmail sync error: ' . $e->getMessage(), [
                    'firma_id' => $firma->id,
                    'label' => $label,
                ]);
            }
        }

        return $count;
    }
    
    /**
     * Parsi Gmail sõnum ja salvesta andmebaasi
     */
    private function parseAndSaveMessage(Firma $firma, Message $message): ?Kiri
    {
        $headers = $this->parseHeaders($message->getPayload()->getHeaders());
        
        // Määra suund
        $myEmail = config('arvelaud.gmail_email');
        $suund = (stripos($headers['from'] ?? '', $myEmail) !== false) ? 'valja' : 'sisse';
        
        // Parsi saatja
        $fromParsed = $this->parseEmailAddress($headers['from'] ?? '');
        
        // Loo kirje
        $kiri = Kiri::create([
            'firma_id' => $firma->id,
            'gmail_message_id' => $message->getId(),
            'gmail_thread_id' => $message->getThreadId(),
            'saatja_email' => $fromParsed['email'],
            'saatja_nimi' => $fromParsed['name'],
            'saaja_email' => $headers['to'] ?? '',
            'teema' => $headers['subject'] ?? '(Teemata)',
            'sisu_text' => $this->getBodyText($message->getPayload()),
            'sisu_html' => $this->getBodyHtml($message->getPayload()),
            'suund' => $suund,
            'staatus' => 'uus',
            'on_manuseid' => $this->hasAttachments($message->getPayload()),
            'gmail_kuupaev' => date('Y-m-d H:i:s', (int)($message->getInternalDate() / 1000)),
        ]);
        
        // Salvesta manused
        $this->saveAttachments($firma, $kiri, $message);
        
        // Lisa tegevus
        Tegevus::lisa($firma->id, 'kiri_saadud', null, $kiri->id);
        
        return $kiri;
    }
    
    /**
     * Parsi headerid massiiviks
     */
    private function parseHeaders(array $headers): array
    {
        $result = [];
        foreach ($headers as $header) {
            $result[strtolower($header->getName())] = $header->getValue();
        }
        return $result;
    }
    
    /**
     * Parsi e-posti aadress ja nimi
     */
    private function parseEmailAddress(string $from): array
    {
        if (preg_match('/^(.+?)\s*<([^>]+)>$/', $from, $matches)) {
            return [
                'name' => trim($matches[1], ' "\''),
                'email' => $matches[2],
            ];
        }
        return [
            'name' => null,
            'email' => $from,
        ];
    }
    
    /**
     * Hangi teksti sisu
     */
    private function getBodyText($payload): ?string
    {
        return $this->getBodyByMimeType($payload, 'text/plain');
    }
    
    /**
     * Hangi HTML sisu
     */
    private function getBodyHtml($payload): ?string
    {
        return $this->getBodyByMimeType($payload, 'text/html');
    }
    
    /**
     * Hangi keha MIME tüübi järgi
     */
    private function getBodyByMimeType($payload, string $mimeType): ?string
    {
        if ($payload->getMimeType() === $mimeType) {
            $data = $payload->getBody()->getData();
            if ($data) {
                return base64_decode(strtr($data, '-_', '+/'));
            }
        }
        
        $parts = $payload->getParts();
        if ($parts) {
            foreach ($parts as $part) {
                $result = $this->getBodyByMimeType($part, $mimeType);
                if ($result) {
                    return $result;
                }
            }
        }
        
        return null;
    }
    
    /**
     * Kas on manuseid?
     */
    private function hasAttachments($payload): bool
    {
        $parts = $payload->getParts();
        if (!$parts) {
            return false;
        }
        
        foreach ($parts as $part) {
            if ($part->getFilename()) {
                return true;
            }
            if ($this->hasAttachments($part)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Salvesta manused
     */
    private function saveAttachments(Firma $firma, Kiri $kiri, Message $message): void
    {
        $this->processAttachmentParts($firma, $kiri, $message->getId(), $message->getPayload());
    }
    
    /**
     * Töötle manuste osad rekursiivselt
     */
    private function processAttachmentParts(Firma $firma, Kiri $kiri, string $messageId, $payload): void
    {
        $parts = $payload->getParts();
        if (!$parts) {
            return;
        }
        
        foreach ($parts as $part) {
            if ($part->getFilename()) {
                $attachmentId = $part->getBody()->getAttachmentId();
                
                Manus::create([
                    'kiri_id' => $kiri->id,
                    'firma_id' => $firma->id,
                    'gmail_attachment_id' => $attachmentId ?? '',
                    'failinimi' => $part->getFilename(),
                    'mime_type' => $part->getMimeType(),
                    'suurus' => $part->getBody()->getSize(),
                    'on_arve' => strtolower(pathinfo($part->getFilename(), PATHINFO_EXTENSION)) === 'pdf',
                ]);
            }
            
            $this->processAttachmentParts($firma, $kiri, $messageId, $part);
        }
    }
    
    /**
     * Lae manus alla
     */
    public function downloadAttachment(Manus $manus): ?string
    {
        try {
            $kiri = $manus->kiri;
            $attachment = $this->service->users_messages_attachments->get(
                'me',
                $kiri->gmail_message_id,
                $manus->gmail_attachment_id
            );
            
            $data = base64_decode(strtr($attachment->getData(), '-_', '+/'));
            
            // Salvesta faili
            $path = "manused/{$manus->firma_id}/{$manus->id}_{$manus->failinimi}";
            Storage::put($path, $data);
            
            $manus->update(['salvestatud_path' => $path]);
            
            return $path;
            
        } catch (\Exception $e) {
            Log::error('Attachment download error: ' . $e->getMessage(), [
                'manus_id' => $manus->id,
            ]);
            return null;
        }
    }
    
    /**
     * Saada vastus
     */
    public function sendReply(Kiri $kiri, string $body, ?array $attachments = null): ?Kiri
    {
        try {
            $firma = $kiri->firma;
            $myEmail = config('arvelaud.gmail_email');
            $myName = config('arvelaud.gmail_name');
            
            // Koosta MIME sõnum
            $subject = $kiri->teema;
            if (!str_starts_with(strtolower($subject), 're:')) {
                $subject = 'Re: ' . $subject;
            }
            
            $rawMessage = "From: {$myName} <{$myEmail}>\r\n";
            $rawMessage .= "To: {$kiri->saatja_email}\r\n";
            $rawMessage .= "Subject: {$subject}\r\n";
            $rawMessage .= "In-Reply-To: {$kiri->gmail_message_id}\r\n";
            $rawMessage .= "References: {$kiri->gmail_message_id}\r\n";
            $rawMessage .= "Content-Type: text/html; charset=UTF-8\r\n\r\n";
            $rawMessage .= $body;
            
            // Encode
            $encodedMessage = rtrim(strtr(base64_encode($rawMessage), '+/', '-_'), '=');
            
            $message = new Message();
            $message->setRaw($encodedMessage);
            $message->setThreadId($kiri->gmail_thread_id);
            
            $sentMessage = $this->service->users_messages->send('me', $message);
            
            // Salvesta saadetud kiri
            $uusKiri = Kiri::create([
                'firma_id' => $firma->id,
                'gmail_message_id' => $sentMessage->getId(),
                'gmail_thread_id' => $sentMessage->getThreadId(),
                'saatja_email' => $myEmail,
                'saatja_nimi' => $myName,
                'saaja_email' => $kiri->saatja_email,
                'teema' => $subject,
                'sisu_html' => $body,
                'suund' => 'valja',
                'staatus' => 'valmis',
                'on_manuseid' => false,
                'gmail_kuupaev' => now(),
            ]);
            
            // Lisa tegevus
            Tegevus::lisa($firma->id, 'kiri_vastatud', null, $uusKiri->id);
            
            return $uusKiri;
            
        } catch (\Exception $e) {
            Log::error('Send reply error: ' . $e->getMessage(), [
                'kiri_id' => $kiri->id,
            ]);
            return null;
        }
    }
}
