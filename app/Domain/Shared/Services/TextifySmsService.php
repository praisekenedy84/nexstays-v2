<?php

declare(strict_types=1);

namespace App\Domain\Shared\Services;

use App\Domain\HBMS\Models\Reservation;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class TextifySmsService
{
    public function sendReservationCreated(Reservation $reservation): void
    {
        $reservation->loadMissing(['guest', 'room']);

        $recipient = $this->normalizePhoneNumber((string) ($reservation->guest?->phone ?? ''));
        if ($recipient === null) {
            return;
        }

        $guestName = trim((string) ($reservation->guest?->first_name ?? '').' '.(string) ($reservation->guest?->last_name ?? ''));
        $roomNumber = (string) ($reservation->room?->room_number ?? '-');
        $checkIn = $reservation->check_in_date?->format('d M Y') ?? '-';
        $checkOut = $reservation->check_out_date?->format('d M Y') ?? '-';

        $content = sprintf(
            'Dear %s, your reservation %s for room %s from %s to %s has been confirmed. Thank you for choosing %s.',
            $guestName !== '' ? $guestName : 'Guest',
            $reservation->booking_ref,
            $roomNumber,
            $checkIn,
            $checkOut,
            $this->hotelName()
        );

        $this->sendSms($recipient, $content, (string) $reservation->id, 'created');
    }

    public function sendReservationCancelled(Reservation $reservation): void
    {
        $reservation->loadMissing(['guest', 'room']);

        $recipient = $this->normalizePhoneNumber((string) ($reservation->guest?->phone ?? ''));
        if ($recipient === null) {
            return;
        }

        $guestName = trim((string) ($reservation->guest?->first_name ?? '').' '.(string) ($reservation->guest?->last_name ?? ''));
        $content = sprintf(
            'Dear %s, your reservation %s has been cancelled. Please contact the front desk for assistance.',
            $guestName !== '' ? $guestName : 'Guest',
            $reservation->booking_ref
        );

        $this->sendSms($recipient, $content, (string) $reservation->id, 'cancelled');
    }

    private function hotelName(): string
    {
        if (tenancy()->initialized) {
            $name = trim((string) (tenancy()->tenant->name ?? ''));

            if ($name !== '') {
                return $name;
            }
        }

        return (string) config('app.name', 'NexStay');
    }

    private function sendSms(string $receiver, string $content, string $reservationId, string $event): void
    {
        $apiKey = (string) config('services.textify.api_key');
        $baseUrl = rtrim((string) config('services.textify.base_url', 'https://portal.textify.africa/api'), '/');

        // Per-tenant sender name takes priority; fall back to global config.
        $senderName = (string) (
            (tenancy()->initialized ? tenancy()->tenant->sms_sender_name : null)
            ?? config('services.textify.sender_name')
        );

        if ($apiKey === '' || $senderName === '') {
            return;
        }

        try {
            $response = Http::timeout(15)
                ->acceptJson()
                ->withHeaders(['Authorization' => $apiKey])
                ->post("{$baseUrl}/message/create", [
                    'sender_name' => $senderName,
                    'messages' => [
                        [
                            'receiver' => $receiver,
                            'content' => $content,
                        ],
                    ],
                    'is_scheduled' => false,
                    'scheduled_date' => null,
                ]);

            if (! $response->successful()) {
                Log::warning('Textify SMS request failed.', [
                    'reservation_id' => $reservationId,
                    'event' => $event,
                    'status' => $response->status(),
                    'response' => $response->json() ?? $response->body(),
                ]);
            }
        } catch (Throwable $e) {
            Log::error('Textify SMS request exception.', [
                'reservation_id' => $reservationId,
                'event' => $event,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function normalizePhoneNumber(string $rawPhone): ?string
    {
        $digits = preg_replace('/\D+/', '', $rawPhone);
        if ($digits === null || $digits === '') {
            return null;
        }

        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        $defaultCountryCode = preg_replace('/\D+/', '', (string) config('services.textify.default_country_code', '255')) ?: '255';
        if (str_starts_with($digits, '0')) {
            $digits = $defaultCountryCode.ltrim($digits, '0');
        }

        return $digits !== '' ? $digits : null;
    }
}
