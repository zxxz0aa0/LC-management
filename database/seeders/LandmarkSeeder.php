<?php

namespace Database\Seeders;

use App\Models\Landmark;
use Illuminate\Database\Seeder;

class LandmarkSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $landmarks = [
            // 交通相關地標
            [
                'name' => '台北車站',
                'address' => '中山南路1-1號',
                'city' => '台北市',
                'district' => '中正區',
                'category' => 'transport',
                'description' => '台北主要交通樞紐，包含台鐵、高鐵、捷運',
                'is_active' => true,
                'created_by' => 'System',
            ],
            [
                'name' => '桃園機場第一航廈',
                'address' => '航站南路9號',
                'city' => '桃園市',
                'district' => '大園區',
                'category' => 'transport',
                'description' => '桃園國際機場第一航廈',
                'is_active' => true,
                'created_by' => 'System',
            ],
            [
                'name' => '桃園機場第二航廈',
                'address' => '航站南路9號',
                'city' => '桃園市',
                'district' => '大園區',
                'category' => 'transport',
                'description' => '桃園國際機場第二航廈',
                'is_active' => true,
                'created_by' => 'System',
            ],
            [
                'name' => '板橋車站',
                'address' => '縣民大道二段7號',
                'city' => '新北市',
                'district' => '板橋區',
                'category' => 'transport',
                'description' => '新北市重要交通樞紐',
                'is_active' => true,
                'created_by' => 'System',
            ],

            // 醫療相關地標
            [
                'name' => '台北榮總',
                'address' => '石牌路2段201號',
                'city' => '台北市',
                'district' => '北投區',
                'category' => 'hospital',
                'description' => '台北榮民總醫院',
                'is_active' => true,
                'created_by' => 'System',
            ],
            [
                'name' => '台大醫院',
                'address' => '中山南路7號',
                'city' => '台北市',
                'district' => '中正區',
                'category' => 'hospital',
                'description' => '國立台灣大學醫學院附設醫院',
                'is_active' => true,
                'created_by' => 'System',
            ],
            [
                'name' => '三軍總醫院',
                'address' => '成功路二段325號',
                'city' => '台北市',
                'district' => '內湖區',
                'category' => 'hospital',
                'description' => '三軍總醫院',
                'is_active' => true,
                'created_by' => 'System',
            ],
            [
                'name' => '馬偕醫院',
                'address' => '中山北路二段92號',
                'city' => '台北市',
                'district' => '中山區',
                'category' => 'hospital',
                'description' => '馬偕紀念醫院',
                'is_active' => true,
                'created_by' => 'System',
            ],
            [
                'name' => '亞東醫院',
                'address' => '南雅南路二段21號',
                'city' => '新北市',
                'district' => '板橋區',
                'category' => 'hospital',
                'description' => '亞東紀念醫院',
                'is_active' => true,
                'created_by' => 'System',
            ],

            // 政府機關
            [
                'name' => '總統府',
                'address' => '重慶南路一段122號',
                'city' => '台北市',
                'district' => '中正區',
                'category' => 'government',
                'description' => '中華民國總統府',
                'is_active' => true,
                'created_by' => 'System',
            ],
            [
                'name' => '台北市政府',
                'address' => '市府路1號',
                'city' => '台北市',
                'district' => '信義區',
                'category' => 'government',
                'description' => '台北市政府',
                'is_active' => true,
                'created_by' => 'System',
            ],
            [
                'name' => '新北市政府',
                'address' => '中山路一段161號',
                'city' => '新北市',
                'district' => '板橋區',
                'category' => 'government',
                'description' => '新北市政府',
                'is_active' => true,
                'created_by' => 'System',
            ],

            // 教育機構
            [
                'name' => '台灣大學',
                'address' => '羅斯福路四段1號',
                'city' => '台北市',
                'district' => '大安區',
                'category' => 'education',
                'description' => '國立台灣大學',
                'is_active' => true,
                'created_by' => 'System',
            ],
            [
                'name' => '政治大學',
                'address' => '指南路二段64號',
                'city' => '台北市',
                'district' => '文山區',
                'category' => 'education',
                'description' => '國立政治大學',
                'is_active' => true,
                'created_by' => 'System',
            ],

            // 商業區域
            [
                'name' => '信義威秀',
                'address' => '松壽路20號',
                'city' => '台北市',
                'district' => '信義區',
                'category' => 'commercial',
                'description' => '信義威秀影城',
                'is_active' => true,
                'created_by' => 'System',
            ],
            [
                'name' => '台北101',
                'address' => '信義路五段7號',
                'city' => '台北市',
                'district' => '信義區',
                'category' => 'commercial',
                'description' => '台北101大樓',
                'is_active' => true,
                'created_by' => 'System',
            ],
            [
                'name' => '西門紅樓',
                'address' => '成都路10號',
                'city' => '台北市',
                'district' => '萬華區',
                'category' => 'commercial',
                'description' => '西門紅樓文創園區',
                'is_active' => true,
                'created_by' => 'System',
            ],

            // 一般地標
            [
                'name' => '中正紀念堂',
                'address' => '中山南路21號',
                'city' => '台北市',
                'district' => '中正區',
                'category' => 'general',
                'description' => '中正紀念堂',
                'is_active' => true,
                'created_by' => 'System',
            ],
            [
                'name' => '國父紀念館',
                'address' => '仁愛路四段505號',
                'city' => '台北市',
                'district' => '信義區',
                'category' => 'general',
                'description' => '國父紀念館',
                'is_active' => true,
                'created_by' => 'System',
            ],
            [
                'name' => '陽明山國家公園',
                'address' => '竹子湖路1-20號',
                'city' => '台北市',
                'district' => '北投區',
                'category' => 'general',
                'description' => '陽明山國家公園管理處',
                'is_active' => true,
                'created_by' => 'System',
            ],
        ];

        foreach ($landmarks as $landmark) {
            Landmark::create($landmark);
        }
    }
}
