<?php

namespace Database\Seeders;

use Exception;
use App\Models\Town;
use App\Models\County;
use App\Models\Region;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class RegionSeeder extends Seeder {
    protected $regions = [
        'north' => ['北部', ['A', 'C', 'F', 'G', 'H', 'J', 'O']],
        'central' => ['中部', ['B', 'K', 'M', 'N', 'P']],
        'south' => ['南部', ['D', 'E', 'I', 'Q', 'T', 'X']],
        'east' => ['東部', ['U', 'V']],
        'outer_island' => ['外島', ['W', 'Z']]
    ];
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run() {
        try {
            DB::beginTransaction();
            $this->insertRegions();
            $this->insertCountys();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error($e->getMessage());
            return;
        }
        DB::commit();
    }

    /**
     * 取得當前時間
     *
     * @return \Carbon\Carbon
     */
    private function now() {
        return  \Carbon\Carbon::now();
    }

    /**
     * insesrt 北、中、南、東、外島區域
     *
     * @return void
     */
    private function insertRegions() {
        Region::insert(array_map(function ($code, $region) {
            return [
                'code' => $code,
                'name' => $region[0],
                'created_at' => $this->now(),
                'updated_at' => $this->now()
            ];
        }, array_keys($this->regions), array_values($this->regions)));
    }

    /**
     * insert 政府資料開放平台-縣市清單
     *
     * @return void
     */
    private function insertCountys() {
        $response = Http::get('https://api.nlsc.gov.tw/other/ListCounty');
        $xml = simplexml_load_string($response->body());
        $counties = json_decode(json_encode($xml), false)->countyItem;

        foreach ($counties as $countyItem) {
            $region_id = $this->getRegionId($countyItem->countycode);
            if (!$region_id) {
                throw new Exception("{$countyItem->countyname} region error");
            }
            $county = County::create([
                'code' => $countyItem->countycode,
                'code01' => $countyItem->countycode01,
                'name' => $countyItem->countyname,
                'region_id' => $region_id
            ]);
            $this->command->info("add county：{$county->name}");
            //$this->insertCountyTowns($county);
        }
        $this->command->info("successfully add " . count($counties) . " counties");
    }

    /**
     * insert 政府資料開放平台-縣市-鄉鎮市區清單
     *
     * @param \App\Models\County $county
     * @return void
     */
    private function insertCountyTowns($county) {
        $db_towns = [];
        $response = Http::get("https://api.nlsc.gov.tw/other/ListTown1/{$county->code}");
        $xml = simplexml_load_string($response->body());
        $towns = json_decode(json_encode($xml), false)->townItem;
        foreach ($towns as $town) {
            array_push($db_towns, [
                'county_id' => $county->id,
                'code' => $town->towncode,
                'code01' => $town->towncode01,
                'name' => $town->townname,
                'created_at' => $this->now(),
                'updated_at' => $this->now()
            ]);
        }
        Town::insert($db_towns);
        $this->command->info("successfully add " . count($db_towns) . " towns");
    }

    /**
     * 根據countycode取得對應區域
     *
     * @param string $countycode
     * @return int|boolean
     */
    private function getRegionId($countycode) {
        foreach ($this->regions as $code => $region_division) {
            if (in_array($countycode, $region_division[1])) {
                $regions = Region::where('code', '=', $code)->first();
                return $regions ? $regions->id : false;
            }
        }
        return false;
    }
}
