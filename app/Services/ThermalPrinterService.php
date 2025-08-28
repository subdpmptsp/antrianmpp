<?php

namespace App\Services;

class ThermalPrinterService
{
    /**
     * Membuat teks untuk printer thermal
     */
    public function createText(array $lines)
    {
        $text = [
            $this->initializePrinter(),
        ];

        foreach ($lines as $line) {
            if (isset($line['type']) && $line['type'] === 'qrcode') {
                $text[] = $this->formatQRCode($line);
            } else {
                $text[] = $this->formatLine($line);
            }
        }

        $text[] = $this->feedPaper();
        $text[] = $this->cutPaper();

        return implode('', $text);
    }

    private function formatLine(array $line)
    {
        $formattedLine = '';

        if (isset($line['align'])) {
            $formattedLine .= $this->align($line['align']);
        }

        if (isset($line['style'])) {
            $formattedLine .= $this->style($line['style']);
        }

        $formattedLine .= $line['text'] . "\n";

        if (isset($line['style'])) {
            $formattedLine .= $this->resetStyle();
        }

        return $formattedLine;
    }

    private function initializePrinter()
    {
        return "\x1B\x40";  // Initialize printer
    }

    private function align($alignment)
    {
        switch ($alignment) {
            case 'center':
                return "\x1B\x61\x01";  // Center alignment
            case 'right':
                return "\x1B\x61\x02";  // Right alignment
            default:
                return "\x1B\x61\x00";  // Left alignment
        }
    }

    private function style($style)
    {
        switch ($style) {
            case 'double-all':
                return "\x1B\x21\x30\x1B\x45\x01";  // Double-height, double-width, Tebal
            case 'double-h':
                return "\x1B\x21\x10\x1B\x45\x01";  // Double-height, Tebal
            case 'double-w':
                return "\x1B\x21\x20\x1B\x45\x01";  // double-width, Tebal
            default:
                return "\x1B\x45\x00\x1B\x21\x00";  // Normal text
        }
    }

    private function resetStyle()
    {
        return "\x1B\x21\x00";  // Reset to normal text
    }

    private function feedPaper()
    {
        return "\n\n";  // Feed paper
    }

    private function cutPaper()
    {
        return "\x1B\x69";  // Cut paper
    }

    private function formatQRCode(array $qrParams)
    {
        $data = $qrParams['data'] ?? '';
        $size = $qrParams['size'] ?? 6;
        $eccLevel = $qrParams['eccLevel'] ?? 49; // Default M (49)
        $align = $qrParams['align'] ?? 'center';

        // Validasi parameter
        $size = max(1, min(16, $size)); // Batasi ukuran antara 1-16
        $eccLevel = max(48, min(51, $eccLevel)); // Batasi error correction level antara 48-51 (L,M,Q,H)

        $qrCode = '';

        // Set alignment
        $qrCode .= $this->align($align);

        // Command untuk mencetak QR Code
        $qrCode .= "\x1D\x28\x6B"; // GS ( k - QR Code command
        $qrCode .= chr(strlen($data) + 3); // pL - Panjang data + 3
        $qrCode .= chr((strlen($data) + 3) >> 8); // pH - Byte tinggi panjang (untuk data panjang)
        $qrCode .= "\x31\x50\x30"; // cn fn m (31=type, 50=store data, 30=auto encoding)
        $qrCode .= $data;

        // Set ukuran dot QR Code
        $qrCode .= "\x1D\x28\x6B\x03\x00\x31\x43" . chr($size);

        // Set error correction level
        $qrCode .= "\x1D\x28\x6B\x03\x00\x31\x45" . chr($eccLevel);

        // Cetak QR Code
        $qrCode .= "\x1D\x28\x6B\x03\x00\x31\x51\x30";

        // Feed setelah QR Code
        $qrCode .= "\n";

        return $qrCode;
    }

}
