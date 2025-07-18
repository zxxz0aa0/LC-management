<?php

namespace App\Exports;

use App\Models\Customer;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CustomersExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return Customer::all()->map(function ($customer) {
            return [
                'name' => $customer->name,
                'id_number' => $customer->id_number,
                'birthday' => $customer->birthday,
                'gender' => $customer->gender,
                'phone_number' => implode(',', $customer->phone_number ?? []),
                'addresses' => implode(',', $customer->addresses ?? []),
                'contact_person' => $customer->contact_person,
                'contact_phone' => $customer->contact_phone,
                'contact_relationship' => $customer->contact_relationship,
                'email' => $customer->email,
                'wheelchair' => $customer->wheelchair,
                'stair_climbing_machine' => $customer->stair_climbing_machine,
                'ride_sharing' => $customer->ride_sharing,
                'identity' => $customer->identity,
                'note' => $customer->note,
                'a_mechanism' => $customer->a_mechanism,
                'a_manager' => $customer->a_manager,
                'special_status' => $customer->special_status,
                'county_care' => $customer->county_care,
                'service_company' => $customer->service_company,
                'status' => $customer->status,
            ];
        });
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
            'status',
        ];
    }
}
