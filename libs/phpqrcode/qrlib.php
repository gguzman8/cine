<?php
class QRCode {
    private $data;
    private $modules = [];
    private $size;

    private static $alpha = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ $%*+-./:';

    public static function fromString($data, $size = 4) {
        $qr = new self($data, $size);
        $qr->encode();
        return $qr;
    }

    public function __construct($data, $size = 4) {
        $this->data = $data;
        $this->size = max(1, min(10, $size));
    }

    public function encode() {
        $len = strlen($this->data);
        $dim = 21 + ($this->size - 1) * 4;
        $this->modules = array_fill(0, $dim, array_fill(0, $dim, 0));

        $data_bits = $this->byteEncode($this->data);
        $data_bits = $this->addTerminator($data_bits, $dim);
        $data_bits = $this->addPadding($data_bits, $dim);

        $this->placeFinder();
        $this->placeTiming();
        $this->placeData($data_bits, $dim);
        $this->applyMask($dim);
    }

    private function byteEncode($data) {
        $bits = '0100';
        $len = strlen($data);
        $bits .= str_pad(decbin($len), 8, '0', STR_PAD_LEFT);
        for ($i = 0; $i < $len; $i++) {
            $bits .= str_pad(decbin(ord($data[$i])), 8, '0', STR_PAD_LEFT);
        }
        return $bits;
    }

    private function addTerminator($bits, $dim) {
        $cap = $this->getCapacity($this->size);
        $needed = $cap * 8;
        if (strlen($bits) < $needed) {
            $bits .= str_repeat('0', min(4, $needed - strlen($bits)));
        }
        return $bits;
    }

    private function addPadding($bits, $dim) {
        $cap = $this->getCapacity($this->size);
        $needed = $cap * 8;
        while (strlen($bits) < $needed) {
            $bits .= (strlen($bits) % 2 === 0) ? '11101100' : '00010001';
        }
        return substr($bits, 0, $needed);
    }

    private function getCapacity($ver) {
        $caps = [0, 17, 32, 53, 78, 106, 134, 154, 192, 230, 271];
        return $caps[$ver] ?? 17;
    }

    private function placeFinder() {
        $d = 21;
        $patterns = [[0, 0], [$d - 7, 0], [0, $d - 7]];
        foreach ($patterns as [$x, $y]) {
            for ($r = 0; $r < 7; $r++) {
                for ($c = 0; $c < 7; $c++) {
                    $val = ($r === 0 || $r === 6 || $c === 0 || $c === 6 ||
                           ($r >= 2 && $r <= 4 && $c >= 2 && $c <= 4)) ? 1 : 0;
                    $this->setModule($x + $c, $y + $r, $val);
                }
            }
        }
        $seps = [[0, 7], [$d - 8, 0], [0, $d - 8]];
        foreach ($seps as [$x, $y]) {
            for ($i = -1; $i <= 7; $i++) {
                if ($x + $i >= 0 && $x + $i < $d) {
                    if ($y >= 0 && $y < $d) $this->setModule($x + $i, $y, 0);
                    if ($y + 7 >= 0 && $y + 7 < $d) $this->setModule($x + $i, $y + 7, 0);
                }
                if ($y + $i >= 0 && $y + $i < $d) {
                    if ($x >= 0 && $x < $d) $this->setModule($x, $y + $i, 0);
                    if ($x + 7 >= 0 && $x + 7 < $d) $this->setModule($x + 7, $y + $i, 0);
                }
            }
        }
    }

    private function placeTiming() {
        $d = 21;
        for ($i = 8; $i < $d - 8; $i++) {
            $this->setModule($i, 6, ($i % 2 === 0) ? 1 : 0);
            $this->setModule(6, $i, ($i % 2 === 0) ? 1 : 0);
        }
    }

    private function placeData($bits, $dim) {
        $idx = 0;
        $d = $dim;
        for ($col = $d - 1; $col >= 1; $col -= 2) {
            if ($col === 6) $col = 5;
            for ($row = 0; $row < $d; $row++) {
                for ($c = 0; $c < 2; $c++) {
                    $cx = $col - $c;
                    $cy = ($col % 4 === 1) ? $d - 1 - $row : $row;
                    if ($cx < 0) continue;
                    if ($this->isReserved($cx, $cy, $d)) continue;
                    if ($idx < strlen($bits)) {
                        $this->setModule($cx, $cy, (int)$bits[$idx]);
                        $idx++;
                    }
                }
            }
        }
    }

    private function isReserved($x, $y, $d) {
        if ($x <= 7 && $y <= 7) return true;
        if ($x >= $d - 7 && $y <= 7) return true;
        if ($x <= 7 && $y >= $d - 7) return true;
        if ($x === 6 || $y === 6) return true;
        return false;
    }

    private function setModule($x, $y, $val) {
        if (isset($this->modules[$y][$x])) {
            $this->modules[$y][$x] = $val;
        }
    }

    private function applyMask($dim) {
        for ($y = 0; $y < $dim; $y++) {
            for ($x = 0; $x < $dim; $x++) {
                if ($this->isReserved($x, $y, $dim)) continue;
                if (($x + $y) % 2 === 0) {
                    $this->modules[$y][$x] = $this->modules[$y][$x] ^ 1;
                }
            }
        }
    }

    public function toSvg($size = 200) {
        $dim = count($this->modules);
        $cell = $size / $dim;
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 ' . $dim . ' ' . $dim . '">';
        $svg .= '<rect width="' . $dim . '" height="' . $dim . '" fill="#fff"/>';
        for ($y = 0; $y < $dim; $y++) {
            for ($x = 0; $x < $dim; $x++) {
                if ($this->modules[$y][$x]) {
                    $svg .= '<rect x="' . $x . '" y="' . $y . '" width="1" height="1" fill="#000"/>';
                }
            }
        }
        $svg .= '</svg>';
        return $svg;
    }

    public function toPngBase64($size = 200) {
        $svg = $this->toSvg($size);
        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }
}
