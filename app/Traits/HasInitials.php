<?php

namespace App\Traits;

use Illuminate\Support\Str;

trait HasInitials
{
    /**
     * Get the initials from the model's name.
     */
    public function initials(): string
    {
        if (empty($this->name)) {
            return '';
        }

        $words = Str::of((string) $this->name)->trim()->explode(' ')->filter()->values();

        if ($words->isEmpty()) {
            return '';
        }

        $first = Str::substr($words->first(), 0, 1);

        if ($words->count() > 1) {
            $last = Str::substr($words->last(), 0, 1);

            return mb_strtoupper($first.$last);
        }

        return mb_strtoupper($first);
    }
}
