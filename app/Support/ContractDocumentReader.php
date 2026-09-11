<?php

namespace App\Support;

use DOMDocument;
use DOMXPath;
use Illuminate\Validation\ValidationException;
use ZipArchive;

final class ContractDocumentReader
{
    public function read(string $bytes, string $extension): string
    {
        $path = tempnam(sys_get_temp_dir(), 'golf-contract-');
        $zip = new ZipArchive;
        try {
            file_put_contents($path, $bytes);
            if ($zip->open($path) !== true) $this->fail('Belge açılamadı. Geçerli bir DOCX veya XLSX dosyası seçin.');
            $total = 0;
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $entry = $zip->statIndex($i);
                $total += $entry['size'];
                if ($total > 24 * 1024 * 1024 || $zip->numFiles > 2000) $this->fail('Belgenin açılmış boyutu çok büyük. Daha küçük bir belge seçin.');
            }
            $text = $extension === 'docx' ? $this->word($zip) : $this->excel($zip);
            if (trim($text) === '') $this->fail('Belgede okunabilir metin bulunamadı. Görsel veya tarama yerine metin içeren belge yükleyin.');
            if (mb_strlen($text) > 100000) $this->fail('Belge çok uzun. Kontrat bölümlerini ayrı dosyalara ayırın.');
            return $text;
        } finally {
            try { $zip->close(); } catch (\Throwable) {}
            if (is_file($path)) unlink($path);
        }
    }

    private function xml(ZipArchive $zip, string $name): DOMXPath
    {
        $content = $zip->getFromName($name);
        if ($content === false || stripos($content, '<!DOCTYPE') !== false || stripos($content, '<!ENTITY') !== false) $this->fail('Belge yapısı okunamadı.');
        $doc = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        try {
            if (!$doc->loadXML($content, LIBXML_NONET)) $this->fail('Belge XML yapısı geçersiz.');
        } finally { libxml_clear_errors(); libxml_use_internal_errors($previous); }
        return new DOMXPath($doc);
    }

    private function word(ZipArchive $zip): string
    {
        $parts = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (!preg_match('~^word/(document|header\d+|footer\d+|footnotes|endnotes)\.xml$~', $name)) continue;
            $xml = $this->xml($zip, $name);
            foreach ($xml->query('//*[local-name()="p"]') as $paragraph) {
                $line = '';
                foreach ($xml->query('.//*[local-name()="t" or local-name()="tab" or local-name()="br"]', $paragraph) as $node) {
                    $line .= $node->localName === 't' ? $node->textContent : ' | ';
                }
                if (trim($line) !== '') $parts[] = '['.$name.'] '.$line;
            }
        }
        return implode("\n", $parts);
    }

    private function excel(ZipArchive $zip): string
    {
        $shared = [];
        if ($zip->locateName('xl/sharedStrings.xml') !== false) {
            $xml = $this->xml($zip, 'xl/sharedStrings.xml');
            foreach ($xml->query('//*[local-name()="si"]') as $item) {
                $value = '';
                foreach ($xml->query('.//*[local-name()="t"]', $item) as $node) $value .= $node->textContent;
                $shared[] = $value;
            }
        }
        $book = $this->xml($zip, 'xl/workbook.xml');
        $relations = $this->xml($zip, 'xl/_rels/workbook.xml.rels');
        $targets = [];
        foreach ($relations->query('//*[local-name()="Relationship"]') as $rel) {
            if ($rel->getAttribute('TargetMode') === 'External') continue;
            $target = $rel->getAttribute('Target');
            $targets[$rel->getAttribute('Id')] = str_starts_with($target, '/') ? ltrim($target, '/') : 'xl/'.$target;
        }
        $lines = ['Excel hücreleri koordinatlarıyla verilmiştir. Tarih biçimli sayılar Excel seri tarihidir; formüllerde yalnızca kayıtlı sonuç kullanılır.'];
        $date1904 = $book->evaluate('string(//*[local-name()="workbookPr"]/@date1904)');
        $lines[] = 'Tarih sistemi: '.(in_array($date1904, ['1','true']) ? '1904' : '1900');
        if ($zip->locateName('xl/styles.xml') !== false) {
            $styles = $this->xml($zip, 'xl/styles.xml');
            foreach ($styles->query('//*[local-name()="numFmt"]') as $format) $lines[] = 'Sayı biçimi '.$format->getAttribute('numFmtId').': '.$format->getAttribute('formatCode');
            foreach ($styles->query('//*[local-name()="cellXfs"]/*') as $i => $style) $lines[] = 'Stil '.$i.' sayı biçimi: '.$style->getAttribute('numFmtId');
        }
        $hasValues = false;
        foreach ($book->query('//*[local-name()="sheet"]') as $sheet) {
            $id = $sheet->getAttributeNS('http://schemas.openxmlformats.org/officeDocument/2006/relationships', 'id');
            $target = $targets[$id] ?? '';
            if (!preg_match('~^xl/worksheets/[^/]+\.xml$~', $target)) $this->fail('Çalışma sayfası yapısı desteklenmiyor.');
            $xml = $this->xml($zip, $target);
            $lines[] = '\nSayfa: '.$sheet->getAttribute('name').' ('.$sheet->getAttribute('state').')';
            foreach ($xml->query('//*[local-name()="row"]') as $row) {
                $cells = [];
                foreach ($xml->query('./*[local-name()="c"]', $row) as $cell) {
                    $value = $xml->evaluate('string(./*[local-name()="v"])', $cell);
                    if ($cell->getAttribute('t') === 's') $value = $shared[(int) $value] ?? '';
                    if ($cell->getAttribute('t') === 'inlineStr') {
                        $value = '';
                        foreach ($xml->query('.//*[local-name()="t"]', $cell) as $part) $value .= $part->textContent;
                    }
                    if ($xml->evaluate('count(./*[local-name()="f"])', $cell) && $value === '') $value = '[Formül sonucu yok; hesaplanamaz]';
                    if ($value !== '') $cells[] = $cell->getAttribute('r').' (stil '.$cell->getAttribute('s').') = '.$value;
                }
                if ($cells) { $hasValues = true; $lines[] = implode(' | ', $cells); }
            }
            foreach ($xml->query('//*[local-name()="mergeCell"]') as $merge) $lines[] = 'Birleşik hücre: '.$merge->getAttribute('ref');
        }
        return $hasValues ? implode("\n", $lines) : '';
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['document' => $message]);
    }
}
