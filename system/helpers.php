<?php
// system/helpers.php

if (!function_exists('e')) {
    /**
     * Безопасное экранирование строк для вывода в HTML (защита от XSS)
     */
    function e(?string $string): string
    {
        return htmlspecialchars($string ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}