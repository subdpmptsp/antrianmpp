<?php

if (!function_exists('generate_id')) {
    /**
     * Generate a ID from a numeric ID
     *
     * @param int $id The numeric ID to encode
     * @param string $salt Optional salt for additional security
     * @return string The ID
     */
    function generate_id($id, $salt = 'your-secret-salt')
    {
        // Characters used in IDs (62 characters)
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $base = strlen($characters);

        // Add salt and shuffle the ID to make it harder to guess the sequence
        $id = ($id * 23) + 9876; // Simple obfuscation

        $encoded = '';

        while ($id > 0) {
            $encoded = $characters[$id % $base] . $encoded;
            $id = floor($id / $base);
        }

        // Ensure the ID is at least 6 characters long by padding
        while (strlen($encoded) < 6) {
            $encoded = $characters[0] . $encoded;
        }

        return $encoded;
    }
}

if (!function_exists('decode_id')) {
    /**
     * Decode a ID back to its numeric form
     *
     * @param string $encoded The ID to decode
     * @param string $salt Optional salt matching the one used for encoding
     * @return int|null The original numeric ID or null if invalid
     */
    function decode_id($encoded, $salt = 'your-secret-salt')
    {
        // Characters used in IDs (62 characters)
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $base = strlen($characters);

        // Convert back to numeric ID
        $id = 0;
        $length = strlen($encoded);

        for ($i = 0; $i < $length; $i++) {
            $position = strpos($characters, $encoded[$i]);
            if ($position === false) {
                return null; // Invalid character found
            }
            $id = $id * $base + $position;
        }

        // Undo the obfuscation
        $id = ($id - 9876) / 23;

        return floor($id);
    }
}
