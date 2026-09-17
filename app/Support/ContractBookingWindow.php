<?php
namespace App\Support;

use Illuminate\Validation\ValidationException;

class ContractBookingWindow
{
    public static function validate(array $hotels, array $before, array $records, string $today): void
    {
        $existing = array_column($before, null, 'id');
        foreach ($records as $record) {
            if (empty($record['hotelContract'])) continue;
            $previous = $existing[$record['id']] ?? null;
            if ($previous && ($previous['hotel'] ?? '') === ($record['hotel'] ?? '') && ($previous['hotelContract'] ?? '') === $record['hotelContract']) continue;
            foreach ($hotels as $hotel) {
                if ((string) $hotel['id'] !== (string) ($record['hotel'] ?? '')) continue;
                foreach ($hotel['details']['contracts'] ?? [] as $contract) {
                    if ($contract['id'] !== $record['hotelContract']) continue;
                    $first = $contract['bookingFirstDate'] ?? '';
                    $last = $contract['bookingLastDate'] ?? '';
                    if (!$first && !$last) continue;
                    if (!$first || !$last || $today < $first || $today > $last) {
                        throw ValidationException::withMessages(['records'=>'Rezervasyonun oluşturulduğu tarih ana kontratın geçerlilik aralığı dışında. Bu kontratla yeni rezervasyon oluşturulamaz.']);
                    }
                }
            }
        }
    }
}
