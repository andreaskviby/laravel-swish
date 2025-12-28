# Installationsguide - Laravel Swish

En steg-för-steg guide för att installera och konfigurera Laravel Swish-paketet.

## Innehållsförteckning

1. [Förutsättningar](#förutsättningar)
2. [Installation](#installation)
3. [Certifikat-setup](#certifikat-setup)
4. [Konfiguration](#konfiguration)
5. [Verifiering](#verifiering)
6. [Felsökning](#felsökning)

## Förutsättningar

### System-krav

- **PHP:** 8.1 eller högre
- **Laravel:** 10.0 eller högre
- **PHP Extensions:**
  - cURL
  - OpenSSL
  - JSON
  - mbstring

### Swish-krav

- Ett aktivt Swish-avtal med din bank
- Åtkomst till Swish-certifikat (från din bank eller Swish Developer Portal)
- Ett företags-/organisations-Swish-nummer (payee alias)

## Installation

### Steg 1: Installera paketet via Composer

```bash
composer require andreaskviby/laravel-swish
```

### Steg 2: Publicera konfigurationsfilen

```bash
php artisan vendor:publish --provider="AndreasKviby\LaravelSwish\SwishServiceProvider"
```

Detta skapar filen `config/swish.php` i din Laravel-applikation.

### Steg 3: Verifiera att Service Provider är registrerad

Laravel kommer automatiskt att upptäcka och registrera service provider tack vare auto-discovery. Du kan verifiera detta genom att köra:

```bash
php artisan about
```

Leta efter "AndreasKviby\LaravelSwish\SwishServiceProvider" i listan över providers.

## Certifikat-setup

### För testmiljö (Merchant Swish Simulator)

1. **Ladda ner testcertifikat:**
   - Besök [Swish Developer Portal](https://developer.swish.nu/)
   - Gå till "Resources" > "Test Certificates"
   - Ladda ner MSS (Merchant Swish Simulator) certifikat

2. **Extrahera certifikaten:**
   
   Om certifikaten är i .p12 format, konvertera till .pem:
   
   ```bash
   # Skapa en mapp för certifikat
   mkdir storage/certificates
   
   # Konvertera från .p12 till .pem
   openssl pkcs12 -in Swish_Merchant_TestCertificate_1234679304.p12 \
     -out storage/certificates/swish-test-cert.pem \
     -nodes
   ```
   
   När du blir frågad om lösenord, använd lösenordet som anges i Swish dokumentation (oftast "swish").

3. **Ladda ner Swish root-certifikat:**
   
   ```bash
   # För testmiljö
   curl -o storage/certificates/swish-root-test.pem \
     https://developer.swish.nu/certificates/test/Swish_TLS_RootCA.pem
   ```

### För produktionsmiljö

1. **Kontakta din bank:**
   - Begär dina Swish produktionscertifikat
   - Varje bank har sin egen process för detta

2. **Ta emot och installera certifikat:**
   - Du kommer få ett eller flera certifikat-filer
   - Spara dessa säkert i `storage/certificates/`
   - Sätt korrekta filrättigheter:
   
   ```bash
   chmod 600 storage/certificates/*.pem
   ```

3. **Ladda ner produktions root-certifikat:**
   
   ```bash
   # För produktion
   curl -o storage/certificates/swish-root-prod.pem \
     https://developer.swish.nu/certificates/production/Swish_TLS_RootCA.pem
   ```

### Säkerhetsrekommendationer för certifikat

- **Lagra aldrig certifikat i git:** Certifikaten är redan inkluderade i `.gitignore`
- **Använd absoluta sökvägar:** I konfigurationen, använd `storage_path('certificates/...')`
- **Skydda filerna:** Sätt restriktiva filrättigheter (600)
- **Rotera certifikat:** Följ Swish:s rekommendationer för certifikat-rotation
- **Backup:** Ha en säker backup av dina certifikat

## Konfiguration

### Steg 1: Lägg till miljövariabler i .env

```env
# Swish Miljö
# Använd 'test' för utveckling och 'production' för live
SWISH_ENVIRONMENT=test

# Swish Certifikat (absoluta sökvägar)
# För Linux/Mac
SWISH_CLIENT_CERT_PATH=/var/www/html/storage/certificates/swish-test-cert.pem
SWISH_ROOT_CERT_PATH=/var/www/html/storage/certificates/swish-root-test.pem

# För Windows
# SWISH_CLIENT_CERT_PATH=C:\laravel\storage\certificates\swish-test-cert.pem
# SWISH_ROOT_CERT_PATH=C:\laravel\storage\certificates\swish-root-test.pem

# Certifikat-lösenord (om det behövs)
SWISH_CLIENT_CERT_PASSWORD=

# Ditt Swish-nummer (payee alias)
# Testmiljö: använd testnumret från Swish
SWISH_PAYEE_ALIAS=1234679304

# Callback URL
# Detta är URL:en där Swish skickar betalningsbekräftelser
# OBS: Måste vara tillgänglig från internet!
SWISH_CALLBACK_URL=https://yoursite.com/api/swish/callback

# Valfria inställningar
SWISH_TIMEOUT=30
SWISH_VERIFY_SSL=true
```

### Steg 2: Använd Laravel helpers för sökvägar (rekommenderat)

Uppdatera `config/swish.php` för att använda Laravel helpers:

```php
'certificates' => [
    'client_cert' => env('SWISH_CLIENT_CERT_PATH', storage_path('certificates/swish-cert.pem')),
    'client_cert_password' => env('SWISH_CLIENT_CERT_PASSWORD'),
    'root_cert' => env('SWISH_ROOT_CERT_PATH', storage_path('certificates/swish-root.pem')),
],
```

### Steg 3: Konfigurera callback-route

Skapa en route för att ta emot callbacks från Swish:

```php
// routes/web.php eller routes/api.php
Route::post('/api/swish/callback/{order}', [SwishController::class, 'handleCallback'])
    ->name('swish.callback');
```

### Steg 4: Skapa callback-controller

```bash
php artisan make:controller SwishController
```

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use AndreasKviby\LaravelSwish\Facades\Swish;

class SwishController extends Controller
{
    public function handleCallback($orderId)
    {
        try {
            // Implementera din callback-logik här
            // Se README.md för exempel
            
            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            \Log::error('Swish callback error: ' . $e->getMessage());
            return response()->json(['status' => 'error'], 500);
        }
    }
}
```

## Verifiering

### Test 1: Verifiera certifikat-konfiguration

Skapa ett test-kommando:

```bash
php artisan make:command TestSwishConnection
```

```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use AndreasKviby\LaravelSwish\Support\Certificate;

class TestSwishConnection extends Command
{
    protected $signature = 'swish:test-connection';
    protected $description = 'Test Swish certificate configuration';

    public function handle()
    {
        try {
            $cert = new Certificate(
                config('swish.certificates.client_cert'),
                config('swish.certificates.client_cert_password'),
                config('swish.certificates.root_cert')
            );
            
            $this->info('✓ Certifikat konfigurerade korrekt!');
            $this->info('Client cert: ' . $cert->getClientCertPath());
            $this->info('Root cert: ' . $cert->getRootCertPath());
            
            return 0;
        } catch (\Exception $e) {
            $this->error('✗ Certifikat-fel: ' . $e->getMessage());
            return 1;
        }
    }
}
```

Kör kommandot:

```bash
php artisan swish:test-connection
```

### Test 2: Skapa en test-betalning

```php
use AndreasKviby\LaravelSwish\Facades\Swish;
use AndreasKviby\LaravelSwish\Models\PaymentRequest;

Route::get('/test-swish', function () {
    $payment = new PaymentRequest([
        'payeeAlias' => config('swish.payee_alias'),
        'amount' => '100.00',
        'currency' => 'SEK',
        'message' => 'Test betalning',
        'callbackUrl' => route('swish.callback', ['order' => 'TEST123']),
    ]);
    
    try {
        $response = Swish::createPaymentRequest($payment);
        
        return response()->json([
            'success' => true,
            'payment_id' => $payment->id,
            'token' => $response['token'] ?? null,
            'location' => $response['location'] ?? null,
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
        ], 400);
    }
});
```

Besök `/test-swish` i din webbläsare.

### Test 3: Kör enhetstester

```bash
./vendor/bin/phpunit
```

## Felsökning

### Problem: "Klientcertifikat hittades inte"

**Lösning:**
1. Kontrollera att sökvägen i `.env` är absolut och korrekt
2. Verifiera att filen finns: `ls -la /path/to/certificate.pem`
3. Kontrollera filrättigheter: `chmod 644 /path/to/certificate.pem`

### Problem: "SSL certificate problem"

**Lösning:**
1. Kontrollera att root-certifikatet är korrekt
2. För lokal utveckling, kan du temporärt sätta `SWISH_VERIFY_SSL=false`
3. Kontrollera att din PHP-installation har uppdaterade CA-certifikat

### Problem: "Callback URL ej nåbar" (RP03)

**Lösning:**
1. **Lokal utveckling:** Använd ngrok eller liknande för att exponera din lokala server:
   ```bash
   ngrok http 8000
   ```
   Använd den genererade URL:en som SWISH_CALLBACK_URL

2. **Produktion:** Kontrollera att URL:en är:
   - Tillgänglig från internet
   - Använder HTTPS (inte HTTP)
   - Inte skyddad av authentication/middleware

### Problem: "Could not resolve host"

**Lösning:**
1. Kontrollera din internetanslutning
2. Kontrollera DNS-inställningar
3. Verifiera att du kan nå Swish endpoints:
   ```bash
   curl -I https://mss.cpc.getswish.net
   ```

### Problem: OpenSSL errors

**Lösning:**
1. Kontrollera PHP OpenSSL extension:
   ```bash
   php -m | grep openssl
   ```

2. Om den saknas, installera:
   ```bash
   # Ubuntu/Debian
   sudo apt-get install php-openssl
   
   # CentOS/RHEL
   sudo yum install php-openssl
   ```

3. Starta om webbservern efter installation

### Problem: "Invalid certificate format"

**Lösning:**
1. Kontrollera att certifikatet är i PEM-format
2. Öppna filen och verifiera att den börjar med `-----BEGIN CERTIFICATE-----`
3. Om det är .p12-format, konvertera till .pem (se Certifikat-setup ovan)

## Support och hjälp

### Officiella resurser

- [Swish Developer Portal](https://developer.swish.nu/)
- [Swish API Reference](https://developer.swish.nu/api)
- [Laravel Documentation](https://laravel.com/docs)

### Community support

- [GitHub Issues](https://github.com/andreaskviby/laravel-swish/issues)
- [Laravel Community](https://laravel.com/community)

### Tips för framgång

1. **Börja alltid i testmiljö:** Använd MSS innan du går till produktion
2. **Logga allt:** Använd Laravel logging för att spåra betalningar
3. **Testa callbacks:** Använd ngrok för lokal utveckling
4. **Läs dokumentationen:** Både denna guide och Swish officiella dokumentation
5. **Hantera fel graciöst:** Implementera robust felhantering

## Nästa steg

Nu när installationen är klar:

1. Läs [README.md](README.md) för detaljerad API-dokumentation
2. Se [EXAMPLES.md](EXAMPLES.md) för praktiska användningsexempel
3. Implementera ditt första betalningsflöde
4. Testa grundligt i testmiljö
5. Gå till produktion när allt fungerar

Lycka till med din Swish-integration! 🎉
