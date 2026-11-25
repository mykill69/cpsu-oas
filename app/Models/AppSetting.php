<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

/** Small key/value store. Secrets (the Drive refresh token) are encrypted with APP_KEY. */
class AppSetting extends Model
{
    protected $primaryKey = 'key';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['key', 'value'];

    public static function get(string $key, $default = null)
    {
        return static::find($key)?->value ?? $default;
    }

    public static function put(string $key, ?string $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }

    public static function getSecret(string $key): ?string
    {
        $v = static::get($key);

        return $v ? Crypt::decryptString($v) : null;
    }

    public static function putSecret(string $key, ?string $value): void
    {
        static::put($key, $value === null ? null : Crypt::encryptString($value));
    }
}
