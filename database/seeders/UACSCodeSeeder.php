<?php
// database/seeders/UACSCodeSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\UACSCode;

class UACSCodeSeeder extends Seeder
{
    public function run()
    {
        // ==========================================
        // LEVEL 0: ROOT CATEGORIES
        // ==========================================
        $expenses = UACSCode::create([
            'parent_id' => null,
            'uacscode' => '',
            'description' => 'Expenses',
            'level' => 0,
            'is_selectable' => false,
            'sort_order' => 1,
        ]);

        $maintenanceAndOtherOperatingExpenses = UACSCode::create([
            'parent_id' => null,
            'uacscode' => '',
            'description' => 'Maintenance and Other Operating Expenses',
            'level' => 0,
            'is_selectable' => false,
            'sort_order' => 2,
        ]);

        $assets = UACSCode::create([
            'parent_id' => null,
            'uacscode' => '',
            'description' => 'Assets',
            'level' => 0,
            'is_selectable' => false,
            'sort_order' => 3,
        ]);

        // ==========================================
        // LEVEL 1: SUBCATEGORIES
        // ==========================================
        
        // Under Expenses
        $financialAssistance = UACSCode::create([
            'parent_id' => $expenses->id,
            'uacscode' => '5021400000',
            'description' => 'Financial Assistance/Subsidy',
            'level' => 1,
            'is_selectable' => false,
            'sort_order' => 1,
        ]);

        // Under Maintenance and Other Operating Expenses
        $travelingExpenses = UACSCode::create([
            'parent_id' => $maintenanceAndOtherOperatingExpenses->id,
            'uacscode' => '5020100000',
            'description' => 'Traveling Expenses',
            'level' => 1,
            'is_selectable' => false,
            'sort_order' => 2,
        ]);

        // Under Assets
        $otherAssets = UACSCode::create([
            'parent_id' => $assets->id,
            'uacscode' => '1990000000',
            'description' => 'Other Assets',
            'level' => 1,
            'is_selectable' => false,
            'sort_order' => 1,
        ]);

        // ==========================================
        // LEVEL 2: SUBCATEGORIES AND SELECTABLE CODES
        // ==========================================
        
        // Under Financial Assistance (Expenses)
        UACSCode::create([
            'parent_id' => $financialAssistance->id,
            'uacscode' => '5021499000',
            'description' => 'Subsidies - Other',
            'level' => 2,
            'is_selectable' => true,
            'sort_order' => 1,
        ]);

        // Under Traveling Expenses (Maintenance)
        UACSCode::create([
            'parent_id' => $travelingExpenses->id,
            'uacscode' => '5020101000',
            'description' => 'Traveling Expenses - Local',
            'level' => 2,
            'is_selectable' => true,
            'sort_order' => 1,
        ]);

        UACSCode::create([
            'parent_id' => $travelingExpenses->id,
            'uacscode' => '5020102000',
            'description' => 'Traveling Expenses - Foreign',
            'level' => 2,
            'is_selectable' => true,
            'sort_order' => 2,
        ]);

        // Under Other Assets (Assets) - Non-selectable (has children)
        $advances = UACSCode::create([
            'parent_id' => $otherAssets->id,
            'uacscode' => '1990100000',
            'description' => 'Advances',
            'level' => 2,
            'is_selectable' => false,
            'sort_order' => 1,
        ]);

        // ==========================================
        // LEVEL 3: SELECTABLE CODES under Advances
        // ==========================================
        UACSCode::create([
            'parent_id' => $advances->id,
            'uacscode' => '1990101000',
            'description' => 'Advances for Operating Expenses',
            'level' => 3,
            'is_selectable' => true,
            'sort_order' => 1,
        ]);

        UACSCode::create([
            'parent_id' => $advances->id,
            'uacscode' => '1990102000',
            'description' => 'Advances for Payroll',
            'level' => 3,
            'is_selectable' => true,
            'sort_order' => 2,
        ]);

        UACSCode::create([
            'parent_id' => $advances->id,
            'uacscode' => '1990103000',
            'description' => 'Advances to Special Disbursing Officer',
            'level' => 3,
            'is_selectable' => true,
            'sort_order' => 3,
        ]);

        UACSCode::create([
            'parent_id' => $advances->id,
            'uacscode' => '1990104000',
            'description' => 'Advances to Officers and Employees',
            'level' => 3,
            'is_selectable' => true,
            'sort_order' => 4,
        ]);
    }
}