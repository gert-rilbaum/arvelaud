# ArveLaud - Raamatupidaja töölaud

E-kirjade ja arvete haldamise süsteem, mis integreerib Gmail API-ga.

## Nõuded

- PHP 8.2+
- MySQL 8.0+ / MariaDB 10.6+
- Composer
- Node.js (valikuline, Breeze CSS jaoks)

## Installeerimine

### 1. Lae failid serverisse

```bash
cd /var/www/my.e-office24.biz
# Kopeeri failid siia
```

### 2. Installi sõltuvused

```bash
composer install --optimize-autoloader --no-dev
```

### 3. Seadista keskkond

```bash
cp .env.example .env
php artisan key:generate
```

Muuda `.env` failis:
- `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` - andmebaasi andmed
- `APP_URL` - https://my.e-office24.biz

### 4. Loo andmebaas

```bash
php artisan migrate
```

### 5. Installi Laravel Breeze (autentimine)

```bash
composer require laravel/breeze --dev
php artisan breeze:install blade
php artisan migrate
```

### 6. Loo admin kasutaja

```bash
php artisan tinker
>>> \App\Models\User::create(['name' => 'Gert', 'email' => 'gert@rilbaum.ee', 'password' => bcrypt('SINU_PAROOL')])
```

### 7. Google Cloud seadistus

1. Mine: https://console.cloud.google.com/
2. Loo projekt või kasuta olemasolevat
3. Luba Gmail API
4. Loo OAuth 2.0 credentials (Web application)
5. Redirect URI: `https://my.e-office24.biz/gmail/callback`
6. Lae alla JSON ja salvesta: `storage/app/google/credentials.json`

```bash
mkdir -p storage/app/google
# Kopeeri credentials.json siia
```

### 8. Seadista cron (automaatne sünkroonimine)

Lisa crontab'i:

```bash
* * * * * cd /var/www/my.e-office24.biz && php artisan schedule:run >> /dev/null 2>&1
```

### 9. Õigused

```bash
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
```

## Kasutamine

1. Mine https://my.e-office24.biz
2. Logi sisse
3. Ühenda Gmail (Gmail -> Ühenda Gmail)
4. Lisa firmad koos Gmail labelitega
5. Sünkroniseeri kirjad

## Struktuur

```
app/
├── Models/
│   ├── Firma.php          - Klient/firma
│   ├── Kiri.php           - E-kiri
│   ├── Manus.php          - Manused (PDF-id)
│   └── Tegevus.php        - Tegevuste logi
├── Services/
│   └── GmailService.php   - Gmail API
└── Http/Controllers/
    ├── DashboardController.php
    ├── FirmaController.php
    ├── KiriController.php
    └── GmailAuthController.php
```

## Edasiarendus

Järgmised sammud:
1. OCR integratsioon (Google Cloud Vision)
2. Merit Aktiva API integratsioon
3. Automaatne arvete tuvastamine
4. Cron job automaatseks sünkroonimiseks

## Autor

RILBAUM-IT OÜ
