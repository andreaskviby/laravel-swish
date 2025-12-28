<?php

namespace AndreasKviby\LaravelSwish\Models;

use AndreasKviby\LaravelSwish\Exceptions\ValidationException;
use Ramsey\Uuid\Uuid;

/**
 * Representerar en Swish-utbetalning
 */
class Payout
{
    /**
     * @var string Unikt ID för utbetalningen
     */
    public string $id;

    /**
     * @var string Betalarens alias (handlarens Swish-nummer)
     */
    public string $payerAlias;

    /**
     * @var string Mottagarens alias (kundens Swish-nummer)
     */
    public string $payeeAlias;

    /**
     * @var string Belopp i SEK
     */
    public string $amount;

    /**
     * @var string Valuta (alltid "SEK")
     */
    public string $currency = 'SEK';

    /**
     * @var string Meddelande (max 50 tecken)
     */
    public string $message;

    /**
     * @var string Callback URL
     */
    public string $callbackUrl;

    /**
     * @var string|null Betalarens referens
     */
    public ?string $payerPaymentReference = null;

    /**
     * @var string|null Signeringscertifikat-seriell
     */
    public ?string $signingCertificateSerialNumber = null;

    /**
     * Skapa en ny utbetalning
     *
     * @param array $data
     * @throws ValidationException
     */
    public function __construct(array $data = [])
    {
        $this->id = $data['id'] ?? (string) Uuid::uuid4();
        $this->payerAlias = $data['payerAlias'] ?? '';
        $this->payeeAlias = $data['payeeAlias'] ?? '';
        $this->amount = $data['amount'] ?? '';
        $this->currency = $data['currency'] ?? 'SEK';
        $this->message = $data['message'] ?? '';
        $this->callbackUrl = $data['callbackUrl'] ?? '';
        $this->payerPaymentReference = $data['payerPaymentReference'] ?? null;
        $this->signingCertificateSerialNumber = $data['signingCertificateSerialNumber'] ?? null;

        if (!empty($data)) {
            $this->validate();
        }
    }

    /**
     * Validera utbetalning
     *
     * @throws ValidationException
     */
    public function validate(): void
    {
        if (empty($this->payerAlias)) {
            throw new ValidationException('payerAlias är obligatoriskt');
        }

        if (empty($this->payeeAlias)) {
            throw new ValidationException('payeeAlias är obligatoriskt (mottagarens Swish-nummer)');
        }

        if (empty($this->amount)) {
            throw new ValidationException('amount är obligatoriskt');
        }

        if (!is_numeric($this->amount) || (float) $this->amount <= 0) {
            throw new ValidationException('amount måste vara ett positivt tal');
        }

        if (empty($this->message)) {
            throw new ValidationException('message är obligatoriskt');
        }

        if (strlen($this->message) > 50) {
            throw new ValidationException('message får max vara 50 tecken');
        }

        if (empty($this->callbackUrl)) {
            throw new ValidationException('callbackUrl är obligatoriskt');
        }

        if (!filter_var($this->callbackUrl, FILTER_VALIDATE_URL)) {
            throw new ValidationException('callbackUrl måste vara en giltig URL');
        }
    }

    /**
     * Konvertera till array för API-anrop
     *
     * @return array
     */
    public function toArray(): array
    {
        $data = [
            'payerAlias' => $this->payerAlias,
            'payeeAlias' => $this->payeeAlias,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'message' => $this->message,
            'callbackUrl' => $this->callbackUrl,
        ];

        if ($this->payerPaymentReference) {
            $data['payerPaymentReference'] = $this->payerPaymentReference;
        }

        if ($this->signingCertificateSerialNumber) {
            $data['signingCertificateSerialNumber'] = $this->signingCertificateSerialNumber;
        }

        return $data;
    }

    /**
     * Skapa från API-svar
     *
     * @param array $response
     * @return self
     */
    public static function fromResponse(array $response): self
    {
        return new self($response);
    }
}
