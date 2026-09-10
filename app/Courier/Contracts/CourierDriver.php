<?php

namespace App\Courier\Contracts;

use App\Courier\Consignment;
use App\Courier\TrackedStatus;
use App\Models\Order;

/**
 * One delivery company.
 *
 * The static half describes the courier well enough for the settings screen to
 * draw a form for it without knowing anything about that particular company;
 * the instance half does the two things a courier is actually for — take a
 * parcel, and say where it is.
 */
interface CourierDriver
{
    /** Stable key stored on the order, e.g. "steadfast". Never change it once live. */
    public static function key(): string;

    /** What the shop owner calls this company. */
    public static function label(): string;

    /**
     * The credentials this courier needs, in the order the form should show them.
     *
     * @return array<string, array{label: string, type?: string, help?: string, required?: bool, placeholder?: string}>
     *                                                                                                                  type is 'text', 'secret' or 'url'
     */
    public static function fields(): array;

    /** True when every required credential is present. */
    public function isConfigured(): bool;

    /** Hand the parcel over and get back whatever ids the courier issued. */
    public function book(Order $order): Consignment;

    /** Ask where the parcel is. */
    public function track(Order $order): TrackedStatus;
}
