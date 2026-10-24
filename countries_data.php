<?php
declare(strict_types=1);

/**
 * Country data repository for COEPD Aviation International Landing Pages.
 * Sourced and compiled from Country wise page contents.docx
 */

function get_all_countries_data(): array {
    static $data = null;
    if ($data === null) {
        $json_file = __DIR__ . '/countries_data.json';
        if (file_exists($json_file)) {
            $data = json_decode((string)file_get_contents($json_file), true);
        } else {
            $data = [];
        }
    }
    return $data;
}

function get_country_data(string $key): ?array {
    $all = get_all_countries_data();
    return $all[$key] ?? null;
}
