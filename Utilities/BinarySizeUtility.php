<?php

namespace Sidus\FileUploadBundle\Utilities;

/**
 * Small utility class to handle binary size conversions
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class BinarySizeUtility
{
    /**
     * Takes a human-formatted binary size and return a number of octets
     *
     * @param string $size
     * @param string $fallbackUnits
     *
     * @throws \UnexpectedValueException
     *
     * @return int
     */
    public static function parse($size, $fallbackUnits = null)
    {
        preg_match('/^(\d+)[.,]?(\d*)\s*(\w*)$/', $size, $matches);
        if (empty($matches[1]) || (empty($matches[3]) && null === $fallbackUnits)) {
            throw new \UnexpectedValueException("Unable to parse : '{$size}'");
        }
        $oSize = $matches[1];
        if (!empty($matches[2])) {
            $oSize .= '.'.$matches[2];
        }
        $oSize = (float) $oSize;
        $unit = strtolower(empty($matches[3]) ? $fallbackUnits : $matches[3]);
        $byteMultiplier = 1;
        if ('b' === substr($unit, -1)) {
            $byteMultiplier = 8;
            $unit = substr($unit, 0, -1).'o';
        }
        if (!array_key_exists($unit, self::getBinarySizes())) {
            throw new \UnexpectedValueException("Unexpected unit {$unit}");
        }

        return (int) ($oSize * self::getBinarySizes()[$unit] * $byteMultiplier);
    }

    /**
     * Return a binary size in a human readable form.
     *
     * @param int    $size         number of octets
     * @param int    $decimals
     * @param string $decPoint
     * @param string $thousandsSep
     * @param string $unitSep
     *
     * @throws \UnexpectedValueException
     *
     * @return string
     */
    public static function format($size, $decimals = 2, $decPoint = '.', $thousandsSep = '', $unitSep = '')
    {
        $output = $unit = null;
        foreach (self::getBinarySizes() as $unit => $divider) {
            $output = $size / $divider;
            if ($output < 1000) {
                break;
            }
        }
        if (null === $output) {
            throw new \UnexpectedValueException("Unable to parse value: '{$size}'");
        }
        $unit = $unit === 'o' ? 'o' : ucfirst($unit);
        $trimmed = rtrim(rtrim(number_format($output, $decimals, $decPoint, $thousandsSep), '0'), $decPoint);
        $formatted = $trimmed.$unitSep.$unit;

        return str_replace(' ', utf8_encode(chr(160)), $formatted);
    }

    /**
     * @param int    $size
     * @param string $fallbackUnits
     *
     * @throws \UnexpectedValueException
     *
     * @return string
     */
    public static function reformat($size, $fallbackUnits = null)
    {
        return self::format(self::parse($size, $fallbackUnits));
    }

    /**
     * Unit table
     *
     * @return array
     */
    protected static function getBinarySizes()
    {
        return [
            // SI units
            'o' => 1,     // octet
            'ko' => 1e3,   // kilooctet
            'mo' => 1e6,   // mégaoctet
            'go' => 1e9,   // gigaoctet
            'to' => 1e12,  // téraoctet
            'po' => 1e15,  // pétaoctet
            'eo' => 1e18,  // exaoctet
            'zo' => 1e21,  // zettaoctet
            'yo' => 1e24,  // yottaoctet

            // Additionnal binary units
            'kio' => 2 ^ 10,  // kibioctet
            'mio' => 2 ^ 20,  // mébioctet
            'gio' => 2 ^ 30,  // gibioctet
            'tio' => 2 ^ 40,  // tébioctet
            'pio' => 2 ^ 50,  // pébioctet
            'eio' => 2 ^ 60,  // exbioctet
            'zio' => 2 ^ 70,  // zébioctet
            'yio' => 2 ^ 80,  // yobioctet
        ];
    }
}
