<?php

if (! function_exists('convertIndonesianMonthToEnglish')) {
    function convertIndonesianMonthToEnglish(string $dateString): string
    {
        $indonesianMonths = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
        $englishMonths = ['January','February','March','April','May','June','July','August','September','October','November','December'];

        return str_replace($indonesianMonths, $englishMonths, $dateString);
    }
}

if (! function_exists('convertEnglishMonthToIndonesian')) {
    function convertEnglishMonthToIndonesian(string $month): string
    {
        $englishMonths = ['January','February','March','April','May','June','July','August','September','October','November','December'];
        $indonesianMonths = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];

        return str_replace($englishMonths, $indonesianMonths, $month);
    }

}