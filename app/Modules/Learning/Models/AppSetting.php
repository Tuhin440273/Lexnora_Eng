<?php

namespace App\Modules\Learning\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;

/**
 * Admin-editable key/value settings (scoring rules, hints, review intervals, XP...).
 * Falls back to config('learning.defaults') so the app also works before seeding.
 */
class AppSetting extends Model
{
    public const CACHE_KEY = 'app_settings.all';

    protected $table = 'app_settings';

    protected $fillable = ['key', 'value', 'group'];

    protected function casts(): array
    {
        return ['value' => 'json'];
    }

    protected static function booted(): void
    {
        $forget = fn () => Cache::forget(self::CACHE_KEY);

        static::saved($forget);
        static::deleted($forget);
    }

    /** @return array<string, mixed> */
    public static function allValues(): array
    {
        return Cache::rememberForever(
            self::CACHE_KEY,
            fn (): array => static::query()->pluck('value', 'key')->all()
        );
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $stored = static::allValues();

        if (array_key_exists($key, $stored)) {
            return $stored[$key];
        }

        // Keys contain dots ("xp.daily_checkin"), so look them up in the flat array.
        $defaults = (array) config('learning.defaults', []);

        return Arr::exists($defaults, $key) ? $defaults[$key] : $default;
    }

    public static function set(string $key, mixed $value, ?string $group = null): self
    {
        return static::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'group' => $group ?? strtok($key, '.')]
        );
    }
}
