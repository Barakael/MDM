<?php

namespace App\Services\Mdm;

class NanoMdmPlistBuilder
{
    public static function command(string $commandUuid, array $command): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>'
            .'<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">'
            .'<plist version="1.0"><dict>'
            .'<key>Command</key><dict>'.self::dict($command).'</dict>'
            .'<key>CommandUUID</key><string>'.self::escape($commandUuid).'</string>'
            .'</dict></plist>';
    }

    protected static function dict(array $items): string
    {
        $xml = '';
        foreach ($items as $key => $value) {
            $xml .= '<key>'.self::escape((string) $key).'</key>'.self::value($value);
        }

        return $xml;
    }

    protected static function value(mixed $value): string
    {
        if (is_array($value)) {
            if (array_is_list($value)) {
                $xml = '<array>';
                foreach ($value as $item) {
                    $xml .= self::value($item);
                }

                return $xml.'</array>';
            }

            return '<dict>'.self::dict($value).'</dict>';
        }

        if (is_bool($value)) {
            return $value ? '<true/>' : '<false/>';
        }

        if (is_int($value)) {
            return '<integer>'.$value.'</integer>';
        }

        return '<string>'.self::escape((string) $value).'</string>';
    }

    protected static function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
