<?php
class NumberToWords {
    private $ones = [
        0 => "", 1 => "One", 2 => "Two", 3 => "Three", 4 => "Four", 
        5 => "Five", 6 => "Six", 7 => "Seven", 8 => "Eight", 9 => "Nine",
        10 => "Ten", 11 => "Eleven", 12 => "Twelve", 13 => "Thirteen", 
        14 => "Fourteen", 15 => "Fifteen", 16 => "Sixteen", 
        17 => "Seventeen", 18 => "Eighteen", 19 => "Nineteen"
    ];
    
    private $tens = [
        2 => "Twenty", 3 => "Thirty", 4 => "Forty", 5 => "Fifty",
        6 => "Sixty", 7 => "Seventy", 8 => "Eighty", 9 => "Ninety"
    ];

    public function convert($number) {
        if ($number == 0) {
            return "Zero";
        }

        return $this->convertToWords($number);
    }

    private function convertToWords($number) {
        if ($number < 20) {
            return $this->ones[$number];
        }

        if ($number < 100) {
            return $this->tens[floor($number/10)] . ($number % 10 ? " " . $this->ones[$number % 10] : "");
        }

        if ($number < 1000) {
            return $this->ones[floor($number/100)] . " Hundred" . ($number % 100 ? " and " . $this->convertToWords($number % 100) : "");
        }

        if ($number < 100000) {
            return $this->convertToWords(floor($number/1000)) . " Thousand" . ($number % 1000 ? " " . $this->convertToWords($number % 1000) : "");
        }

        if ($number < 10000000) {
            return $this->convertToWords(floor($number/100000)) . " Lakh" . ($number % 100000 ? " " . $this->convertToWords($number % 100000) : "");
        }

        return $this->convertToWords(floor($number/10000000)) . " Crore" . ($number % 10000000 ? " " . $this->convertToWords($number % 10000000) : "");
    }
} 