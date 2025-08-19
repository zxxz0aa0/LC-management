<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CustomerTemplateExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return collect([
            [
                'name' => '王小明',
                'id_number' => 'A123456789',
                'birthday' => '1980-01-15',
                'gender' => '男',
                'phone_number' => '0912345678,0987654321',
                'addresses' => '台北市大安區忠孝東路四段123號,台北市信義區信義路五段456號',
                'contact_person' => '王小華',
                'contact_phone' => '0955123456',
                'contact_relationship' => '兒子',
                'email' => 'example@email.com',
                'wheelchair' => '是',
                'stair_climbing_machine' => '否',
                'ride_sharing' => '是',
                'identity' => '市區－一般',
                'note' => '輕度失智，需要協助',
                'a_mechanism' => 'A單位',
                'a_manager' => '李個管師',
                'special_status' => '一般',
                'county_care' => '台北市',
                'service_company' => '太豐',
                'referral_date' => '2024-01-01',
                'status' => '開案中',
            ],
            [
                'name' => '陳小美',
                'id_number' => 'B987654321',
                'birthday' => '1975-05-20',
                'gender' => '女',
                'phone_number' => '0966777888',
                'addresses' => '新北市板橋區中山路二段789號',
                'contact_person' => '陳小強',
                'contact_phone' => '0933444555',
                'contact_relationship' => '配偶',
                'email' => '',
                'wheelchair' => '否',
                'stair_climbing_machine' => '是',
                'ride_sharing' => '否',
                'identity' => '市區－中低收',
                'note' => '行動不便，需輪椅',
                'a_mechanism' => 'A單位例如耕莘醫院',
                'a_manager' => '張個管師',
                'special_status' => '個管單',
                'county_care' => '新北市',
                'service_company' => '大立亨',
                'referral_date' => '2024-02-15',
                'status' => '開案中',
            ],
            [
                'name' => '李阿公',
                'id_number' => 'C555666777',
                'birthday' => '1945-12-10',
                'gender' => '男',
                'phone_number' => '0922333444',
                'addresses' => '桃園市中壢區中央路三段321號',
                'contact_person' => '李小花',
                'contact_phone' => '0911222333',
                'contact_relationship' => '女兒',
                'email' => '',
                'wheelchair' => '是',
                'stair_climbing_machine' => '是',
                'ride_sharing' => '是',
                'identity' => '市區－低收',
                'note' => '重度失能，需要專業照護',
                'a_mechanism' => 'A單位名稱',
                'a_manager' => '黃個管師',
                'special_status' => '黑名單',
                'county_care' => '桃園市',
                'service_company' => '太豐與大立亨',
                'referral_date' => '2024-03-01',
                'status' => '暫停中',
            ],
        ]);
    }

    public function headings(): array
    {
        return [
            'name',
            'id_number',
            'birthday',
            'gender',
            'phone_number',
            'addresses',
            'contact_person',
            'contact_phone',
            'contact_relationship',
            'email',
            'wheelchair',
            'stair_climbing_machine',
            'ride_sharing',
            'identity',
            'note',
            'a_mechanism',
            'a_manager',
            'special_status',
            'county_care',
            'service_company',
            'referral_date',
            'status',
        ];
    }
}
