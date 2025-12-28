<?php

namespace AndreasKviby\LaravelSwish;

use AndreasKviby\LaravelSwish\Exceptions\ApiException;
use AndreasKviby\LaravelSwish\Models\PaymentRequest;
use AndreasKviby\LaravelSwish\Models\Refund;
use AndreasKviby\LaravelSwish\Models\Payout;
use AndreasKviby\LaravelSwish\Support\Certificate;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;

/**
 * Huvudklass för Swish API-integration
 */
class SwishClient
{
    /**
     * @var string Produktions-endpoint
     */
    public const PRODUCTION_ENDPOINT = 'https://cpc.getswish.net/swish-cpcapi/api';

    /**
     * @var string Test-endpoint (Merchant Swish Simulator)
     */
    public const TEST_ENDPOINT = 'https://mss.cpc.getswish.net/swish-cpcapi/api';

    /**
     * @var Client Guzzle HTTP-klient
     */
    protected Client $client;

    /**
     * @var Certificate Swish-certifikat
     */
    protected Certificate $certificate;

    /**
     * @var string API-endpoint
     */
    protected string $endpoint;

    /**
     * @var bool Verifiera SSL
     */
    protected bool $verifySSL;

    /**
     * Skapa en ny SwishClient-instans
     *
     * @param Certificate $certificate
     * @param string $endpoint
     * @param bool $verifySSL
     * @param int $timeout
     */
    public function __construct(
        Certificate $certificate,
        string $endpoint = self::TEST_ENDPOINT,
        bool $verifySSL = true,
        int $timeout = 30
    ) {
        $this->certificate = $certificate;
        $this->endpoint = rtrim($endpoint, '/');
        $this->verifySSL = $verifySSL;

        $config = array_merge(
            [
                'timeout' => $timeout,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ],
                'http_errors' => false,
            ],
            $this->certificate->toGuzzleConfig()
        );

        if (!$this->verifySSL) {
            $config['verify'] = false;
        }

