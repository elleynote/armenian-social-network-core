<?php
namespace ASN\Core;

defined( 'ABSPATH' ) || exit;

final class Deactivator {
    public static function deactivate(): void {
        // Deliberately preserve all data on deactivation.
    }
}
