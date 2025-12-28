<?php

namespace AndreasKviby\LaravelSwish\Models;

use AndreasKviby\LaravelSwish\Exceptions\ValidationException;
use Ramsey\Uuid\Uuid;

/**
 * Representerar en Swish-betalningsbegäran
 */
class PaymentRequest
{
    /**
     * @var string Unikt ID för betalningen
     */
    public string $id;

    /**
     * @var string Betalarens Swish-nummer (valfritt för e-handel)
     */
    public ?string $payerAlias = null;

    /**
     * @var string Mottagarens Swish-nummer (ditt företags nummer)
     */
    public string $payeeAlias;

    /**
     * @var string Belopp i SEK (format: "100.00")
     */
    public string $amount;

    /**
     * @var string Valuta (alltid "SEK" för Swish)
     */
    public string $currency = 'SEK';

    /**
     * @var string Meddelande till betalaren (max 50 tecken)
     */
    public string $message;

    /**
     * @var string URL som Swish anropar när betalning är klar
     */
    public string $callbackUrl;

    /**
     * @var string|null Betalningens referens hos handlaren
     */
    public ?string $payerPaymentReference = null;

    /**
     * @var string|null Mottagarens referens
     */
    public ?string $payeePaymentReference = null;

    /**
     * Skapa en ny betalningsbegäran
     *
     * @param array $data
     * @throws ValidationException
     */
    public function __construct(array $data = [])
    {
        $this->id = $data['id'] ?? (string) Uuid::uuid4();
        $this->payerAlias = $data['payerAlias'] ?? null;
        $this->payeeAlias = $data['payeeAlias'] ?? '';
        $this->amount = $data['amount'] ?? '';
        $this->currency = $data['currency'] ?? 'SEK';
        $this->message = $data['message'] ?? '';
        $this->callbackUrl = $data['callbackUrl'] ?? '';
        $this->payerPaymentReference = $data['payerPaymentReference'] ?? null;
        $this->payeePaymentReference = $data['payeePaymentReference'] ?? null;
    }

    /**
     * Validera betalningsbegäran
     *
     * @throws ValidationException
     */
    public function validate(): void
    {
        if (empty($this->payeeAlias)) {
            throw new ValidationException('payeeAlias är obligatoriskt');
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

        if ($this->currency !== 'SEK') {
            throw new ValidationException('currency måste vara SEK för Swish');
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
            'payeeAlias' => $this->payeeAlias,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'message' => $this->message,
            'callbackUrl' => $this->callbackUrl,
        ];

        if ($this->payerAlias) {
            $data['payerAlias'] = $this->payerAlias;
        }

        if ($this->payerPaymentReference) {
            $data['payerPaymentReference'] = $this->payerPaymentReference;
        }

        if ($this->payeePaymentReference) {
            $data['payeePaymentReference'] = $this->payeePaymentReference;
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
