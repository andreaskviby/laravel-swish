<?php

namespace AndreasKviby\LaravelSwish\Tests\Unit;

use AndreasKviby\LaravelSwish\Exceptions\ValidationException;
use AndreasKviby\LaravelSwish\Models\PaymentRequest;
use PHPUnit\Framework\TestCase;

class PaymentRequestTest extends TestCase
{
    public function test_can_create_payment_request()
    {
        $payment = new PaymentRequest([
            'payeeAlias' => '1234679304',
            'amount' => '100.00',
            'currency' => 'SEK',
            'message' => 'Test betalning',
            'callbackUrl' => 'https://example.com/callback',
        ]);

        $this->assertEquals('1234679304', $payment->payeeAlias);
        $this->assertEquals('100.00', $payment->amount);
        $this->assertEquals('SEK', $payment->currency);
        $this->assertEquals('Test betalning', $payment->message);
    }

    public function test_validates_required_fields()
    {
        $this->expectException(ValidationException::class);

        $payment = new PaymentRequest([
            'amount' => '100.00',
            // saknar payeeAlias
        ]);

        $payment->validate();
    }

    public function test_validates_amount_is_positive()
    {
        $this->expectException(ValidationException::class);

        $payment = new PaymentRequest([
            'payeeAlias' => '1234679304',
            'amount' => '-100.00',
            'message' => 'Test',
            'callbackUrl' => 'https://example.com/callback',
        ]);

        $payment->validate();
    }

    public function test_validates_message_length()
    {
        $this->expectException(ValidationException::class);

        $payment = new PaymentRequest([
            'payeeAlias' => '1234679304',
            'amount' => '100.00',
            'message' => str_repeat('a', 51), // 51 tecken, max är 50
            'callbackUrl' => 'https://example.com/callback',
        ]);

        $payment->validate();
    }

    public function test_validates_callback_url()
    {
        $this->expectException(ValidationException::class);

        $payment = new PaymentRequest([
            'payeeAlias' => '1234679304',
            'amount' => '100.00',
            'message' => 'Test',
            'callbackUrl' => 'not-a-valid-url',
        ]);

        $payment->validate();
    }

    public function test_to_array_returns_correct_structure()
    {
        $payment = new PaymentRequest([
            'payeeAlias' => '1234679304',
            'amount' => '100.00',
            'currency' => 'SEK',
            'message' => 'Test betalning',
            'callbackUrl' => 'https://example.com/callback',
            'payerAlias' => '46701234567',
        ]);

        $array = $payment->toArray();

        $this->assertArrayHasKey('payeeAlias', $array);
        $this->assertArrayHasKey('amount', $array);
        $this->assertArrayHasKey('currency', $array);
        $this->assertArrayHasKey('message', $array);
        $this->assertArrayHasKey('callbackUrl', $array);
        $this->assertArrayHasKey('payerAlias', $array);
    }

    public function test_generates_uuid_automatically()
    {
        $payment = new PaymentRequest([
            'payeeAlias' => '1234679304',
            'amount' => '100.00',
            'message' => 'Test',
            'callbackUrl' => 'https://example.com/callback',
        ]);

        $this->assertNotEmpty($payment->id);
        $this->assertIsString($payment->id);
    }
}
