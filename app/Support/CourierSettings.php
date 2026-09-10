<?php

namespace App\Support;

use App\Courier\Contracts\CourierDriver;
use App\Models\Setting;

/**
 * Courier credentials, in the settings table beside the SMS and mail ones, so
 * changing delivery company is an afternoon's admin rather than a deploy.
 *
 * Keys are namespaced by driver — courier_steadfast_api_key — which is why the
 * settings table needs no schema of its own for this: a courier added next year
 * writes its own keys the first time someone saves the form.
 */
class CourierSettings
{
    /** Which courier the "send to courier" button reaches for first. */
    public const DEFAULT_KEY = 'courier_default';

    /** @return class-string<CourierDriver>[] keyed by driver key */
    public static function driverClasses(): array
    {
        $classes = [];

        foreach ((array) config('courier.drivers', []) as $key => $definition) {
            $class = is_array($definition) ? ($definition['class'] ?? null) : $definition;

            if (is_string($class) && is_subclass_of($class, CourierDriver::class)) {
                $classes[$key] = $class;
            }
        }

        return $classes;
    }

    /** The settings key one credential of one courier is stored under. */
    public static function key(string $driver, string $field): string
    {
        return "courier_{$driver}_{$field}";
    }

    /**
     * Every stored credential for one courier, keyed by the driver's own field
     * names — the shape a driver's constructor wants.
     *
     * @return array<string, string|null>
     */
    public static function credentials(string $driver): array
    {
        $class = self::driverClasses()[$driver] ?? null;

        if ($class === null) {
            return [];
        }

        $values = [];

        foreach (array_keys($class::fields()) as $field) {
            $values[$field] = Setting::get(self::key($driver, $field));
        }

        return $values;
    }

    public static function isEnabled(string $driver): bool
    {
        return (string) Setting::get(self::key($driver, 'enabled')) === '1';
    }

    /** @return array<int, string> keys of the couriers switched on, in config order */
    public static function enabled(): array
    {
        return array_values(array_filter(
            array_keys(self::driverClasses()),
            fn (string $key) => self::isEnabled($key),
        ));
    }

    /**
     * The preferred courier, or null when none is usable.
     *
     * Falls back to the first enabled one rather than returning a courier that
     * was switched off after being made the default — a stale default should
     * degrade to "some other courier", never to "a courier we cannot use".
     */
    public static function default(): ?string
    {
        $chosen = (string) Setting::get(self::DEFAULT_KEY);

        if ($chosen !== '' && self::isEnabled($chosen)) {
            return $chosen;
        }

        return self::enabled()[0] ?? null;
    }

    /**
     * Save one courier's panel.
     *
     * @param  array<string, mixed>  $values  credential values keyed by field name
     */
    public static function save(string $driver, array $values, bool $enabled): void
    {
        $class = self::driverClasses()[$driver] ?? null;

        if ($class === null) {
            return;
        }

        foreach ($class::fields() as $field => $definition) {
            if (! array_key_exists($field, $values)) {
                continue;
            }

            $type = ($definition['type'] ?? 'text') === 'secret' ? Setting::TYPE_SECRET : 'text';

            // A blank secret means "keep the one already stored" — the form
            // never shows a saved secret back, so an empty box is the normal
            // state of a correctly configured courier, not a request to erase it.
            if ($type === Setting::TYPE_SECRET && blank($values[$field])) {
                continue;
            }

            Setting::put(self::key($driver, $field), $values[$field], $type);
        }

        Setting::put(self::key($driver, 'enabled'), $enabled ? '1' : '0');
    }

    public static function setDefault(?string $driver): void
    {
        Setting::put(self::DEFAULT_KEY, $driver ?: '');
    }
}
