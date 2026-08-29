<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    protected $casts = ['value' => 'array'];

    protected static ?array $memo = null;

    /**
     * Fetch a setting in "group.field" notation from the single stored row
     * (key="homepage", value=[...]). Groups all lookups into one query.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        self::$memo ??= static::withCache(static fn () => static::pluck('value', 'key')->all());

        [$group, $field] = array_pad(explode('.', $key, 2), 2, null);
        $value = self::$memo[$group] ?? null;

        if ($field === null) {
            return $value ?? $default;
        }

        return is_array($value) && array_key_exists($field, $value) ? $value[$field] : $default;
    }

    public static function put(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
        self::$memo = null;
        cache()->forget('homepage.settings');
    }

    /**
     * Whole-table read guarded by a 10-minute cache of plain arrays only
     * (safe on the shared-host database cache store).
     */
    protected static function withCache(callable $fresh): array
    {
        return (array) cache()->remember('homepage.settings', 600, $fresh);
    }
}
