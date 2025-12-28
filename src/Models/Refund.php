<?php

namespace AndreasKviby\LaravelSwish\Models;

use AndreasKviby\LaravelSwish\Exceptions\ValidationException;
use Ramsey\Uuid\Uuid;

/**
 * Representerar en Swish-återbetalning
 */
class Refund
{
    /**
     * @var string Unikt ID för återbetalningen
     */
    public string $id;

    /**
     * @var string Referens till ursprunglig betalning
     */
    public string $originalPaymentReference;

    /**
     * @var string Betalarens alias (handlarens Swish-nummer)
     */
    public string $payerAlias;

    /**
     * @var string Mottagarens alias (kundens Swish-nummer)
     */
    public ?string $payeeAlias = null;

    /**
     * @var string Belopp att återbetala i SEK
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
     * Skapa en ny återbetalning
     *
     * @param array $data
     * @throws ValidationException
     */
    public function __construct(array $data = [])
    {
        $this->id = $data['id'] ?? (string) Uuid::uuid4();
        $this->originalPaymentReference = $data['originalPaymentReference'] ?? '';
        $this->payerAlias = $data['payerAlias'] ?? '';
        $this->payeeAlias = $data['payeeAlias'] ?? null;
        $this->amount = $data['amount'] ?? '';
        $this->currency = $data['currency'] ?? 'SEK';
        $this->message = $data['message'] ?? '';
        $this->callbackUrl = $data['callbackUrl'] ?? '';
        $this->payerPaymentReference = $data['payerPaymentReference'] ?? null;
    }

    /**
     * Validera återbetalning
     *
     * @throws ValidationException
     */
    public function validate(): void
    {
        if (empty($this->originalPaymentReference)) {
            throw new ValidationException('originalPaymentReference är obligatoriskt');
        }

        if (empty($this->payerAlias)) {
            throw new ValidationException('payerAlias är obligatoriskt');
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
            'originalPaymentReference' => $this->originalPaymentReference,
            'payerAlias' => $this->payerAlias,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'message' => $this->message,
            'callbackUrl' => $this->callbackUrl,
        ];

        if ($this->payeeAlias) {
            $data['payeeAlias'] = $this->payeeAlias;
        }

        if ($this->payerPaymentReference) {
            $data['payerPaymentReference'] = $this->payerPaymentReference;
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
