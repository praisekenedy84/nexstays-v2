<?php

declare(strict_types=1);

namespace Tests\Feature\Finance;

use App\Domain\Shared\Mail\DailyReportSummaryMail;
use Illuminate\Support\Facades\Mail;
use Stancl\Tenancy\Middleware\InitializeTenancyBySubdomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;
use Tests\TenantTestCase;

class ReportDeliveryTest extends TenantTestCase
{
    public function test_reports_hub_shows_report_delivery_section(): void
    {
        $response = $this
            ->withoutMiddleware([
                InitializeTenancyBySubdomain::class,
                PreventAccessFromCentralDomains::class,
            ])
            ->actingAs($this->user, 'web')
            ->get(route('tenant.reports'));

        $response->assertOk();
        $response->assertSee('Daily report email setup');
        $response->assertSee('Send report email now');
    }

    public function test_send_now_dispatches_daily_report_email(): void
    {
        Mail::fake();
        config()->set('nexstay.reports.delivery.recipient_email', 'owner@example.com');

        $response = $this
            ->withoutMiddleware([
                InitializeTenancyBySubdomain::class,
                PreventAccessFromCentralDomains::class,
            ])
            ->actingAs($this->user, 'web')
            ->post(route('tenant.reports.delivery-settings.send-now'));

        $response->assertRedirect(route('tenant.reports'));
        $response->assertSessionHas('success');

        Mail::assertSent(DailyReportSummaryMail::class, function (DailyReportSummaryMail $mail): bool {
            return $mail->hasTo('owner@example.com');
        });
    }
}
