<?php


namespace Amrnn\CursorPaginator\Util;


class Base64Url {
    public static function encode(string $data): string
    {
        $encoded = base64_encode($data);
        $encoded = strtr($encoded, '+/', '-_');
        return rtrim($encoded, '=');
    }

    public static function decode(string $data): string
    {
        $data = strtr($data, '-_', '+/');
        return base64_decode($data);
    }
}