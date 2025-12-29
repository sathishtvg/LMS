<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(['email'=>'admin@example.com'], ['tenant_id'=>1,
            'name'=>'System Admin',
            'phone'=>'+6591111111',
            'password'=>Hash::make('Admin@12345'),
            'role'=>'admin','language'=>'en','status'=>'active'
        ]);

        User::updateOrCreate(['email'=>'trainer@example.com'], ['tenant_id'=>1,
            'name'=>'Trainer User',
            'phone'=>'+6592222222',
            'password'=>Hash::make('Trainer@12345'),
            'role'=>'trainer','language'=>'en','status'=>'active'
        ]);

        User::updateOrCreate(['email'=>'learner1@example.com'], ['tenant_id'=>1,
            'name'=>'Learner One',
            'phone'=>'+6593333333',
            'password'=>Hash::make('Learner@12345'),
            'role'=>'learner','language'=>'en','status'=>'active'
        ]);
    }
}
