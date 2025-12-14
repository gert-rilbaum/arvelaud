# ArveLaud Projekti Juhend

## Projekti Kirjeldus
ArveLaud on Laravel-põhine rakendus e-kirjade ja arvete haldamiseks. Rakendus ühendub Gmailiga, toob kirjad sisse firma labelite alusel ja võimaldab PDF-arveid automaatselt parsida.

## Tehniline Stack
- **Backend:** Laravel 12 (PHP 8.2+)
- **Frontend:** Blade templates + Tailwind CSS
- **Andmebaas:** MySQL (Zone.ee shared hosting)
- **API-d:** Gmail API, Claude API (arve parsing)

## Serverid ja Ligipääs

### Tootmisserver (Zone.ee)
- **Host:** `arvelaud` (SSH config alias)
- **Tegelik host:** www.e-office24.biz
- **Kasutaja:** virt65405
- **SSH võti:** `C:\Users\gert\OneDrive\drupal\.ssh\id_rsa`
- **Projekti kaust:** `domeenid/www.e-office24.biz/my/arvelaud/`
- **URL:** https://my.e-office24.biz/

### Lokaalne arendus
- **Kaust:** `C:\Users\gert\OneDrive\Z_Claude\ArveLaud\arvelaud\`
- **Git repo:** https://github.com/gert-rilbaum/arvelaud

## Andmebaasi Struktuur

### Tabelid
- **firmad** - Ettevõtted (gmail_labels JSON array, registrikood, KMKR)
- **kirjad** - E-kirjad (gmail_id, thread_id, staatus, suund)
- **manused** - Manused (failinimi, mime_type, gmail_attachment_id, on_arve)
- **tegevused** - Tegevuste logi (kiri_id, manus_id, tyyp, kirjeldus)
- **users** - Laravel Breeze kasutajad

## Põhifunktsioonid

### 1. Gmail Integratsioon
- OAuth2 autentimine
- Kirjade sünkroonimine labelite alusel
- Üks firma võib omada mitut labelit (JSON array)
- Iga label kuulub ainult ühele firmale

### 2. Kirjade Haldus
- Staatused: uus, loetud, tootluses, valmis, ignoreeritud
- Duplikaatide tuvastus (sama failinimi + suurus)
- Kirjade teisaldamine firmade vahel

### 3. Arve Parser (InvoiceParserService)
3-astmeline lähenemine:
1. **XML parser** - Eesti e-arve formaadid (EVK, Finvoice)
2. **OCR/regex parser** - PDF teksti analüüs ilma AI-ta (smalot/pdfparser)
3. **Claude AI parser** - Keerukate PDF-ide jaoks (fallback)

**Parsitavad väljad:**
- arve_number, arve_kuupaev, maksetahtaeg
- hankija_nimi, hankija_reg_nr, hankija_kmkr
- summa_km_ta, km_summa, summa_kokku
- valuuta, viitenumber, iban

**Loogika:** Kui OCR leiab arve numbri AGA MITTE summasid → proovib Claude AI-d

## Olulised Failid

### Controllers
- `app/Http/Controllers/KiriController.php` - Kirjade ja manuste haldus
- `app/Http/Controllers/FirmaController.php` - Firmade CRUD + sünkroonimine
- `app/Http/Controllers/GmailAuthController.php` - OAuth voog

### Services
- `app/Services/GmailService.php` - Gmail API suhtlus
- `app/Services/InvoiceParserService.php` - Arve parsing (XML/OCR/AI)

### Views
- `resources/views/kiri/show.blade.php` - Kirja vaade + arve parser UI
- `resources/views/firma/edit.blade.php` - Firma muutmine (labelite valik)

### Config
- `config/services.php` - Anthropic API key konfig
- `.env` - ANTHROPIC_API_KEY, GOOGLE_CLIENT_ID, jne

## Keskkonna Muutujad (.env)

```
# Gmail OAuth
GOOGLE_CLIENT_ID=xxx
GOOGLE_CLIENT_SECRET=xxx

# Claude API (arve parsing)
ANTHROPIC_API_KEY=sk-ant-api...

# Merit Aktiva (TULEB LISADA)
MERIT_API_ID=xxx
MERIT_API_KEY=xxx
```

## Järgmised Sammud - Merit Integratsioon

### 1. Merit API Seadistus
- Hangi Merit Aktiva API võtmed (API ID + API Key)
- Lisa .env faili: MERIT_API_ID, MERIT_API_KEY
- Lisa config/services.php: merit konfiguratsioon

### 2. Merit Service Loomine
Loo `app/Services/MeritService.php`:
- API autentimine (timestamp + signature)
- Ostuarve loomine: POST /api/v1/sendinvoice
- Hankijate otsing/loomine
- Vigade käsitlemine

### 3. UI Uuendused
- Aktiveeri "Saada Meriti" nupp
- Lisa kinnituse dialoog enne saatmist
- Näita Merit vastust (arve ID, link)

### 4. Merit API Endpoint-id
- Base URL: `https://aktiva.merit.ee/api/v1/`
- Ostuarve: `POST /sendinvoice`
- Hankijad: `GET /getvendors`
- Autentimine: HMAC-SHA256 signature

## Testimine

### Testitud Arved (manus ID-d)
- **ID 10** - Merit Tarkvara arve → OCR töötab täielikult
- **ID 5** - EMK/Postimees arve → OCR töötab täielikult
- **ID 2** - Keretööde arve → OCR osaline, AI täiendab

### Test Käsud (serveris)
```bash
cd domeenid/www.e-office24.biz/my/arvelaud
php /tmp/test_parser.php      # Merit arve
php /tmp/test_parser2.php     # EMK arve
php /tmp/test_parser_ai.php   # Keretööde arve (AI)
```

## Deployment

### Failide üleslaadimine
```bash
scp failinimi arvelaud:domeenid/www.e-office24.biz/my/arvelaud/path/
```

### Git push
```bash
cd "C:\Users\gert\OneDrive\Z_Claude\ArveLaud\arvelaud"
git add -A && git commit -m "kirjeldus" && git push
```

## Kontaktid
- **Arendaja:** Gert Rilbaum (gert@rilbaum.ee)
- **GitHub:** https://github.com/gert-rilbaum/arvelaud
