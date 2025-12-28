# Laravel Swish API Wrapper

Ett komplett Laravel-paket för att integrera Swish API - betalningar, återbetalningar, utbetalningar och QR-koder.

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)

## Innehållsförteckning

- [Om Swish](#om-swish)
- [Funktioner](#funktioner)
- [Krav](#krav)
- [Installation](#installation)
- [Konfiguration](#konfiguration)
- [Användning](#användning)
  - [Betalningsbegäran](#betalningsbegäran)
  - [Hämta betalningsstatus](#hämta-betalningsstatus)
  - [Avbryta betalning](#avbryta-betalning)
  - [Återbetalningar](#återbetalningar)
  - [Utbetalningar](#utbetalningar)
  - [QR-koder](#qr-koder)
- [Callback-hantering](#callback-hantering)
- [Testmiljö](#testmiljö)
- [Felhantering](#felhantering)
- [Exempel](#exempel)
- [Support](#support)
- [Licens](#licens)

## Om Swish

Swish är Sveriges ledande mobilbetalningssystem som möjliggör snabba och säkra betalningar mellan privatpersoner och företag. Alla betalningar bekräftas med BankID för maximal säkerhet.

Detta paket är en komplett API-wrapper som implementerar alla Swish API-funktioner för Laravel-applikationer.

## Funktioner

✅ **Betalningar (Payment Requests)**
- Skapa betalningsbegäran för e-handel
- Stöd för både M-Commerce (mobil) och E-Commerce (desktop med QR-kod)
- Hämta betalningsstatus
- Avbryt väntande betalningar

✅ **Återbetalningar (Refunds)**
- Full och delvis återbetalning
- Flera delåterbetalningar på samma betalning
- Spåra återbetalningsstatus

✅ **Utbetalningar (Payouts)**
- Skicka pengar direkt till kunder
- Instant överföring

✅ **QR-koder**
- Generera QR-koder för desktop-betalningar
- Stöd för SVG och PNG format

✅ **Säkerhet**
- SSL/TLS certifikathantering
- Säkra API-anrop med klientcertifikat
- Komplett felhantering

✅ **Laravel-integration**
- Service Provider för enkel setup
- Facade för bekväm användning
- Konfigurerbar via .env-fil

## Krav

- PHP 8.1 eller högre
- Laravel 10.0 eller högre
- Swish-avtal med din bank
- Swish klient- och root-certifikat
- PHP cURL och OpenSSL extensions

## Installation

### 1. Installera via Composer

```bash
composer require andreaskviby/laravel-swish
```

### 2. Publicera konfigurationsfilen

```bash
php artisan vendor:publish --provider="AndreasKviby\LaravelSwish\SwishServiceProvider"
```

Detta skapar filen `config/swish.php`.

### 3. Konfigurera certifikat och inställningar

Se [Konfiguration](#konfiguration) nedan.

## Konfiguration

### 1. Lägg till i .env-fil

```env
# Swish Miljö (test eller production)
SWISH_ENVIRONMENT=test

# Swish Certifikat (absoluta sökvägar)
SWISH_CLIENT_CERT_PATH=/path/to/your/client-cert.pem
SWISH_CLIENT_CERT_PASSWORD=your-cert-password
SWISH_ROOT_CERT_PATH=/path/to/swish-root-cert.pem

# Ditt Swish-nummer
SWISH_PAYEE_ALIAS=1234679304

# Callback URL (där Swish skickar betalningsbekräftelser)
SWISH_CALLBACK_URL=https://yoursite.com/api/swish/callback

# Valfria inställningar
SWISH_TIMEOUT=30
SWISH_VERIFY_SSL=true
```

### 2. Hämta Swish-certifikat

#### För testmiljö:
- Ladda ner testcertifikat från [Swish Developer Portal](https://developer.swish.nu/)
- Merchant Swish Simulator (MSS) certifikat används för test

#### För produktion:
- Kontakta din bank för att få dina produktionscertifikat
- Certifikaten är unika för ditt företag

### 3. Konvertera certifikat (om nödvändigt)

Om du får certifikat i .p12-format, konvertera till .pem:

```bash
# Extrahera privat nyckel och certifikat
openssl pkcs12 -in certificate.p12 -out certificate.pem -nodes

# Extrahera endast certifikat (utan privat nyckel)
openssl pkcs12 -in certificate.p12 -out certificate.pem -nokeys

# Extrahera endast privat nyckel
openssl pkcs12 -in certificate.p12 -out private-key.pem -nocerts -nodes
```

## Användning

### Betalningsbegäran

#### Skapa en betalning (E-Commerce)

```php
use AndreasKviby\LaravelSwish\Facades\Swish;
use AndreasKviby\LaravelSwish\Models\PaymentRequest;

// Skapa betalningsbegäran
$payment = new PaymentRequest([
    'payeeAlias' => config('swish.payee_alias'), // Ditt Swish-nummer
    'amount' => '100.00',
    'currency' => 'SEK',
    'message' => 'Betalning för order #12345',
    'callbackUrl' => route('swish.callback'),
]);

try {
    $response = Swish::createPaymentRequest($payment);
    
    // Response innehåller:
    // - location: URL till betalningen
    // - token: Token för QR-kod generering
    
    $paymentToken = $response['token'];
    $paymentLocation = $response['location'];
    
    // Spara payment ID för senare uppföljning
    $paymentId = $payment->id;
    
    // Generera QR-kod för desktop-användare
    $qrCodeUrl = Swish::generateQRCode($paymentToken);
    
    return view('payment.show', [
        'qrCodeUrl' => $qrCodeUrl,
        'paymentId' => $paymentId,
    ]);
    
} catch (\AndreasKviby\LaravelSwish\Exceptions\ApiException $e) {
    // Hantera API-fel
    return back()->withErrors(['error' => $e->getMessage()]);
}
```

#### Skapa en betalning (M-Commerce - mobil)

```php
use AndreasKviby\LaravelSwish\Facades\Swish;
use AndreasKviby\LaravelSwish\Models\PaymentRequest;

// För mobilanvändare, inkludera payerAlias för direkt öppning av Swish-appen
$payment = new PaymentRequest([
    'payerAlias' => '46701234567', // Kundens mobilnummer
    'payeeAlias' => config('swish.payee_alias'),
    'amount' => '250.50',
    'currency' => 'SEK',
    'message' => 'Betalning för tjänst',
    'callbackUrl' => route('swish.callback'),
]);

$response = Swish::createPaymentRequest($payment);

// Omdirigera till Swish-app
return redirect($response['location']);
```

### Hämta betalningsstatus

```php
use AndreasKviby\LaravelSwish\Facades\Swish;

$paymentId = 'AB23456789...'; // ID från när betalningen skapades

try {
    $status = Swish::getPaymentRequest($paymentId);
    
    // Status kan vara: CREATED, PAID, DECLINED, ERROR, CANCELLED
    echo $status['status'];
    
    // Annan information
    echo $status['amount'];
    echo $status['payerAlias'];
    echo $status['dateCreated'];
    echo $status['datePaid'] ?? 'Ej betald än';
    
} catch (\AndreasKviby\LaravelSwish\Exceptions\ApiException $e) {
    echo "Kunde inte hämta status: " . $e->getMessage();
}
```

### Avbryta betalning

```php
use AndreasKviby\LaravelSwish\Facades\Swish;

$paymentId = 'AB23456789...';

try {
    $response = Swish::cancelPaymentRequest($paymentId);
    echo "Betalning avbruten";
} catch (\AndreasKviby\LaravelSwish\Exceptions\ApiException $e) {
    // Betalningen kan inte avbrytas om den redan är betald eller avbruten
    echo "Kunde inte avbryta: " . $e->getMessage();
}
```

### Återbetalningar

#### Skapa en återbetalning

```php
use AndreasKviby\LaravelSwish\Facades\Swish;
use AndreasKviby\LaravelSwish\Models\Refund;

$refund = new Refund([
    'originalPaymentReference' => 'ABC123...', // ID från ursprunglig betalning
    'payerAlias' => config('swish.payee_alias'), // Ditt nummer (du betalar tillbaka)
    'payeeAlias' => '46701234567', // Kundens nummer (tar emot återbetalning)
    'amount' => '100.00', // Helt eller delvis belopp
    'currency' => 'SEK',
    'message' => 'Återbetalning order #12345',
    'callbackUrl' => route('swish.refund.callback'),
]);

try {
    $response = Swish::createRefund($refund);
    
    // Spara refund ID för uppföljning
    $refundId = $refund->id;
    
    return response()->json([
        'message' => 'Återbetalning initierad',
        'refundId' => $refundId,
    ]);
    
} catch (\AndreasKviby\LaravelSwish\Exceptions\ApiException $e) {
    return response()->json(['error' => $e->getMessage()], 400);
}
```

#### Hämta återbetalningsstatus

```php
use AndreasKviby\LaravelSwish\Facades\Swish;

$refundId = 'DEF456...';

$status = Swish::getRefund($refundId);

// Status: CREATED, PAID, DECLINED, ERROR
echo $status['status'];
```

### Utbetalningar

#### Skapa en utbetalning

```php
use AndreasKviby\LaravelSwish\Facades\Swish;
use AndreasKviby\LaravelSwish\Models\Payout;

$payout = new Payout([
    'payerAlias' => config('swish.payee_alias'), // Ditt nummer
    'payeeAlias' => '46709876543', // Mottagarens nummer
    'amount' => '500.00',
    'currency' => 'SEK',
    'message' => 'Utbetalning av vinst',
    'callbackUrl' => route('swish.payout.callback'),
    'payerPaymentReference' => 'PAYOUT-001', // Valfri referens
]);

try {
    $response = Swish::createPayout($payout);
    
    $payoutId = $payout->id;
    
    return response()->json([
        'message' => 'Utbetalning skapad',
        'payoutId' => $payoutId,
    ]);
    
} catch (\AndreasKviby\LaravelSwish\Exceptions\ApiException $e) {
    return response()->json(['error' => $e->getMessage()], 400);
}
```

#### Hämta utbetalningsstatus

```php
use AndreasKviby\LaravelSwish\Facades\Swish;

$payoutId = 'GHI789...';

$status = Swish::getPayout($payoutId);
echo $status['status']; // CREATED, PAID, DECLINED, ERROR
```

### QR-koder

#### Generera QR-kod för betalning

```php
use AndreasKviby\LaravelSwish\Facades\Swish;

// Efter att ha skapat en betalning och fått tillbaka token
$qrCodeUrl = Swish::generateQRCode($paymentToken, 'svg');

// För PNG-format med specifik storlek
$qrCodePngUrl = Swish::generateQRCode($paymentToken, 'png', 400);

// Visa i view
return view('payment.qr', [
    'qrCodeUrl' => $qrCodeUrl,
]);
```

#### Visa QR-kod i Blade-template

```blade
<!-- SVG QR-kod (rekommenderat) -->
<img src="{{ $qrCodeUrl }}" alt="Swish QR-kod" style="width: 300px; height: 300px;">

<!-- Eller inbädda direkt -->
<object data="{{ $qrCodeUrl }}" type="image/svg+xml" width="300" height="300"></object>
```

## Callback-hantering

Swish skickar POST-förfrågningar till din callback URL när en betalning är klar.

### Skapa en callback-route

```php
// routes/api.php
Route::post('/swish/callback', [SwishController::class, 'handleCallback']);
```

### Hantera callback i Controller

```php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use AndreasKviby\LaravelSwish\Facades\Swish;

class SwishController extends Controller
{
    public function handleCallback(Request $request)
    {
        // Swish skickar ett tomt POST-anrop
        // ID:t finns i URL:en eller så kan du extrahera det från request
        
        // Om du har sparat payment ID i din databas, hämta det här
        $paymentId = $request->route('id'); // eller från session/databas
        
        try {
            // Hämta betalningsstatus från Swish
            $payment = Swish::getPaymentRequest($paymentId);
            
            if ($payment['status'] === 'PAID') {
                // Betalning lyckades! Uppdatera din databas
                \App\Models\Order::where('payment_id', $paymentId)
                    ->update([
                        'status' => 'paid',
                        'paid_at' => now(),
                    ]);
                
                // Skicka bekräftelsemail etc.
            } elseif ($payment['status'] === 'DECLINED') {
                // Betalning nekades
                \App\Models\Order::where('payment_id', $paymentId)
                    ->update(['status' => 'declined']);
            }
            
            return response()->json(['status' => 'success']);
            
        } catch (\Exception $e) {
            \Log::error('Swish callback fel: ' . $e->getMessage());
            return response()->json(['status' => 'error'], 500);
        }
    }
}
```

### Bästa praxis för callbacks

1. **Verifiera källan**: I produktion, verifiera att anropet kommer från Swish IP-adresser
2. **Idempotens**: Hantera samma callback flera gånger utan bieffekter
3. **Asynkron bearbetning**: Använd queues för att bearbeta callbacks
4. **Logga allt**: Spara alla callbacks för felsökning
5. **Snabbt svar**: Returnera 200 OK snabbt, gör tung logik asynkront

## Testmiljö

### Swish Merchant Swish Simulator (MSS)

För utveckling och testning använder du Swish MSS-miljö:

1. Ladda ner testcertifikat från [Swish Developer Portal](https://developer.swish.nu/)
2. Sätt `SWISH_ENVIRONMENT=test` i .env
3. Använd testnummer för payerAlias: `46701234567`

### Test-telefonnummer

När du testar i MSS-miljö, använd dessa nummer:

- `46701234567` - Godkänner betalning
- `46701234568` - Nekar betalning
- Andra nummer kan ge olika testscenarier

### Testa utan verklig mobiltelefon

I testmiljö behöver du inte en riktig Swish-app. MSS simulerar hela flödet.

## Felhantering

Paketet kastar olika exceptions beroende på feltyp:

```php
use AndreasKviby\LaravelSwish\Exceptions\ApiException;
use AndreasKviby\LaravelSwish\Exceptions\ValidationException;
use AndreasKviby\LaravelSwish\Exceptions\CertificateException;

try {
    $response = Swish::createPaymentRequest($payment);
} catch (ValidationException $e) {
    // Valideringsfel - felaktig data
    echo "Valideringsfel: " . $e->getMessage();
} catch (CertificateException $e) {
    // Certifikatproblem
    echo "Certifikatfel: " . $e->getMessage();
} catch (ApiException $e) {
    // API-fel från Swish
    echo "API-fel: " . $e->getMessage();
    
    // Hämta detaljerad felinformation
    $details = $e->getErrorDetails();
    if ($details) {
        echo "Felkod: " . ($details[0]['errorCode'] ?? 'N/A');
        echo "Felbeskrivning: " . ($details[0]['errorMessage'] ?? 'N/A');
    }
}
```

### Vanliga felkoder från Swish

- `FF08` - Betalning nekad av användare
- `RP03` - Callback-URL ej nåbar
- `PA02` - Ogiltigt belopp
- `BE18` - Mottagaren finns inte
- `ACMT07` - Mottagaren kan inte ta emot betalningar

## Exempel

### Komplett e-handelsflöde

```php
// 1. Skapa betalning när kund går till checkout
public function checkout()
{
    $order = Order::create([
        'user_id' => auth()->id(),
        'total' => 299.00,
        'status' => 'pending',
    ]);
    
    $payment = new PaymentRequest([
        'payeeAlias' => config('swish.payee_alias'),
        'amount' => (string) $order->total,
        'currency' => 'SEK',
        'message' => "Order #{$order->id}",
        'callbackUrl' => route('swish.callback', ['order' => $order->id]),
    ]);
    
    $response = Swish::createPaymentRequest($payment);
    
    // Spara i databas
    $order->update([
        'payment_id' => $payment->id,
        'payment_token' => $response['token'],
    ]);
    
    // Visa QR-kod
    $qrCode = Swish::generateQRCode($response['token']);
    
    return view('checkout.swish', [
        'order' => $order,
        'qrCode' => $qrCode,
    ]);
}

// 2. Hantera callback
public function handleCallback($orderId)
{
    $order = Order::findOrFail($orderId);
    
    $status = Swish::getPaymentRequest($order->payment_id);
    
    if ($status['status'] === 'PAID') {
        $order->update(['status' => 'paid', 'paid_at' => now()]);
        
        // Skicka email
        Mail::to($order->user)->send(new OrderConfirmation($order));
    }
    
    return response()->json(['status' => 'ok']);
}

// 3. Visa betalningsstatus (polling från frontend)
public function checkStatus($orderId)
{
    $order = Order::findOrFail($orderId);
    
    if (!$order->payment_id) {
        return response()->json(['status' => 'pending']);
    }
    
    try {
        $status = Swish::getPaymentRequest($order->payment_id);
        return response()->json([
            'status' => strtolower($status['status']),
            'paid' => $status['status'] === 'PAID',
        ]);
    } catch (ApiException $e) {
        return response()->json(['status' => 'error'], 500);
    }
}
```

### Återbetalning vid orderavbrytning

```php
public function cancelOrder($orderId)
{
    $order = Order::findOrFail($orderId);
    
    if ($order->status !== 'paid') {
        return back()->with('error', 'Kan endast återbetala betalda ordrar');
    }
    
    // Hämta betalarens Swish-nummer från ursprunglig betalning
    $originalPayment = Swish::getPaymentRequest($order->payment_id);
    
    $refund = new Refund([
        'originalPaymentReference' => $order->payment_id,
        'payerAlias' => config('swish.payee_alias'),
        'payeeAlias' => $originalPayment['payerAlias'],
        'amount' => (string) $order->total,
        'currency' => 'SEK',
        'message' => "Återbetalning order #{$order->id}",
        'callbackUrl' => route('swish.refund.callback', ['order' => $order->id]),
    ]);
    
    try {
        $response = Swish::createRefund($refund);
        
        $order->update([
            'status' => 'refunding',
            'refund_id' => $refund->id,
        ]);
        
        return back()->with('success', 'Återbetalning initierad');
        
    } catch (ApiException $e) {
        return back()->with('error', 'Återbetalning misslyckades: ' . $e->getMessage());
    }
}
```

## Support

### Officiell Swish-dokumentation

- [Swish Developer Portal](https://developer.swish.nu/)
- [Swish API Reference](https://developer.swish.nu/api)

### Problem eller frågor?

- Öppna ett [issue på GitHub](https://github.com/andreaskviby/laravel-swish-/issues)
- Kontrollera att dina certifikat är korrekta
- Verifiera att callback-URL är nåbar från internet
- Använd testmiljön för utveckling

### Bidrag

Pull requests är välkomna! För större ändringar, öppna först ett issue för att diskutera vad du vill ändra.

## Licens

Detta paket är öppen källkod licensierad under [MIT-licensen](LICENSE).

## Tack till

- Swish för deras omfattande API-dokumentation
- Laravel-communityn för fantastiska verktyg och support

---

**Viktigt:** Detta paket är inte officiellt från Swish. Det är ett community-projekt för att göra Swish-integration enklare i Laravel-applikationer.
