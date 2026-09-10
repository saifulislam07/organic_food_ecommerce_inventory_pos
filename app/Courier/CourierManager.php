<?php

namespace App\Courier;

use App\Courier\Contracts\CourierDriver;
use App\Support\CourierSettings;

/**
 * Which couriers exist, which of them this shop can actually use, and how to
 * get hold of one.
 *
 * Nothing here touches an order — that is CourierDispatch's job. This class
 * only resolves, so the settings screen can ask "what is configured?" without
 * dragging order handling in with it.
 */
class CourierManager
{
    /** @var array<string, CourierDriver> resolved once per request */
    private array $resolved = [];

    private ?CourierDriver $override = null;

    /**
     * One driver, built from its stored credentials.
     *
     * Passing null asks for the shop's default. Returns null when the key names
     * a courier that no longer exists in config, which is what happens to an
     * order booked with a driver that was later removed.
     */
    public function driver(?string $key = null): ?CourierDriver
    {
        if ($this->override) {
            return $this->override;
        }

        $key = $key ?: CourierSettings::default();

        if ($key === null) {
            return null;
        }

        if (isset($this->resolved[$key])) {
            return $this->resolved[$key];
        }

        $class = CourierSettings::driverClasses()[$key] ?? null;

        if ($class === null) {
            return null;
        }

        return $this->resolved[$key] = new $class(CourierSettings::credentials($key));
    }

    /** Swap in a driver for tests, or to try credentials before saving them. */
    public function use(?CourierDriver $driver): self
    {
        $this->override = $driver;

        return $this;
    }

    /**
     * Every courier the shop could send this parcel by: switched on, and with
     * the credentials it needs.
     *
     * @return array<int, array{key: string, label: string, is_default: bool}>
     */
    public function usable(): array
    {
        $default = CourierSettings::default();
        $usable = [];

        foreach (CourierSettings::enabled() as $key) {
            $driver = $this->driver($key);

            if ($driver === null || ! $driver->isConfigured()) {
                continue;
            }

            $usable[] = [
                'key' => $key,
                'label' => $driver::label(),
                'is_default' => $key === $default,
            ];
        }

        return $usable;
    }

    /**
     * Everything on the settings page, configured or not.
     *
     * @return array<int, array{key: string, label: string, fields: array, enabled: bool, configured: bool, is_default: bool}>
     */
    public function catalogue(): array
    {
        $default = CourierSettings::default();
        $rows = [];

        foreach (CourierSettings::driverClasses() as $key => $class) {
            $driver = $this->driver($key);

            $rows[] = [
                'key' => $key,
                'label' => $class::label(),
                'fields' => $class::fields(),
                'enabled' => CourierSettings::isEnabled($key),
                'configured' => $driver?->isConfigured() ?? false,
                'is_default' => $key === $default,
            ];
        }

        return $rows;
    }

    /** The name to print for a courier key, including one no longer installed. */
    public function label(?string $key): string
    {
        if (blank($key)) {
            return '—';
        }

        $class = CourierSettings::driverClasses()[$key] ?? null;

        return $class ? $class::label() : ucfirst($key);
    }

    /** Forget resolved drivers, so a settings save takes effect in the same request. */
    public function flush(): void
    {
        $this->resolved = [];
    }
}
