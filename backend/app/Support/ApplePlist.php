<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMXPath;

class ApplePlist
{
    /**
     * Decode NanoMDM / MicroMDM webhook raw_payload (base64 or raw XML) into a flat dict.
     *
     * @return array<string, mixed>
     */
    public static function decode(mixed $raw): array
    {
        if ($raw === null || $raw === '') {
            return [];
        }

        if (is_array($raw)) {
            return $raw;
        }

        $xml = is_string($raw) ? $raw : '';

        if ($xml !== '' && ! str_starts_with(ltrim($xml), '<')) {
            $decoded = base64_decode($xml, true);
            if ($decoded !== false) {
                $xml = $decoded;
            }
        }

        if ($xml === '' || ! str_contains($xml, '<plist')) {
            return [];
        }

        return self::parseXml($xml);
    }

    /**
     * @return array<string, mixed>
     */
    public static function parseXml(string $xml): array
    {
        $doc = new DOMDocument;
        if (! @$doc->loadXML($xml)) {
            return [];
        }

        $xpath = new DOMXPath($doc);
        $dict = $xpath->query('/plist/dict')->item(0);
        if (! $dict instanceof DOMElement) {
            return [];
        }

        return self::parseDict($dict);
    }

    /**
     * @return array<string, mixed>
     */
    protected static function parseDict(DOMElement $dict): array
    {
        $out = [];
        $key = null;

        foreach ($dict->childNodes as $node) {
            if (! $node instanceof DOMElement) {
                continue;
            }

            if ($node->tagName === 'key') {
                $key = $node->textContent;
                continue;
            }

            if ($key === null) {
                continue;
            }

            $out[$key] = self::parseValue($node);
            $key = null;
        }

        return $out;
    }

    protected static function parseValue(DOMElement $node): mixed
    {
        return match ($node->tagName) {
            'string', 'data' => $node->textContent,
            'integer' => (int) $node->textContent,
            'real' => (float) $node->textContent,
            'true' => true,
            'false' => false,
            'dict' => self::parseDict($node),
            'array' => self::parseArray($node),
            default => $node->textContent,
        };
    }

    /**
     * @return list<mixed>
     */
    protected static function parseArray(DOMElement $array): array
    {
        $items = [];
        foreach ($array->childNodes as $node) {
            if ($node instanceof DOMElement) {
                $items[] = self::parseValue($node);
            }
        }

        return $items;
    }
}
