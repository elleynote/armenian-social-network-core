<?php
namespace ASN\Core;

defined( 'ABSPATH' ) || exit;

final class Activator {
    public static function activate(): void {
        Database::install();
    }
}
