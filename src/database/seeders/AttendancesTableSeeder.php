<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class AttendancesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // 実際に存在するユーザーIDを取得
        $userIds = User::pluck('id')->toArray();

        if (empty($userIds)) {
            throw new \Exception('No users found. Please create users before running ExhibitionsTableSeeder.');
        }

        DB::table('exhibitions')->insert([
            [
                'user_id' => $userIds[0],
                'date' => 20250701,
                'start_time' => '9:00',
                'end_time' => '18:00',
                'note' => '',
            ],
            [
                'seller_id' => $userIds[0],
                'condition_id' => '2',
                'name' => 'HDD',
                'brand' => '',
                'price' => '5000',
                'description' => '高速で信頼性の高いハードディスク',
                'img_url' => 'img/HDD.jpg',
            ],
            [
                'seller_id' => count($userIds) > 1 ? $userIds[2] : $userIds[0],
                'condition_id' => '3',
                'name' => '玉ねぎ3束',
                'brand' => 'コーチ農園',
                'price' => '300',
                'description' => '新鮮な玉ねぎ3束のセット',
                'img_url' => 'img/onions.jpg',
            ],
            [
                'seller_id' => count($userIds) > 1 ? $userIds[2] : $userIds[0],
                'condition_id' => '4',
                'name' => '革靴',
                'brand' => '',
                'price' => '4000',
                'description' => 'クラシックなデザインの革靴',
                'img_url' => 'img/leather-shoes.jpg',
            ],
            [
                'seller_id' => count($userIds) > 1 ? $userIds[2] : $userIds[0],
                'condition_id' => '1',
                'name' => 'ノートPC',
                'brand' => '',
                'price' => '45000',
                'description' => '高性能なノートパソコン',
                'img_url' => 'img/laptop.jpg',
            ],
            [
                'seller_id' => $userIds[0],
                'condition_id' => '2',
                'name' => 'マイク',
                'brand' => '',
                'price' => '8000',
                'description' => '高音質のレコーディング用マイク',
                'img_url' => 'img/microphone.jpg',
            ],
            [
                'seller_id' => count($userIds) > 1 ? $userIds[1] : $userIds[0],
                'condition_id' => '3',
                'name' => 'ショルダーバッグ',
                'brand' => '',
                'price' => '3500',
                'description' => 'おしゃれなショルダーバッグ',
                'img_url' => 'img/shoulder-bag.jpg',
            ],
            [
                'seller_id' => $userIds[0],
                'condition_id' => '4',
                'name' => 'タンブラー',
                'brand' => '',
                'price' => '500',
                'description' => '使いやすいタンブラー',
                'img_url' => 'img/tumbler.jpg',
            ],
            [
                'seller_id' => count($userIds) > 1 ? $userIds[1] : $userIds[0],
                'condition_id' => $userIds[0],
                'name' => 'コーヒーミル',
                'brand' => '',
                'price' => '4000',
                'description' => '手動のコーヒーミル',
                'img_url' => 'img/coffee-grinder.jpg',
            ],
            [
                'seller_id' => count($userIds) > 1 ? $userIds[1] : $userIds[0],
                'condition_id' => '2',
                'name' => 'メイクセット',
                'brand' => '',
                'price' => '2500',
                'description' => '便利なメイクアップセット',
                'img_url' => 'img/makeup-set.jpg',
            ],
        ]);
    }
}
