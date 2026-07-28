<?php

declare(strict_types=1);

namespace App\Support;

use InvalidArgumentException;

final class IpNetwork
{
    /**
     * @return array{family: 4|6, canonical: string, first: string, last: string}
     */
    public static function parse(string $cidr): array
    {
        if (substr_count($cidr, '/') !== 1) {
            throw new InvalidArgumentException('The subnet must use CIDR notation.');
        }

        [$address, $prefixText] = explode('/', $cidr, 2);
        $packed = @inet_pton($address);
        if ($packed === false || ! ctype_digit($prefixText)) {
            throw new InvalidArgumentException('The subnet contains an invalid IP address or prefix.');
        }

        $family = strlen($packed) === 4 ? 4 : 6;
        $bits = $family === 4 ? 32 : 128;
        $prefix = (int) $prefixText;
        if ($prefix < 0 || $prefix > $bits) {
            throw new InvalidArgumentException("IPv{$family} prefixes must be between 0 and {$bits}.");
        }

        [$network, $end] = self::bounds($packed, $prefix);
        $first = $network;
        $last = $end;

        if ($family === 4 && $prefix < 31) {
            $first = self::incrementPacked($network);
            $last = self::decrementPacked($end);
        } elseif ($family === 6 && $prefix < 128) {
            $first = self::incrementPacked($network);
        }

        return [
            'family' => $family,
            'canonical' => inet_ntop($network)."/{$prefix}",
            'first' => inet_ntop($first),
            'last' => inet_ntop($last),
        ];
    }

    public static function compare(string $left, string $right): int
    {
        $leftPacked = self::pack($left);
        $rightPacked = self::pack($right);
        if (strlen($leftPacked) !== strlen($rightPacked)) {
            throw new InvalidArgumentException('IP addresses from different families cannot be compared.');
        }

        return strcmp($leftPacked, $rightPacked);
    }

    public static function increment(string $address): ?string
    {
        $packed = self::pack($address);
        $incremented = self::incrementPacked($packed);

        return $incremented === str_repeat("\0", strlen($packed)) ? null : inet_ntop($incremented);
    }

    /** @return array{string, string} */
    private static function bounds(string $packed, int $prefix): array
    {
        $network = '';
        $end = '';
        $remaining = $prefix;

        foreach (unpack('C*', $packed) as $byte) {
            $mask = $remaining >= 8 ? 255 : ($remaining <= 0 ? 0 : (255 << (8 - $remaining)) & 255);
            $network .= chr($byte & $mask);
            $end .= chr(($byte & $mask) | (255 ^ $mask));
            $remaining -= 8;
        }

        return [$network, $end];
    }

    private static function pack(string $address): string
    {
        $packed = @inet_pton($address);
        if ($packed === false) {
            throw new InvalidArgumentException("Invalid IP address: {$address}");
        }

        return $packed;
    }

    private static function incrementPacked(string $packed): string
    {
        for ($index = strlen($packed) - 1; $index >= 0; $index--) {
            $byte = ord($packed[$index]);
            $packed[$index] = chr(($byte + 1) & 255);
            if ($byte < 255) {
                break;
            }
        }

        return $packed;
    }

    private static function decrementPacked(string $packed): string
    {
        for ($index = strlen($packed) - 1; $index >= 0; $index--) {
            $byte = ord($packed[$index]);
            $packed[$index] = chr(($byte - 1) & 255);
            if ($byte > 0) {
                break;
            }
        }

        return $packed;
    }
}
