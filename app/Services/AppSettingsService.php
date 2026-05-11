<?php

namespace App\Services;

use App\Models\AppSetting;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;

class AppSettingsService
{
    public function get(string $key, mixed $default = null): mixed
    {
        $setting = AppSetting::query()->where('key', $key)->first();

        if (! $setting) {
            return $default;
        }

        if ($setting->is_encrypted && filled($setting->value)) {
            try {
                return Crypt::decryptString($setting->value);
            } catch (DecryptException) {
                return $default;
            }
        }

        return $setting->value ?? $default;
    }

    public function setMany(string $group, array $values, array $encryptedKeys = []): void
    {
        foreach ($values as $key => $value) {
            $isEncrypted = in_array($key, $encryptedKeys, true);

            AppSetting::query()->updateOrCreate(
                ['key' => $key],
                [
                    'group' => $group,
                    'value' => $isEncrypted && filled($value) ? Crypt::encryptString((string) $value) : $value,
                    'is_encrypted' => $isEncrypted,
                ]
            );
        }
    }

    public function group(string $group): array
    {
        return AppSetting::query()
            ->where('group', $group)
            ->get()
            ->mapWithKeys(function (AppSetting $setting) {
                return [$setting->key => $setting->is_encrypted && filled($setting->value)
                    ? $this->get($setting->key)
                    : $setting->value];
            })
            ->all();
    }

    public function bool(string $key, bool $default = false): bool
    {
        return filter_var($this->get($key, $default), FILTER_VALIDATE_BOOL);
    }
}
