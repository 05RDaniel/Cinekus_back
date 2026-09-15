<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UniqueSlug
{
    public static function make(string $table, string $column, string $source, string $fallback = 'tipo'): string
    {
        $base = Str::slug($source);
        if ($base === '' || $base === 'none') {
            $base = Str::slug($fallback);
        }
        if ($base === '' || $base === 'none') {
            $base = 'tipo';
        }

        $slug = $base;
        $suffix = 2;
        while (DB::table($table)->where($column, $slug)->exists()) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }
}
