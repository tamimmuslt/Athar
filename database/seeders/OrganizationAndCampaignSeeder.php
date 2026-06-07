<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Organization;
use App\Models\Campaign;
use Illuminate\Support\Facades\Hash;

class OrganizationAndCampaignSeeder extends Seeder
{
    public function run(): void
    {
        // 1. إنشاء مؤسسة: الهلال الأحمر العربي السوري
        $sarc = Organization::create([
            'org_name'              => 'Syrian Arab Red Crescent',
            'official_email'        => 'syrian_red_crescent@gmail.com',
            'password'              => Hash::make('12345678'), // كلمة المرور للتجربة
            'phone_number'          => '+963-976-976-9779',
            'address'               => 'Aleppo',
            'org_description'       => 'A non-profit organization dedicated to environmental protection and community development through volunteer initiatives and awareness campaigns.',
            'verification_document' => 'verification_documents/sarc_doc.pdf',
            'website_link'          => 'https://sarc.org',
            'status'                => 'approved', // مقبولة مباشرة لتظهر في الفحص
            'logo'                  => 'logos/sarc.png', // مسار افتراضي للشعار
        ]);
        
        // إضافة حملات تابعة للهلال الأحمر
        Campaign::create([
            'organization_id'     => $sarc->id,
            'title'               => 'Tree Planting Campaign',
            'description'         => 'An environmental campaign aimed at planting 1000 trees in Aleppo to combat climate change.',
            'image'               => 'campaigns/tree.png',
            'location_name'       => 'Hama, al-furkan',
            'required_volunteers' => 50,
            'start_date'          => '2026-06-10',
            'end_date'            => '2026-06-20',
            'status'              => 'active',
        ]);

        Campaign::create([
            'organization_id'     => $sarc->id,
            'title'               => 'Blood Donation Campaign',
            'description'         => 'A humanitarian campaign to secure blood units for local hospitals.',
            'image'               => 'campaigns/blood.png',
            'location_name'       => 'Aleppo, al-zahraa',
            'required_volunteers' => 30,
            'start_date'          => '2026-06-15',
            'end_date'            => '2026-06-17',
            'status'              => 'active',
        ]);

        Campaign::create([
            'organization_id'     => $sarc->id,
            'title'               => 'Food Distribution Campaign',
            'description'         => 'Distributing food parcels to families in need within the region.',
            'image'               => 'campaigns/food.png',
            'location_name'       => 'Aleppo, al-zahraa',
            'required_volunteers' => 20,
            'start_date'          => '2026-06-12',
            'end_date' => '2026-06-15',
            'status'              => 'active',
        ]);


        // 2. إنشاء مؤسسة ثانية: منظمة اليونيسيف
        $unicef = Organization::create([
            'org_name'              => 'United Nations Children\'s Fund',
            'official_email'        => 'unicef_support@gmail.com',
            'password'              => Hash::make('12345678'),
            'phone_number'          => '+963-911-222-333',
            'address'               => 'Homs',
            'org_description'       => 'UNICEF works in over 190 countries and territories to save children\'s lives, to defend their rights, and to help them fulfill their potential.',
            'verification_document' => 'verification_documents/unicef_doc.pdf',
            'website_link'          => 'https://unicef.org',
            'status'                => 'approved',
            'logo'                  => 'logos/unicef.png',
        ]);

        // إضافة حملة تابعة لليونيسيف
        Campaign::create([
            'organization_id'     => $unicef->id,
            'title'               => 'Child Education Support',
            'description'         => 'Providing educational materials and psychological support for children in rural areas.',
            'image'               => 'campaigns/education.png',
            'location_name'       => 'Homs, center',
            'required_volunteers' => 40,
            'start_date'          => '2026-07-01',
            'end_date'            => '2026-07-10',
            'status'              => 'active',
        ]);
    }
}