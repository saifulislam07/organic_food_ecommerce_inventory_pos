/** Mirrors Blade's number_format($value) so JS and PHP render prices identically. */
export function money(value) {
    const number = Number(value) || 0;

    return '৳' + number.toLocaleString('en-US', { maximumFractionDigits: 0 });
}
