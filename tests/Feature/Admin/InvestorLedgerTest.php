<?php

namespace Tests\Feature\Admin;

use App\Models\Adjustment;
use App\Models\Investment;
use App\Models\Investor;
use App\Models\User;
use App\Models\Withdrawal;
use App\Services\ProfitLossReport;
use App\Support\AdminModules;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Capital in and out.
 *
 * The rule worth guarding above all others: neither ledger may reach the profit
 * figure. Money an owner puts in is not something the shop earned, and money
 * they take back out is not something it spent — but both really move cash, so
 * both belong in the account table underneath.
 */
class InvestorLedgerTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->superAdmin()->create();
    }

    private function investor(array $attributes = []): Investor
    {
        return Investor::create(array_merge([
            'name' => 'Saiful Islam',
            'phone' => '01700000000',
            'is_active' => true,
        ], $attributes));
    }

    /* ----------------------------------------------------------- balances */

    public function test_a_balance_is_what_went_in_less_what_came_out(): void
    {
        $investor = $this->investor();

        Investment::create([
            'investor_id' => $investor->id, 'amount' => 500000,
            'invested_at' => now()->toDateString(), 'received_in' => 'bank',
        ]);
        Investment::create([
            'investor_id' => $investor->id, 'amount' => 100000,
            'invested_at' => now()->toDateString(), 'received_in' => 'cash',
        ]);
        Withdrawal::create([
            'investor_id' => $investor->id, 'amount' => 80000,
            'withdrawn_at' => now()->toDateString(), 'paid_from' => 'bkash',
        ]);

        $this->assertSame(600000.0, $investor->totalInvested());
        $this->assertSame(80000.0, $investor->totalWithdrawn());
        $this->assertSame(520000.0, $investor->balance());
    }

    /** Drawing profit puts an investor below zero, which is not an error. */
    public function test_a_balance_may_go_negative(): void
    {
        $investor = $this->investor();

        Investment::create([
            'investor_id' => $investor->id, 'amount' => 1000,
            'invested_at' => now()->toDateString(),
        ]);
        Withdrawal::create([
            'investor_id' => $investor->id, 'amount' => 2500,
            'withdrawn_at' => now()->toDateString(),
        ]);

        $this->assertSame(-1500.0, $investor->balance());
    }

    /**
     * The list sums in one query. If withSum's null for an investor with no
     * rows were read as "not asked", the fallback would fire for exactly the
     * rows the sum was meant to save.
     */
    public function test_the_list_reads_its_totals_without_a_query_per_row(): void
    {
        $this->investor(['name' => 'Nobody']);

        $row = Investor::query()
            ->withSum('investments', 'amount')
            ->withSum('withdrawals', 'amount')
            ->first();

        \DB::enableQueryLog();

        $this->assertSame(0.0, $row->totalInvested());
        $this->assertSame(0.0, $row->totalWithdrawn());
        $this->assertSame(0.0, $row->balance());

        $this->assertCount(0, \DB::getQueryLog(), 'Reading the totals hit the database.');

        \DB::disableQueryLog();
    }

    public function test_a_statement_lists_both_ledgers_newest_first(): void
    {
        $investor = $this->investor();

        Investment::create([
            'investor_id' => $investor->id, 'amount' => 1000,
            'invested_at' => '2026-01-10',
        ]);
        Withdrawal::create([
            'investor_id' => $investor->id, 'amount' => 400,
            'withdrawn_at' => '2026-03-05',
        ]);
        Investment::create([
            'investor_id' => $investor->id, 'amount' => 700,
            'invested_at' => '2026-02-01',
        ]);

        $statement = $investor->load(['investments', 'withdrawals'])->statement();

        $this->assertSame(['withdrawal', 'investment', 'investment'], $statement->pluck('type')->all());
        $this->assertSame([400.0, 700.0, 1000.0], $statement->pluck('amount')->all());
    }

    /* ----------------------------------------------------------- the rule */

    public function test_capital_never_reaches_the_profit_figure(): void
    {
        $investor = $this->investor();

        Investment::create([
            'investor_id' => $investor->id, 'amount' => 500000,
            'invested_at' => now()->toDateString(), 'received_in' => 'bank',
        ]);
        Withdrawal::create([
            'investor_id' => $investor->id, 'amount' => 20000,
            'withdrawn_at' => now()->toDateString(), 'paid_from' => 'cash',
        ]);

        $report = ProfitLossReport::between(null, null)->summary();

        // No sales, no expenses, no damage — so the profit is zero however much
        // capital moved through the shop this month.
        $this->assertSame(0.0, $report['net_profit']);
        $this->assertSame(0.0, $report['gross_profit']);
        $this->assertSame(0.0, $report['expenses']);

        $this->assertSame(500000.0, $report['invested']);
        $this->assertSame(20000.0, $report['withdrawn']);
    }

    public function test_capital_does_move_the_account_table(): void
    {
        $investor = $this->investor();

        Investment::create([
            'investor_id' => $investor->id, 'amount' => 500000,
            'invested_at' => now()->toDateString(), 'received_in' => 'bank',
        ]);
        Withdrawal::create([
            'investor_id' => $investor->id, 'amount' => 20000,
            'withdrawn_at' => now()->toDateString(), 'paid_from' => 'bank',
        ]);

        $bank = ProfitLossReport::between(null, null)->summary()['accounts']['bank'];

        $this->assertSame(500000.0, $bank['in']);
        $this->assertSame(20000.0, $bank['out']);
        $this->assertSame(480000.0, $bank['net']);
        $this->assertSame(500000.0, $bank['invested']);
        $this->assertSame(20000.0, $bank['withdrawn']);
    }

    /** A movement outside the range is somebody else's month. */
    public function test_capital_outside_the_range_is_left_out(): void
    {
        $investor = $this->investor();

        Investment::create([
            'investor_id' => $investor->id, 'amount' => 9999,
            'invested_at' => '2020-01-01', 'received_in' => 'cash',
        ]);

        $report = ProfitLossReport::between('2026-09-01', '2026-09-30')->summary();

        $this->assertSame(0.0, $report['invested']);
    }

    /**
     * Every account head keeps its columns whether or not anything happened,
     * so the table does not change shape between two months.
     */
    public function test_untouched_heads_still_report_zero_capital(): void
    {
        $accounts = ProfitLossReport::between(null, null)->summary()['accounts'];

        foreach ($accounts as $head) {
            $this->assertArrayHasKey('invested', $head);
            $this->assertArrayHasKey('withdrawn', $head);
            $this->assertSame(0.0, $head['invested']);
        }
    }

    /* ---------------------------------------------------------- the admin */

    public function test_an_investor_can_be_created_and_listed(): void
    {
        $this->actingAs($this->admin())
            ->post('/admin/investors', [
                'name' => 'Rakib',
                'phone' => '01800000000',
                'is_active' => '1',
            ])
            ->assertRedirect('/admin/investors');

        $this->assertDatabaseHas('investors', ['name' => 'Rakib', 'is_active' => true]);

        $this->actingAs($this->admin())
            ->get('/admin/investors')
            ->assertOk()
            ->assertSee('Rakib');
    }

    public function test_an_investment_can_be_recorded(): void
    {
        $investor = $this->investor();

        $this->actingAs($this->admin())
            ->post('/admin/investments', [
                'investor_id' => $investor->id,
                'amount' => '250000',
                'invested_at' => '2026-09-07',
                'received_in' => 'bkash',
                'notes' => 'Mango season float',
            ])
            ->assertRedirect('/admin/investments');

        $this->assertDatabaseHas('investments', [
            'investor_id' => $investor->id,
            'amount' => '250000.00',
            'received_in' => 'bkash',
        ]);
    }

    public function test_a_withdrawal_beyond_the_balance_saves_and_warns(): void
    {
        $investor = $this->investor();

        Investment::create([
            'investor_id' => $investor->id, 'amount' => 1000,
            'invested_at' => now()->toDateString(),
        ]);

        $this->actingAs($this->admin())
            ->post('/admin/withdrawals', [
                'investor_id' => $investor->id,
                'amount' => '2500',
                'withdrawn_at' => now()->toDateString(),
                'paid_from' => 'cash',
            ])
            ->assertRedirect('/admin/withdrawals')
            ->assertSessionHas('success')
            // Flagged, not refused: the money genuinely left.
            ->assertSessionHas('warning');

        $this->assertDatabaseHas('withdrawals', ['investor_id' => $investor->id, 'amount' => '2500.00']);
    }

    public function test_a_withdrawal_within_the_balance_says_nothing(): void
    {
        $investor = $this->investor();

        Investment::create([
            'investor_id' => $investor->id, 'amount' => 5000,
            'invested_at' => now()->toDateString(),
        ]);

        $this->actingAs($this->admin())
            ->post('/admin/withdrawals', [
                'investor_id' => $investor->id,
                'amount' => '1000',
                'withdrawn_at' => now()->toDateString(),
            ])
            ->assertRedirect('/admin/withdrawals')
            ->assertSessionMissing('warning');
    }

    public function test_money_has_to_belong_to_somebody(): void
    {
        $this->actingAs($this->admin())
            ->post('/admin/investments', [
                'amount' => '1000',
                'invested_at' => now()->toDateString(),
            ])
            ->assertSessionHasErrors('investor_id');
    }

    public function test_an_account_head_that_is_not_a_head_is_rejected(): void
    {
        $investor = $this->investor();

        $this->actingAs($this->admin())
            ->post('/admin/investments', [
                'investor_id' => $investor->id,
                'amount' => '1000',
                'invested_at' => now()->toDateString(),
                'received_in' => 'shoebox',
            ])
            ->assertSessionHasErrors('received_in');
    }

    /**
     * An investor with history is the only record of where that money came
     * from. Retiring them is the way out, not deletion.
     */
    public function test_an_investor_with_money_against_them_cannot_be_deleted(): void
    {
        $investor = $this->investor();

        Investment::create([
            'investor_id' => $investor->id, 'amount' => 1000,
            'invested_at' => now()->toDateString(),
        ]);

        $this->actingAs($this->admin())
            ->delete('/admin/investors/'.$investor->id)
            ->assertSessionHasErrors('delete');

        $this->assertDatabaseHas('investors', ['id' => $investor->id]);
    }

    public function test_an_investor_with_no_history_can_be_deleted(): void
    {
        $investor = $this->investor();

        $this->actingAs($this->admin())
            ->delete('/admin/investors/'.$investor->id)
            ->assertRedirect('/admin/investors');

        $this->assertDatabaseMissing('investors', ['id' => $investor->id]);
    }

    /** An inactive investor is off the picker but still on their own record. */
    public function test_an_inactive_investor_is_not_offered_for_new_money(): void
    {
        $this->investor(['name' => 'Retired Partner', 'is_active' => false]);
        $this->investor(['name' => 'Active Partner']);

        $this->actingAs($this->admin())
            ->get('/admin/investments/create')
            ->assertOk()
            ->assertSee('Active Partner')
            ->assertDontSee('Retired Partner');
    }

    public function test_editing_an_old_record_still_offers_its_own_investor(): void
    {
        $retired = $this->investor(['name' => 'Retired Partner', 'is_active' => false]);

        $investment = Investment::create([
            'investor_id' => $retired->id, 'amount' => 1000,
            'invested_at' => now()->toDateString(),
        ]);

        // Otherwise saving an unrelated edit would silently move the money.
        $this->actingAs($this->admin())
            ->get('/admin/investments/'.$investment->id.'/edit')
            ->assertOk()
            ->assertSee('Retired Partner');
    }

    public function test_the_statement_page_shows_both_ledgers(): void
    {
        $investor = $this->investor();

        Investment::create([
            'investor_id' => $investor->id, 'amount' => 1000,
            'invested_at' => now()->toDateString(), 'notes' => 'Opening capital',
        ]);
        Withdrawal::create([
            'investor_id' => $investor->id, 'amount' => 250,
            'withdrawn_at' => now()->toDateString(), 'notes' => 'Profit draw',
        ]);

        $this->actingAs($this->admin())
            ->get('/admin/investors/'.$investor->id)
            ->assertOk()
            ->assertSee('Opening capital')
            ->assertSee('Profit draw');
    }

    /* ---------------------------------------------------- wiring and gates */

    public function test_the_three_modules_are_registered_for_permissions(): void
    {
        foreach (['investors', 'investments', 'withdrawals'] as $module) {
            $this->assertArrayHasKey($module, AdminModules::MODULES);
            $this->assertContains("{$module}.view", AdminModules::permissions());
            $this->assertContains("{$module}.delete", AdminModules::permissions());
        }
    }

    public function test_a_user_without_the_permission_is_turned_away(): void
    {
        $this->seed(PermissionSeeder::class);

        $staff = User::factory()->admin()->create();
        $staff->syncPermissions(['dashboard.view']);

        foreach (['/admin/investments', '/admin/withdrawals', '/admin/investors'] as $url) {
            $this->actingAs($staff->fresh())->get($url)->assertForbidden();
        }
    }

    /** The permission that does exist opens exactly its own section. */
    public function test_the_view_permission_opens_its_own_section_only(): void
    {
        $this->seed(PermissionSeeder::class);

        $staff = User::factory()->admin()->create();
        $staff->syncPermissions(['dashboard.view', 'investments.view']);

        $this->actingAs($staff->fresh())->get('/admin/investments')->assertOk();
        $this->actingAs($staff->fresh())->get('/admin/withdrawals')->assertForbidden();

        // View is not create: the form is a separate grant.
        $this->actingAs($staff->fresh())->get('/admin/investments/create')->assertForbidden();
    }

    public function test_the_sidebar_offers_the_money_group(): void
    {
        $this->actingAs($this->admin())
            ->get('/admin')
            ->assertOk()
            ->assertSee('Investments')
            ->assertSee('Withdrawals')
            ->assertSee('Investors');
    }

    /** Adjustments are unrelated, but the report reads both — keep it honest. */
    public function test_damage_still_hits_profit_while_capital_does_not(): void
    {
        $this->assertContains('damage', ProfitLossReport::LOSSES);
        $this->assertSame(0, Adjustment::count());
    }
}
