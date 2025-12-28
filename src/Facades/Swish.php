<?php

namespace AndreasKviby\LaravelSwish\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static array createPaymentRequest(\AndreasKviby\LaravelSwish\Models\PaymentRequest $paymentRequest)
 * @method static array getPaymentRequest(string $id)
 * @method static array cancelPaymentRequest(string $id)
 * @method static array createRefund(\AndreasKviby\LaravelSwish\Models\Refund $refund)
 * @method static array getRefund(string $id)
 * @method static array createPayout(\AndreasKviby\LaravelSwish\Models\Payout $payout)
 * @method static array getPayout(string $id)
 * @method static string generateQRCode(string $token, string $format = 'svg', int $size = 300)
 *
 * @see \AndreasKviby\LaravelSwish\SwishClient
 */
class Swish extends Facade
{
    /**
     * Hämta namnet på facadens registrerade komponent
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'swish';
    }
}
