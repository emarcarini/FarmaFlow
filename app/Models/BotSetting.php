<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class BotSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'group',
        'label',
        'description',
        'type',
    ];

    /**
     * Obter o valor tipado de uma configuração com suporte a cache.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember("bot_setting_{$key}", 300, function () use ($key, $default) {
            $setting = static::where('key', $key)->first();
            if (!$setting) {
                return $default;
            }

            return match ($setting->type) {
                'boolean' => filter_var($setting->value, FILTER_VALIDATE_BOOLEAN),
                'integer' => (int) $setting->value,
                'float' => (float) $setting->value,
                default => $setting->value ?? $default,
            };
        });
    }

    /**
     * Definir ou atualizar o valor de uma configuração.
     */
    public static function set(string $key, mixed $value): void
    {
        $setting = static::where('key', $key)->first();
        if ($setting) {
            $setting->update(['value' => (string) $value]);
        } else {
            static::create([
                'key' => $key,
                'value' => (string) $value,
                'label' => ucfirst(str_replace('_', ' ', $key)),
            ]);
        }

        Cache::forget("bot_setting_{$key}");
    }

    /**
     * Obter todas as configurações agrupadas por categoria/grupo.
     */
    public static function getAllGrouped(): array
    {
        return static::all()->groupBy('group')->toArray();
    }
}
