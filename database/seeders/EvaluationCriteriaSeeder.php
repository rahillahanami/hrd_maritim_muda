<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EvaluationCriteriaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('evaluation_criterias')->insert([
    ['name' => 'Disiplin', 'weight' => 0.3, 'type' => 'benefit'],
    ['name' => 'Kehadiran', 'weight' => 0.25, 'type' => 'benefit'],
    ['name' => 'Kualitas Pekerjaan', 'weight' => 0.25, 'type' => 'benefit'],
    ['name' => 'Inisiatif & Kolaborasi', 'weight' => 0.2, 'type' => 'benefit'],
]);
    }
}
