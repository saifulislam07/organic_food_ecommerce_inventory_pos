<?php

namespace App\Console\Commands;

use App\Services\CourierDispatch;
use App\Support\CourierSettings;
use Illuminate\Console\Command;

/**
 * Ask every courier where its parcels are.
 *
 * This is the half of the courier work that pays for itself: nobody has to open
 * an order and type "delivered" into it, because the company that delivered it
 * already knows. Schedule it and the orders list keeps itself honest.
 */
class SyncCourierStatuses extends Command
{
    protected $signature = 'couriers:sync
        {--courier= : Only sync parcels booked with this courier}';

    protected $description = 'Update order statuses from what the couriers report';

    public function handle(CourierDispatch $dispatch): int
    {
        $only = $this->option('courier');

        if ($only && ! array_key_exists($only, CourierSettings::driverClasses())) {
            $this->error("No courier called \"{$only}\" is installed.");

            return self::FAILURE;
        }

        if (CourierSettings::enabled() === []) {
            $this->warn('No courier is switched on — nothing to sync.');

            return self::SUCCESS;
        }

        $tally = $dispatch->syncAll($only);

        $this->info(sprintf(
            '%d parcel(s) checked, %d moved, %d could not be read.',
            $tally['checked'],
            $tally['moved'],
            $tally['failed'],
        ));

        // Deliberately a success even when some reads failed: a courier being
        // unreachable is an ordinary afternoon, and a non-zero exit here would
        // put a scheduler into a failure loop over it.
        return self::SUCCESS;
    }
}
