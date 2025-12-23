<?php

namespace Database\Seeders;

use App\Models\LocationAuditRecord;
use App\Models\LocationSupplier;
use App\Models\Toilet;
use Illuminate\Database\Seeder;

class LocationAuditRecordSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $records = Toilet
            ::select([
                'location_id',
                'creator_id',
            ])
            ->groupBy([
                'location_id',
                'creator_id',
            ])
            ->get()
            ->each(function ($record) {
                $locationAuditResord = new LocationAuditRecord();
                $locationAuditResord->location_id = $record->location_id;
                $locationAuditResord->supplier_id = $record->creator_id;
                $locationAuditResord->status = LocationAuditRecord::STATUS_ACCEPT;
                $locationAuditResord->generateToken();
                $locationAuditResord->save();

                $locationSupplier = new LocationSupplier();
                $locationSupplier->location_id = $record->location_id;
                $locationSupplier->supplier_id = $record->creator_id;
                $locationSupplier->status = LocationSupplier::STATUS_PERMISSION;
                $locationSupplier->save();
            });
    }
}
