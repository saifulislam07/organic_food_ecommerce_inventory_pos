<?php

namespace Tests\Feature\Admin;

use App\Models\Setting;
use App\Models\SiteBlock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The header menu, the service strip, the promo tiles, the payment chips and
 * the footer link columns were fixed strings in the Blade templates. They now
 * come out of site_blocks, so a shop can change them without a deploy.
 */
class SiteBlockTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->superAdmin()->create();
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'group' => 'header_menu',
            'title_en' => 'Offers',
            'title_bn' => 'অফার',
            'url' => '/shop?sort=price_low',
            'icon' => 'tag',
            'sort_order' => 9,
            'is_active' => '1',
        ], $overrides);
    }

    /* ------------------------------------------------------- the storefront */

    public function test_the_migration_ships_the_lists_the_pages_used_to_hard_code(): void
    {
        foreach (array_keys(SiteBlock::GROUPS) as $group) {
            $this->assertNotEmpty(
                SiteBlock::list($group),
                "The {$group} list should arrive seeded so an existing shop reads the same after migrating."
            );
        }
    }

    public function test_a_header_menu_item_reaches_the_navigation(): void
    {
        SiteBlock::create($this->payload(['title_en' => 'Gift Hampers', 'url' => '/shop']));

        $this->get('/')->assertOk()->assertSee('Gift Hampers');
    }

    public function test_an_inactive_item_never_renders(): void
    {
        SiteBlock::create($this->payload(['title_en' => 'Hidden Link', 'is_active' => false]));

        $this->get('/')->assertOk()->assertDontSee('Hidden Link');
    }

    public function test_sort_order_decides_the_running_order(): void
    {
        SiteBlock::query()->group('payment')->delete();
        SiteBlock::create(['group' => 'payment', 'title_en' => 'Second Chip', 'sort_order' => 5]);
        SiteBlock::create(['group' => 'payment', 'title_en' => 'First Chip', 'sort_order' => 1]);

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertLessThan(strpos($html, 'Second Chip'), strpos($html, 'First Chip'));
    }

    public function test_a_bengali_title_is_used_and_falls_back_to_english(): void
    {
        SiteBlock::query()->group('header_menu')->delete();
        SiteBlock::create(['group' => 'header_menu', 'title_en' => 'Deals', 'title_bn' => 'ডিলস', 'url' => '/shop']);
        // No Bangla of its own: it has to keep showing the English wording
        // rather than going blank in the Bangla storefront.
        SiteBlock::create(['group' => 'header_menu', 'title_en' => 'Blog', 'url' => '/shop']);

        $this->get(route('lang.switch', 'bn'))->assertRedirect();

        $this->get('/')->assertOk()
            ->assertSee('ডিলস')
            ->assertSee('Blog')
            ->assertDontSee('Deals');
    }

    public function test_a_service_card_can_quote_the_live_delivery_threshold(): void
    {
        Setting::put('free_delivery_threshold', 3500);
        SiteBlock::query()->group('service')->delete();
        SiteBlock::create([
            'group' => 'service',
            'title_en' => 'Free Delivery',
            'subtitle_en' => 'On orders over ৳:threshold',
        ]);

        $this->get('/')->assertOk()->assertSee('On orders over ৳3,500');
    }

    public function test_a_bare_path_is_resolved_against_the_site_root(): void
    {
        $block = SiteBlock::create($this->payload(['url' => 'shop']));

        $this->assertSame(url('/shop'), $block->link);
        $this->assertFalse($block->is_external);
    }

    public function test_a_full_url_is_left_alone_and_marked_external(): void
    {
        $block = SiteBlock::create($this->payload(['url' => 'https://example.com/deals']));

        $this->assertSame('https://example.com/deals', $block->link);
        $this->assertTrue($block->is_external);
    }

    public function test_an_empty_list_simply_drops_its_strip(): void
    {
        SiteBlock::query()->group('payment')->delete();

        $this->get('/')->assertOk()->assertDontSee('pc-payment-chip', false);
    }

    /* ------------------------------------------------------------ the panel */

    public function test_the_admin_can_create_an_item(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.blocks.store'), $this->payload())
            ->assertRedirect(route('admin.blocks.index', ['group' => 'header_menu']));

        $this->assertDatabaseHas('site_blocks', ['title_en' => 'Offers', 'icon' => 'tag']);
    }

    public function test_a_field_the_group_does_not_use_is_not_stored(): void
    {
        // A payment chip has no link, so one posted anyway must be dropped
        // rather than saved for nothing to render.
        $this->actingAs($this->admin())
            ->post(route('admin.blocks.store'), $this->payload([
                'group' => 'payment',
                'title_en' => 'Rocket',
                'url' => '/somewhere',
            ]))
            ->assertRedirect();

        $this->assertDatabaseHas('site_blocks', ['title_en' => 'Rocket', 'url' => null]);
    }

    public function test_an_unknown_group_is_rejected(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.blocks.store'), $this->payload(['group' => 'nonsense']))
            ->assertSessionHasErrors('group');
    }

    public function test_an_icon_has_to_look_like_an_icon_name(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.blocks.store'), $this->payload(['icon' => '<script>x</script>']))
            ->assertSessionHasErrors('icon');
    }

    public function test_the_admin_can_edit_and_delete_an_item(): void
    {
        $block = SiteBlock::create($this->payload());
        $admin = $this->admin();

        $this->actingAs($admin)
            ->put(route('admin.blocks.update', $block), $this->payload(['title_en' => 'Renamed']))
            ->assertRedirect();

        $this->assertDatabaseHas('site_blocks', ['id' => $block->id, 'title_en' => 'Renamed']);

        $this->actingAs($admin)
            ->delete(route('admin.blocks.destroy', $block))
            ->assertRedirect();

        $this->assertDatabaseMissing('site_blocks', ['id' => $block->id]);
    }

    public function test_every_group_tab_renders(): void
    {
        $admin = $this->admin();

        foreach (array_keys(SiteBlock::GROUPS) as $group) {
            $this->actingAs($admin)
                ->get(route('admin.blocks.index', ['group' => $group]))
                ->assertOk()
                ->assertSee(SiteBlock::groupLabel($group));
        }
    }

    public function test_the_create_and_edit_forms_render(): void
    {
        $block = SiteBlock::create($this->payload());
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get(route('admin.blocks.create', ['group' => 'promo']))
            ->assertOk()
            ->assertSee('Promo Tiles');

        $this->actingAs($admin)
            ->get(route('admin.blocks.edit', $block))
            ->assertOk()
            ->assertSee('Offers');
    }

    public function test_a_guest_cannot_reach_the_panel(): void
    {
        $this->get(route('admin.blocks.index'))->assertRedirect(route('login'));
    }
}
