<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Swish Miljö
    |--------------------------------------------------------------------------
    |
    | Välj vilken miljö du vill använda för Swish API:
    | - 'production' för riktiga transaktioner
    | - 'test' för Swish Merchant Simulator (MSS)
    |
    */

    'environment' => env('SWISH_ENVIRONMENT', 'test'),

    /*
    |--------------------------------------------------------------------------
    | Swish API Endpoints
    |--------------------------------------------------------------------------
    |
    | URL:er för de olika Swish-miljöerna. Du behöver normalt inte ändra dessa.
    |
    */

    'endpoints' => [
        'production' => 'https://cpc.getswish.net/swish-cpcapi/api',
        'test' => 'https://mss.cpc.getswish.net/swish-cpcapi/api',
    ],

    /*
    |--------------------------------------------------------------------------
    | Swish Certifikat
    |--------------------------------------------------------------------------
    |
    | Sökvägar till dina Swish-certifikat. Använd absoluta sökvägar.
    | - client_cert: Din klientcertifikatfil (.pem)
    | - client_cert_password: Lösenord för klientcertifikatet
    | - root_cert: Swish root-certifikat för verifiering (.pem)
    |
    */

    'certificates' => [
        'client_cert' => env('SWISH_CLIENT_CERT_PATH'),
        'client_cert_password' => env('SWISH_CLIENT_CERT_PASSWORD'),
        'root_cert' => env('SWISH_ROOT_CERT_PATH'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Swish Payee Alias
    |--------------------------------------------------------------------------
    |
    | Ditt Swish-nummer (företagets/butikens Swish-nummer).
    | Detta är det nummer som tar emot betalningar.
    |
    */

    'payee_alias' => env('SWISH_PAYEE_ALIAS'),

    /*
    |--------------------------------------------------------------------------
    | Callback URL
    |--------------------------------------------------------------------------
    |
    | Standard callback URL som Swish anropar när en betalning är klar.
    | Du kan överskriva detta per betalning om du behöver.
    |
    */

    'callback_url' => env('SWISH_CALLBACK_URL'),

    /*
    |--------------------------------------------------------------------------
    | Timeout
    |--------------------------------------------------------------------------
    |
    | Timeout i sekunder för API-anrop till Swish.
    |
    */

    'timeout' => env('SWISH_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | Verify SSL
    |--------------------------------------------------------------------------
    |
    | Anger om SSL-certifikat ska verifieras. Bör alltid vara true i produktion.
    | Kan sättas till false för lokal utveckling om det behövs.
    |
    */

    'verify_ssl' => env('SWISH_VERIFY_SSL', true),

    /*
    |--------------------------------------------------------------------------
    | QR-kod URL
    |--------------------------------------------------------------------------
    |
    | URL för Swish QR-kod generering. Denna URL används för att generera
    | QR-koder som kunder kan skanna för att betala.
    |
    */

    'qr_code_url' => env('SWISH_QR_CODE_URL', 'https://mpc.getswish.net/qrg-swish/api/v1/prefilled'),

];
