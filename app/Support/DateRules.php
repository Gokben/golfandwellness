<?php
namespace App\Support;
use Illuminate\Validation\ValidationException;
final class DateRules {
    public static function validate(array $rows): void {
        foreach ($rows as $key => $value) {
            if ($key === 'sourceDateIssue') continue;
            if (is_array($value)) { self::validate($value); continue; }
            if (in_array($key, ['allotment','guarantee'], true) && $value !== '' && $value !== null && !preg_match('/^[0-9]{1,4}$/D', (string)$value)) {
                throw ValidationException::withMessages(['records' => 'Kontenjan ve Garanti Oda en fazla dört rakam olmalıdır (0–9999).']);
            }
            if (!is_string($value) || $value === '') continue;
            if (preg_match('/(?:Date$|^date$|^checkIn$|^checkOut$|^closedFrom$|^closedTo$|^buggyFrom$|^buggyTo$)/', (string)$key)) {
                if (!preg_match('/^[12][0-9]{3}-[0-9]{2}-[0-9]{2}$/D', $value) || !checkdate((int)substr($value,5,2),(int)substr($value,8,2),(int)substr($value,0,4))) {
                    throw ValidationException::withMessages(['records' => 'Geçerli bir tarih girin. Yıl 1 veya 2 ile başlayan dört rakam olmalıdır.']);
                }
            }
        }
        foreach ([['firstDate','lastDate'],['validityFirstDate','validityLastDate'],['checkIn','checkOut']] as [$start,$end]) {
            if (!empty($rows[$start]) && !empty($rows[$end]) && $rows[$start] > $rows[$end]) throw ValidationException::withMessages(['records' => ($start === 'validityFirstDate' ? 'Geçerlilik başlangıcı geçerlilik bitişinden büyük olamaz.' : 'İlk tarih son tarihten büyük olamaz.')]);
        }
    }
}
