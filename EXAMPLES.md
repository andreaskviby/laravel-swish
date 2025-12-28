# Användningsexempel - Laravel Swish

Detta dokument innehåller praktiska exempel på hur du använder Laravel Swish-paketet i olika scenarier.

## Innehåll

1. [E-handelsflöde](#e-handelsflöde)
2. [Prenumerationstjänst](#prenumerationstjänst)
3. [Event-biljetter](#event-biljetter)
4. [Delbetalningar](#delbetalningar)
5. [API-endpoint för mobilapp](#api-endpoint-för-mobilapp)

## E-handelsflöde

### 1. OrderController - Skapa order och betalning

```php
<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Cart;
use Illuminate\Http\Request;
use AndreasKviby\LaravelSwish\Facades\Swish;
use AndreasKviby\LaravelSwish\Models\PaymentRequest;

class OrderController extends Controller
{
    public function checkout(Request $request)
    {
        $cart = Cart::where('user_id', auth()->id())->with('items')->first();
        
        if (!$cart || $cart->items->isEmpty()) {
            return redirect()->route('cart.index')
                ->with('error', 'Din varukorg är tom');
        }
        
        // Skapa order
        $order = Order::create([
            'user_id' => auth()->id(),
            'total' => $cart->total,
            'status' => 'pending',
            'items' => $cart->items->toArray(),
        ]);
        
        // Skapa Swish-betalning
        $payment = new PaymentRequest([
            'payeeAlias' => config('swish.payee_alias'),
            'amount' => number_format($order->total, 2, '.', ''),
            'currency' => 'SEK',
            'message' => "Beställning #{$order->id}",
            'callbackUrl' => route('swish.callback', ['order' => $order->id]),
        ]);
        
        try {
            $response = Swish::createPaymentRequest($payment);
            
            // Spara betalningsinfo
            $order->update([
                'payment_id' => $payment->id,
                'payment_token' => $response['token'] ?? null,
            ]);
            
            // Töm kundvagn
            $cart->items()->delete();
            
            return view('orders.payment', [
                'order' => $order,
                'qrCode' => Swish::generateQRCode($response['token']),
                'isMobile' => $request->header('User-Agent') && 
                    preg_match('/Mobile|Android|iPhone/i', $request->header('User-Agent')),
            ]);
            
        } catch (\Exception $e) {
            $order->delete();
            
            return back()->with('error', 'Kunde inte skapa betalning: ' . $e->getMessage());
        }
    }
    
    public function checkPaymentStatus($orderId)
    {
        $order = Order::findOrFail($orderId);
        
        if (!$order->payment_id) {
            return response()->json(['status' => 'pending']);
        }
        
        try {
            $payment = Swish::getPaymentRequest($order->payment_id);
            
            return response()->json([
                'status' => strtolower($payment['status']),
                'paid' => $payment['status'] === 'PAID',
                'message' => $this->getStatusMessage($payment['status']),
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Kunde inte hämta betalningsstatus'
            ], 500);
        }
    }
    
    private function getStatusMessage($status): string
    {
        return match($status) {
            'CREATED' => 'Väntar på betalning...',
            'PAID' => 'Betalning genomförd!',
            'DECLINED' => 'Betalning nekades',
            'ERROR' => 'Ett fel uppstod',
            'CANCELLED' => 'Betalning avbruten',
            default => 'Okänd status',
        };
    }
}
```

### 2. SwishCallbackController - Hantera callbacks

```php
<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use AndreasKviby\LaravelSwish\Facades\Swish;
use App\Jobs\SendOrderConfirmationEmail;
use App\Jobs\ProcessShipping;

class SwishCallbackController extends Controller
{
    public function handlePaymentCallback($orderId)
    {
        $order = Order::findOrFail($orderId);
        
        if ($order->status === 'paid') {
            return response()->json(['status' => 'already_processed']);
        }
        
        try {
            $payment = Swish::getPaymentRequest($order->payment_id);
            
            \Log::info('Swish callback', [
                'order_id' => $order->id,
                'payment_status' => $payment['status'],
            ]);
            
            if ($payment['status'] === 'PAID') {
                $order->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                    'payer_alias' => $payment['payerAlias'] ?? null,
                ]);
                
                // Skicka bekräftelse och starta leveransprocess
                SendOrderConfirmationEmail::dispatch($order);
                ProcessShipping::dispatch($order);
            } 
            elseif ($payment['status'] === 'DECLINED') {
                $order->update(['status' => 'declined']);
            }
            elseif ($payment['status'] === 'ERROR') {
                $order->update(['status' => 'error']);
            }
            
            return response()->json(['status' => 'success']);
            
        } catch (\Exception $e) {
            \Log::error('Swish callback error', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json(['status' => 'error'], 500);
        }
    }
}
```

### 3. Blade-template för betalningssida

```blade
{{-- resources/views/orders/payment.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3>Slutför din betalning</h3>
                </div>
                
                <div class="card-body text-center">
                    <h4>Order #{{ $order->id }}</h4>
                    <p class="lead">Totalt: {{ number_format($order->total, 2) }} SEK</p>
                    
                    <div id="payment-status" class="alert alert-info">
                        Väntar på betalning...
                    </div>
                    
                    @if($isMobile)
                        <a href="swish://paymentrequest?token={{ $order->payment_token }}" 
                           class="btn btn-primary btn-lg">
                            Öppna Swish-appen
                        </a>
                    @else
                        <div class="qr-code-container">
                            <p>Skanna QR-koden med din Swish-app:</p>
                            <img src="{{ $qrCode }}" alt="Swish QR-kod" class="img-fluid" style="max-width: 300px;">
                        </div>
                    @endif
                    
                    <div class="spinner-border text-primary mt-4" role="status" id="loading-spinner">
                        <span class="sr-only">Laddar...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Polling för betalningsstatus
let pollInterval;

function checkPaymentStatus() {
    fetch('{{ route("order.payment.status", $order->id) }}')
        .then(response => response.json())
        .then(data => {
            const statusDiv = document.getElementById('payment-status');
            const spinner = document.getElementById('loading-spinner');
            
            if (data.paid) {
                clearInterval(pollInterval);
                statusDiv.className = 'alert alert-success';
                statusDiv.textContent = '✓ Betalning genomförd!';
                spinner.style.display = 'none';
                
                setTimeout(() => {
                    window.location.href = '{{ route("order.confirmation", $order->id) }}';
                }, 2000);
            } 
            else if (data.status === 'declined') {
                clearInterval(pollInterval);
                statusDiv.className = 'alert alert-danger';
                statusDiv.textContent = 'Betalning nekades. Försök igen.';
                spinner.style.display = 'none';
            }
            else if (data.status === 'error') {
                clearInterval(pollInterval);
                statusDiv.className = 'alert alert-danger';
                statusDiv.textContent = 'Ett fel uppstod. Kontakta kundservice.';
                spinner.style.display = 'none';
            }
        })
        .catch(error => {
            console.error('Error checking payment status:', error);
        });
}

// Starta polling när sidan laddas
document.addEventListener('DOMContentLoaded', function() {
    // Kolla direkt
    checkPaymentStatus();
    
    // Sedan var 2:a sekund
    pollInterval = setInterval(checkPaymentStatus, 2000);
    
    // Stoppa efter 5 minuter
    setTimeout(() => {
        clearInterval(pollInterval);
        const statusDiv = document.getElementById('payment-status');
        statusDiv.className = 'alert alert-warning';
        statusDiv.textContent = 'Betalningen tog för lång tid. Uppdatera sidan för att försöka igen.';
    }, 300000);
});
</script>
@endpush
```

## Prenumerationstjänst

```php
<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\Request;
use AndreasKviby\LaravelSwish\Facades\Swish;
use AndreasKviby\LaravelSwish\Models\PaymentRequest;

class SubscriptionController extends Controller
{
    public function subscribe(Request $request)
    {
        $plan = $request->input('plan'); // 'monthly' or 'yearly'
        $amount = $plan === 'yearly' ? '999.00' : '99.00';
        
        $subscription = Subscription::create([
            'user_id' => auth()->id(),
            'plan' => $plan,
            'amount' => $amount,
            'status' => 'pending',
            'starts_at' => now(),
            'ends_at' => now()->addMonths($plan === 'yearly' ? 12 : 1),
        ]);
        
        $payment = new PaymentRequest([
            'payeeAlias' => config('swish.payee_alias'),
            'amount' => $amount,
            'currency' => 'SEK',
            'message' => "Prenumeration {$plan}",
            'callbackUrl' => route('subscription.callback', ['subscription' => $subscription->id]),
        ]);
        
        $response = Swish::createPaymentRequest($payment);
        
        $subscription->update(['payment_id' => $payment->id]);
        
        return view('subscription.payment', [
            'subscription' => $subscription,
            'qrCode' => Swish::generateQRCode($response['token']),
        ]);
    }
    
    public function handleCallback($subscriptionId)
    {
        $subscription = Subscription::findOrFail($subscriptionId);
        
        $payment = Swish::getPaymentRequest($subscription->payment_id);
        
        if ($payment['status'] === 'PAID') {
            $subscription->update([
                'status' => 'active',
                'activated_at' => now(),
            ]);
            
            // Ge användaren tillgång till premium-funktioner
            $subscription->user->update(['is_premium' => true]);
        }
        
        return response()->json(['status' => 'success']);
    }
}
```

## Event-biljetter

```php
<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Ticket;
use Illuminate\Http\Request;
use AndreasKviby\LaravelSwish\Facades\Swish;
use AndreasKviby\LaravelSwish\Models\PaymentRequest;

class TicketController extends Controller
{
    public function purchase(Request $request, Event $event)
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:1|max:10',
            'email' => 'required|email',
        ]);
        
        // Kontrollera tillgänglighet
        if ($event->tickets_available < $validated['quantity']) {
            return back()->with('error', 'Inte tillräckligt med biljetter tillgängliga');
        }
        
        $total = $event->price * $validated['quantity'];
        
        // Skapa biljetter (med pending status)
        $tickets = [];
        for ($i = 0; $i < $validated['quantity']; $i++) {
            $tickets[] = Ticket::create([
                'event_id' => $event->id,
                'email' => $validated['email'],
                'price' => $event->price,
                'status' => 'pending',
                'code' => \Str::random(10),
            ]);
        }
        
        $ticketIds = collect($tickets)->pluck('id')->implode(',');
        
        $payment = new PaymentRequest([
            'payeeAlias' => config('swish.payee_alias'),
            'amount' => number_format($total, 2, '.', ''),
            'currency' => 'SEK',
            'message' => "{$event->name} - {$validated['quantity']} biljetter",
            'callbackUrl' => route('ticket.callback', ['ids' => $ticketIds]),
        ]);
        
        $response = Swish::createPaymentRequest($payment);
        
        // Spara payment ID på första biljetten
        $tickets[0]->update(['payment_id' => $payment->id]);
        
        return view('tickets.payment', [
            'event' => $event,
            'tickets' => $tickets,
            'qrCode' => Swish::generateQRCode($response['token']),
        ]);
    }
    
    public function handleCallback(Request $request)
    {
        $ticketIds = explode(',', $request->input('ids'));
        $tickets = Ticket::whereIn('id', $ticketIds)->get();
        
        $firstTicket = $tickets->first();
        $payment = Swish::getPaymentRequest($firstTicket->payment_id);
        
        if ($payment['status'] === 'PAID') {
            foreach ($tickets as $ticket) {
                $ticket->update([
                    'status' => 'confirmed',
                    'purchased_at' => now(),
                ]);
            }
            
            // Skicka biljetter via email
            \Mail::to($firstTicket->email)->send(
                new \App\Mail\TicketsPurchased($tickets)
            );
        }
        
        return response()->json(['status' => 'success']);
    }
}
```

## Delbetalningar

```php
<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Illuminate\Http\Request;
use AndreasKviby\LaravelSwish\Facades\Swish;
use AndreasKviby\LaravelSwish\Models\PaymentRequest;

class InvoiceController extends Controller
{
    public function payPartial(Request $request, Invoice $invoice)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1|max:' . $invoice->remaining_amount,
        ]);
        
        $payment = new PaymentRequest([
            'payeeAlias' => config('swish.payee_alias'),
            'amount' => number_format($validated['amount'], 2, '.', ''),
            'currency' => 'SEK',
            'message' => "Delbetalning faktura #{$invoice->number}",
            'callbackUrl' => route('invoice.callback', [
                'invoice' => $invoice->id,
                'amount' => $validated['amount'],
            ]),
        ]);
        
        $response = Swish::createPaymentRequest($payment);
        
        // Spara delbetalning
        $invoice->partialPayments()->create([
            'payment_id' => $payment->id,
            'amount' => $validated['amount'],
            'status' => 'pending',
        ]);
        
        return view('invoice.payment', [
            'invoice' => $invoice,
            'amount' => $validated['amount'],
            'qrCode' => Swish::generateQRCode($response['token']),
        ]);
    }
    
    public function handleCallback(Invoice $invoice, Request $request)
    {
        $amount = $request->input('amount');
        $partialPayment = $invoice->partialPayments()
            ->where('amount', $amount)
            ->where('status', 'pending')
            ->latest()
            ->first();
        
        if (!$partialPayment) {
            return response()->json(['status' => 'not_found'], 404);
        }
        
        $payment = Swish::getPaymentRequest($partialPayment->payment_id);
        
        if ($payment['status'] === 'PAID') {
            $partialPayment->update([
                'status' => 'paid',
                'paid_at' => now(),
            ]);
            
            // Uppdatera faktura
            $invoice->paid_amount += $amount;
            
            if ($invoice->paid_amount >= $invoice->total_amount) {
                $invoice->status = 'paid';
            } else {
                $invoice->status = 'partially_paid';
            }
            
            $invoice->save();
        }
        
        return response()->json(['status' => 'success']);
    }
}
```

## API-endpoint för mobilapp

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use AndreasKviby\LaravelSwish\Facades\Swish;
use AndreasKviby\LaravelSwish\Models\PaymentRequest;

class PaymentApiController extends Controller
{
    /**
     * Skapa betalning via API
     */
    public function createPayment(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
            'description' => 'required|string|max:50',
            'phone' => 'nullable|string', // För M-Commerce
        ]);
        
        $order = Order::create([
            'user_id' => auth()->id(),
            'amount' => $validated['amount'],
            'description' => $validated['description'],
            'status' => 'pending',
        ]);
        
        $payment = new PaymentRequest([
            'payeeAlias' => config('swish.payee_alias'),
            'amount' => number_format($validated['amount'], 2, '.', ''),
            'currency' => 'SEK',
            'message' => $validated['description'],
            'callbackUrl' => route('api.payment.callback', ['order' => $order->id]),
        ]);
        
        // Lägg till telefonnummer för M-Commerce om angivet
        if (!empty($validated['phone'])) {
            $payment->payerAlias = $validated['phone'];
        }
        
        try {
            $response = Swish::createPaymentRequest($payment);
            
            $order->update([
                'payment_id' => $payment->id,
                'payment_token' => $response['token'] ?? null,
            ]);
            
            return response()->json([
                'success' => true,
                'order_id' => $order->id,
                'payment_id' => $payment->id,
                'swish_url' => $response['location'] ?? null,
                'qr_code_url' => isset($response['token']) 
                    ? Swish::generateQRCode($response['token']) 
                    : null,
            ]);
            
        } catch (\Exception $e) {
            $order->delete();
            
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
    
    /**
     * Hämta betalningsstatus
     */
    public function getPaymentStatus($orderId)
    {
        $order = Order::findOrFail($orderId);
        
        if (!$order->payment_id) {
            return response()->json([
                'status' => 'pending',
                'paid' => false,
            ]);
        }
        
        try {
            $payment = Swish::getPaymentRequest($order->payment_id);
            
            return response()->json([
                'status' => strtolower($payment['status']),
                'paid' => $payment['status'] === 'PAID',
                'amount' => $payment['amount'] ?? null,
                'date_created' => $payment['dateCreated'] ?? null,
                'date_paid' => $payment['datePaid'] ?? null,
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Kunde inte hämta status',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Hantera callback
     */
    public function handleCallback($orderId)
    {
        $order = Order::findOrFail($orderId);
        
        $payment = Swish::getPaymentRequest($order->payment_id);
        
        if ($payment['status'] === 'PAID') {
            $order->update([
                'status' => 'paid',
                'paid_at' => now(),
            ]);
            
            // Skicka push-notis till appen om betalning lyckades
            // NotificationService::send($order->user_id, 'Betalning genomförd!');
        }
        
        return response()->json(['status' => 'success']);
    }
}
```

### Routes för API

```php
// routes/api.php

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/payments', [PaymentApiController::class, 'createPayment']);
    Route::get('/payments/{order}/status', [PaymentApiController::class, 'getPaymentStatus']);
});

Route::post('/payments/{order}/callback', [PaymentApiController::class, 'handleCallback']);
```

---

## Testning av exempel

För att testa exemplen ovan:

1. Sätt upp testmiljön enligt dokumentationen
2. Använd testnummer `46701234567` som godkänner betalningar
3. Övervaka logs med `tail -f storage/logs/laravel.log`
4. Testa callbacks lokalt med ngrok eller liknande

## Ytterligare resurser

- Se `README.md` för komplett API-dokumentation
- Besök [Swish Developer Portal](https://developer.swish.nu/) för mer information
