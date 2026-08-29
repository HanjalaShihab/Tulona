<?php

namespace App\Support;

use DOMDocument;
use DOMElement;

/**
 * Allow-list HTML sanitizer for admin-authored rich text. Drops every element
 * and attribute that could execute script, leaving only safe formatting.
 */
class HtmlSanitizer
{
    protected const TAGS = [
        'p', 'br', 'strong', 'b', 'em', 'i', 'u', 's', 'strike', 'a', 'ul', 'ol', 'li',
        'h2', 'h3', 'h4', 'blockquote', 'code', 'pre', 'img', 'table', 'thead', 'tbody',
        'tfoot', 'tr', 'th', 'td', 'caption', 'figure', 'figcaption', 'span', 'div', 'hr',
        'sub', 'sup', 'mark', 'small', 'details', 'summary',
    ];

    protected const ATTRIBUTES = ['href', 'src', 'alt', 'title', 'width', 'height', 'target', 'rel', 'class'];

    protected const PROTOCOLS = ['http', 'https', 'mailto', 'tel', 'ftp', 'ftps'];

    /** Container elements with no visible text of their own — dropped entirely. */
    protected const DANGEROUS = [
        'script', 'style', 'iframe', 'object', 'embed', 'form', 'input', 'button',
        'textarea', 'select', 'option', 'optgroup', 'link', 'meta', 'base', 'template',
        'svg', 'math', 'noscript', 'audio', 'video', 'source', 'track', 'canvas', 'portal',
    ];

    public static function sanitize(?string $html): string
    {
        if ($html === null || trim($html) === '') {
            return '';
        }

        $doc = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);

        try {
            $loaded = $doc->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

            foreach (iterator_to_array($doc->childNodes) as $child) {
                if ($child->nodeType === XML_PI_NODE) {
                    $doc->removeChild($child);
                }
            }

            foreach (iterator_to_array($doc->getElementsByTagName('*')) as $node) {
                if (! $node instanceof DOMElement) {
                    continue;
                }

                $name = strtolower($node->nodeName);

                if (! in_array($name, self::TAGS, true)) {
                    if (in_array($name, self::DANGEROUS, true)) {
                        $node->parentNode?->removeChild($node);
                    } else {
                        $node->parentNode?->replaceChild($doc->createTextNode($node->textContent), $node);
                    }

                    continue;
                }

                foreach (iterator_to_array($node->attributes) as $attribute) {
                    $attr = strtolower($attribute->nodeName);

                    if (str_starts_with($attr, 'on') || ! in_array($attr, self::ATTRIBUTES, true)) {
                        $node->removeAttributeNode($attribute);

                        continue;
                    }

                    $value = trim($attribute->nodeValue);

                    if (in_array($attr, ['href', 'src'], true)
                        && preg_match('/^[a-zA-Z][a-zA-Z0-9+.\-]*:/', $value)
                        && ! in_array(strtolower((string) parse_url($value, PHP_URL_SCHEME)), self::PROTOCOLS, true)) {
                        $node->removeAttributeNode($attribute);
                    }
                }
            }
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        if (! $loaded) {
            return e($html);
        }

        return $doc->saveHTML();
    }
}