        $this->client = new Client($config);
    }

    /**
     * Skapa en betalningsbegäran
     *
     * @param PaymentRequest $paymentRequest
     * @return array
     * @throws ApiException
     */
    public function createPaymentRequest(PaymentRequest $paymentRequest): array
    {
        $paymentRequest->validate();

        $response = $this->request(
            'PUT',
            "/v2/paymentrequests/{$paymentRequest->id}",
            $paymentRequest->toArray()
        );

        return $this->parseResponse($response);
    }

    /**
     * Hämta status för en betalning
     *
     * @param string $id Betalnings-ID
     * @return array
     * @throws ApiException
     */
    public function getPaymentRequest(string $id): array
    {
        $response = $this->request('GET', "/v1/paymentrequests/{$id}");

        return $this->parseResponse($response);
    }

    /**
     * Avbryt en betalning (endast före den är betald)
     *
     * @param string $id Betalnings-ID
     * @return array
     * @throws ApiException
     */
    public function cancelPaymentRequest(string $id): array
    {
        $response = $this->request('PATCH', "/v1/paymentrequests/{$id}", [
            'op' => 'cancel',
            'path' => '/status',
            'value' => 'cancelled'
        ]);

        return $this->parseResponse($response);
    }

    /**
     * Skapa en återbetalning
     *
     * @param Refund $refund
     * @return array
     * @throws ApiException
     */
    public function createRefund(Refund $refund): array
    {
        $refund->validate();

        $response = $this->request(
            'PUT',
            "/v2/refunds/{$refund->id}",
            $refund->toArray()
        );

        return $this->parseResponse($response);
    }

    /**
     * Hämta status för en återbetalning
     *
     * @param string $id Återbetalnings-ID
     * @return array
     * @throws ApiException
     */
    public function getRefund(string $id): array
    {
        $response = $this->request('GET', "/v1/refunds/{$id}");

        return $this->parseResponse($response);
    }

    /**
     * Skapa en utbetalning
     *
     * @param Payout $payout
     * @return array
     * @throws ApiException
     */
    public function createPayout(Payout $payout): array
    {
        $payout->validate();

        $response = $this->request(
            'PUT',
            "/v2/payouts/{$payout->id}",
            $payout->toArray()
        );

        return $this->parseResponse($response);
    }

    /**
     * Hämta status för en utbetalning
     *
     * @param string $id Utbetalnings-ID
     * @return array
     * @throws ApiException
     */
    public function getPayout(string $id): array
    {
        $response = $this->request('GET', "/v1/payouts/{$id}");

        return $this->parseResponse($response);
    }

    /**
     * Generera QR-kod data för en betalning
     *
     * @param string $token Betalnings-token från Location header
     * @param string $format Format: 'svg' eller 'png'
     * @param int $size Storlek på QR-kod (endast för png)
     * @return string QR-kod data (URL för nedladdning eller SVG)
     */
    public function generateQRCode(string $token, string $format = 'svg', int $size = 300): string
    {
        $url = "https://mpc.getswish.net/qrg-swish/api/v1/prefilled";
        $params = [
            'token' => $token,
            'format' => $format,
        ];

        if ($format === 'png') {
            $params['size'] = $size;
        }

        return $url . '?' . http_build_query($params);
    }

    /**
     * Gör ett HTTP-anrop till Swish API
     *
     * @param string $method HTTP-metod
     * @param string $uri URI-sökväg
     * @param array|null $data Request body
     * @return ResponseInterface
     * @throws ApiException
     */
    protected function request(string $method, string $uri, ?array $data = null): ResponseInterface
    {
        $url = $this->endpoint . $uri;

        $options = [];
        if ($data !== null) {
            $options['json'] = $data;
        }

        try {
            return $this->client->request($method, $url, $options);
        } catch (RequestException $e) {
            throw new ApiException(
                "Swish API-anrop misslyckades: {$e->getMessage()}",
                $e->getCode(),
                $e
            );
        } catch (GuzzleException $e) {
            throw new ApiException(
                "HTTP-fel vid Swish API-anrop: {$e->getMessage()}",
                0,
                $e
            );
        }
    }

    /**
     * Tolka API-svar
     *
     * @param ResponseInterface $response
     * @return array
     * @throws ApiException
     */
    protected function parseResponse(ResponseInterface $response): array
    {
        $statusCode = $response->getStatusCode();
        $body = (string) $response->getBody();

        // 201 Created - betalning/återbetalning/utbetalning skapad
        // 200 OK - hämtning lyckades
        // 204 No Content - avbrytning lyckades
        if (in_array($statusCode, [200, 201, 204])) {
            $data = [];
            
            if ($statusCode !== 204 && !empty($body)) {
                $data = json_decode($body, true) ?? [];
            }

            // Lägg till Location header om det finns (innehåller token för QR-kod)
            if ($response->hasHeader('Location')) {
                $location = $response->getHeader('Location')[0];
                $data['location'] = $location;
                // Extrahera token från Location header
                if (preg_match('/\/([^\/]+)$/', $location, $matches)) {
                    $data['token'] = $matches[1];
                }
            }

            // Lägg till PaymentRequestToken header om det finns
            if ($response->hasHeader('PaymentRequestToken')) {
                $data['paymentRequestToken'] = $response->getHeader('PaymentRequestToken')[0];
            }

            return $data;
        }

        // Hantera fel
        $errorData = json_decode($body, true);
        $errorMessage = 'Swish API returnerade ett fel';

        if (is_array($errorData)) {
            if (isset($errorData['errorMessage'])) {
                $errorMessage = $errorData['errorMessage'];
            } elseif (isset($errorData[0]['errorMessage'])) {
                $errorMessage = $errorData[0]['errorMessage'];
            }
        }

        $exception = new ApiException(
            "{$errorMessage} (HTTP {$statusCode})",
            $statusCode
        );

        if (is_array($errorData)) {
            $exception->setErrorDetails($errorData);
        }

        throw $exception;
    }
}
