<?php

namespace Modules\VasAccounting\Tests\Feature;

use Illuminate\Support\Collection;
use Tests\TestCase;

class VasAccountingPublicPageRenderTest extends TestCase
{
    public function test_public_invoice_page_renders_with_metronic_guest_layout(): void
    {
        $invoice = (object) [
            'id' => 101,
            'reference' => 'INV-2026-0001',
            'voucher_no' => 'VCH-2026-0001',
            'document_date' => now(),
            'currency_code' => 'VND',
            'lines' => new Collection([
                (object) [
                    'line_no' => 1,
                    'account' => (object) ['account_code' => '1111', 'account_name' => 'Cash'],
                    'description' => 'Sales line',
                    'debit' => 150.25,
                    'credit' => 0,
                ],
            ]),
        ];

        $html = view('vasaccounting::public.show_invoice', [
            'title' => 'Public invoice',
            'invoice' => $invoice,
            'business' => (object) [
                'name' => 'Demo Business',
                'logo' => null,
                'tax_label_1' => 'Tax',
                'tax_number_1' => 'TX-001',
                'tax_label_2' => null,
                'tax_number_2' => null,
            ],
            'location' => (object) [
                'name' => 'HQ',
                'landmark' => 'Main Street',
                'city' => 'Ho Chi Minh',
                'state' => null,
                'country' => 'Vietnam',
            ],
            'payment_link' => 'https://example.com/pay',
            'outstandingAmount' => 150.25,
        ])->render();

        $this->assertStringContainsString('assets/plugins/global/plugins.bundle.css', $html);
        $this->assertStringContainsString('container-xxl py-10', $html);
        $this->assertStringContainsString('id="print_invoice"', $html);
        $this->assertStringContainsString('Demo Business', $html);
        $this->assertStringContainsString('INV-2026-0001', $html);
    }

    public function test_guest_payment_form_renders_gateway_and_amount_panels(): void
    {
        $invoice = (object) [
            'id' => 202,
            'reference' => 'INV-2026-0002',
            'voucher_no' => 'VCH-2026-0002',
        ];

        $html = view('vasaccounting::public.guest_payment_form', [
            'title' => 'Guest payment',
            'token' => 'test-token',
            'invoice' => $invoice,
            'business' => (object) ['name' => 'Demo Business', 'logo' => null],
            'business_details' => (object) ['currency_code' => 'VND'],
            'location' => (object) ['name' => 'HQ', 'landmark' => null, 'city' => 'Da Nang', 'state' => null, 'country' => 'Vietnam'],
            'contact' => (object) ['supplier_business_name' => 'ACME', 'name' => 'ACME'],
            'date_formatted' => now()->format('d/m/Y'),
            'total_amount' => '1,000.00',
            'total_paid' => '200.00',
            'total_payable' => 800.00,
            'total_payable_formatted' => '800.00',
            'pos_settings' => [
                'razor_pay_key_id' => 'rzp_test_123',
                'stripe_public_key' => 'pk_test_123',
                'stripe_secret_key' => 'sk_test_123',
            ],
        ])->render();

        $this->assertStringContainsString('layouts.guest_metronic', file_get_contents(module_path('VasAccounting', 'Resources/views/public/guest_payment_form.blade.php')));
        $this->assertStringContainsString('assets/plugins/global/plugins.bundle.css', $html);
        $this->assertStringContainsString('checkout.razorpay.com/v1/checkout.js', $html);
        $this->assertStringContainsString('checkout.stripe.com/checkout.js', $html);
        $this->assertStringContainsString('confirm-payment/vas-202', $html);
    }
}
