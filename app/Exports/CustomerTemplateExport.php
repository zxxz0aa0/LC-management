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
                'county_care' => '新北長照',
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
                'county_care' => '台北長照',
                'service_company' => '大立亨',
                'referral_date' => '2024-02-15',
                'status' => '開案中',
            ],
            [
                'name' => '個案的名字',
                'id_number' => '身分證號',
                'birthday' => '出身年月日要是YYYY-MM-DD',
                'gender' => '姓名',
                'phone_number' => '電話',
                'addresses' => '桃園市中壢區中央路三段321號',
                'contact_person' => '李小花',
                'contact_phone' => '聯絡人電話',
                'contact_relationship' => '聯絡人與個案關係',
                'email' => '填EMAIL或不填不能填0或其他文字',
                'wheelchair' => '是否輪椅',
                'stair_climbing_machine' => '是否爬梯機',
                'ride_sharing' => '是否共乘',
                'identity' => '暫時用不到不用填',
                'note' => '備註',
                'a_mechanism' => '填A單位名稱',
                'a_manager' => '填個管師姓名',
                'special_status' => '填黑名單OR個管單OR一般',
                'county_care' => '填新北長照OR台北長照OR新北富康OR一般乘客',
                'service_company' => '太豐與大立亨',
                'referral_date' => '填照會日期',
                'status' => '填開案中或已結案',
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
