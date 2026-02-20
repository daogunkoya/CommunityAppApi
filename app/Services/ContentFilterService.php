<?php

declare(strict_types=1);

namespace App\Services;

class ContentFilterService
{
    /**
     * List of prohibited words/phrases
     * This is a basic list and can be expanded or moved to a config/database
     */
    private const PROHIBITED_WORDS = [
        'badword1',
        'badword2', // Placeholders to demonstrate functionality
        'abuse',
        'hate',
        'kill',
        'murder',
        'terror',
        'bomb',
        'sex',
        'porn',
        'xxx',
        'nude',
        // Add more words as needed, or load from a better source
    ];

    /**
     * Check if text contains prohibited content
     */
    public function containsProfanity(?string $text): bool
    {
        if (empty($text)) {
            return false;
        }

        $normalizedText = strtolower($text);

        foreach (self::PROHIBITED_WORDS as $word) {
            // Simple check: is the word present with word boundaries?
            // This prevents flagging "scunthorpe" due to "cunt"
            if (preg_match('/\b' . preg_quote($word, '/') . '\b/i', $normalizedText)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Validate content and throw exception if prohibited
     * @throws \Exception
     */
    public function validateContent(string ...$texts): void
    {
        foreach ($texts as $text) {
            if ($this->containsProfanity($text)) {
                throw new \Exception('Content contains prohibited words or phrases.');
            }
        }
    }
}
