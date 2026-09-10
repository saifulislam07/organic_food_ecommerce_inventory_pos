<?php

namespace App\Http\Controllers\Admin;

use App\Courier\CourierManager;
use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Support\CourierSettings;
use Illuminate\Http\Request;

/**
 * One page for every delivery company the shop can use.
 *
 * The form is drawn from the drivers themselves rather than written out here,
 * so adding a courier is one class and one config line — this controller never
 * learns any provider's name.
 */
class AdminCourierSettingController extends Controller
{
    public function __construct(private readonly CourierManager $couriers) {}

    public function edit()
    {
        $catalogue = $this->couriers->catalogue();

        return view('admin.settings.couriers', [
            'couriers' => $catalogue,
            'values' => $this->storedValues($catalogue),
            // Secrets are never sent back to the browser; the form only needs
            // to know whether there is one saved, so it can say so.
            'saved' => $this->savedFlags($catalogue),
            'default' => CourierSettings::default(),
        ]);
    }

    public function update(Request $request)
    {
        $catalogue = $this->couriers->catalogue();

        $validated = $request->validate(
            $this->rules($catalogue),
            [],
            $this->attributeNames($catalogue),
        );

        foreach ($catalogue as $courier) {
            $submitted = $validated['couriers'][$courier['key']] ?? [];
            $enabled = (bool) ($submitted['enabled'] ?? false);

            CourierSettings::save(
                $courier['key'],
                array_diff_key($submitted, ['enabled' => true]),
                $enabled,
            );
        }

        CourierSettings::setDefault($validated['courier_default'] ?? null);

        // Drivers resolved earlier in this request still hold the old
        // credentials; the redirect would not care, but a driver cached here is
        // the sort of thing that quietly breaks a later refactor.
        $this->couriers->flush();

        return redirect()
            ->route('admin.settings.couriers.edit')
            ->with('success', 'Courier settings saved.');
    }

    /**
     * Rules built from what each driver says it needs.
     *
     * Required-ness is conditional on the switch: a courier nobody has turned
     * on must not block the form because its API key is blank, and a courier
     * being turned on must not be saved half-configured. A secret already
     * stored counts as present, since the form deliberately posts it back blank.
     */
    private function rules(array $catalogue): array
    {
        $rules = [
            'couriers' => ['array'],
            'courier_default' => ['nullable', 'string', 'max:32'],
        ];

        foreach ($catalogue as $courier) {
            $key = $courier['key'];
            $rules["couriers.{$key}"] = ['array'];
            $rules["couriers.{$key}.enabled"] = ['nullable', 'boolean'];

            foreach ($courier['fields'] as $field => $definition) {
                $stored = filled(Setting::get(CourierSettings::key($key, $field)));

                // nullable throughout: an empty box arrives as null, thanks to
                // the framework's convert-empty-strings middleware, and that is
                // the ordinary state of a courier nobody has filled in. The
                // required_if still fires on it, because required rules are
                // implicit and run whether or not the value is null.
                $rule = ['nullable', 'string', 'max:255'];

                if (($definition['required'] ?? true) && ! $stored) {
                    $rule[] = "required_if:couriers.{$key}.enabled,1";
                }

                if (($definition['type'] ?? 'text') === 'url') {
                    $rule[] = 'url';
                }

                $rules["couriers.{$key}.{$field}"] = $rule;
            }
        }

        return $rules;
    }

    /** So an error reads "Steadfast Courier API key", not "couriers.steadfast.api_key". */
    private function attributeNames(array $catalogue): array
    {
        $names = [];

        foreach ($catalogue as $courier) {
            foreach ($courier['fields'] as $field => $definition) {
                $names["couriers.{$courier['key']}.{$field}"] =
                    $courier['label'].' '.mb_strtolower($definition['label']);
            }
        }

        return $names;
    }

    /**
     * The values the form can safely show back.
     *
     * Secrets are omitted on purpose. A store id or a base URL is configuration
     * and belongs in the box where it was typed; an API key is a credential,
     * and re-rendering it puts it in the page source, the browser cache and
     * anyone's shoulder view for no benefit at all.
     *
     * @return array<string, array<string, string|null>>
     */
    private function storedValues(array $catalogue): array
    {
        $values = [];

        foreach ($catalogue as $courier) {
            foreach ($courier['fields'] as $field => $definition) {
                $values[$courier['key']][$field] = ($definition['type'] ?? 'text') === 'secret'
                    ? null
                    : Setting::get(CourierSettings::key($courier['key'], $field));
            }
        }

        return $values;
    }

    /**
     * Which credentials already have a value stored, so the form can say
     * "saved" in a box it is deliberately leaving empty.
     *
     * @return array<string, array<string, bool>>
     */
    private function savedFlags(array $catalogue): array
    {
        $flags = [];

        foreach ($catalogue as $courier) {
            foreach (array_keys($courier['fields']) as $field) {
                $flags[$courier['key']][$field] = filled(
                    Setting::get(CourierSettings::key($courier['key'], $field))
                );
            }
        }

        return $flags;
    }
}
