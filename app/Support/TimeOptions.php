<?php

namespace App\Support;

class TimeOptions
{
    /** @return array<string, array<string, string>> */
    public static function everyFiveMinutes(): array
    {
        $options = [];

        for ($hour = 0; $hour < 24; $hour++) {
            $hourLabel = str_pad((string) $hour, 2, '0', STR_PAD_LEFT).'.00–'
                .str_pad((string) $hour, 2, '0', STR_PAD_LEFT).'.59';

            for ($minute = 0; $minute < 60; $minute += 5) {
                $time = sprintf('%02d:%02d', $hour, $minute);
                $options[$hourLabel][$time] = $time;
            }
        }

        return $options;
    }
}
