<?php

namespace Database\Seeders;

use App\Models\Agency;
use App\Models\AgencyKnowledgeItem;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoAgencySeeder extends Seeder
{
    private const PHOTO = 'https://images.unsplash.com/photo-%s?auto=format&fit=crop&w=1600&q=80';

    public function run(): void
    {
        $owner = User::firstOrCreate(
            ['email' => 'owner@ftsrealty.test'],
            [
                'name' => 'Owner',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        $agency = Agency::updateOrCreate(
            ['slug' => 'fts-realty'],
            [
                'name' => 'FTS Realty',
                'tagline' => 'Agen properti BSD City & Serpong — rumah, apartemen, dan ruko pilihan dengan AI Property Advisor 24 jam.',
                'description' => 'FTS Realty adalah agen properti resmi di BSD City, Tangerang Selatan. Kami memasarkan rumah tapak, townhouse, apartemen, ruko, dan kavling dari pengembang terpercaya, serta mendampingi proses KPR dan legalitas hingga serah terima kunci.',
                'translations' => [
                    'en' => [
                        'tagline' => 'Property agents for BSD City & Serpong — selected houses, apartments and shophouses with a 24/7 AI Property Advisor.',
                        'description' => 'FTS Realty is a licensed property agency in BSD City, South Tangerang. We market landed houses, townhouses, apartments, shophouses and plots from trusted developers, and guide buyers through mortgages and paperwork until the keys are handed over.',
                    ],
                    'ja' => [
                        'tagline' => 'BSDシティ・スルポンの不動産エージェント。厳選した一戸建て、アパートメント、店舗付き住宅を24時間AIアドバイザーがご案内します。',
                        'description' => 'FTS Realtyは南タンゲランのBSDシティにある公認不動産エージェントです。信頼できるデベロッパーの一戸建て、タウンハウス、アパートメント、店舗付き住宅、土地を扱い、住宅ローンや法務手続きから鍵の引き渡しまでサポートします。',
                    ],
                ],
                'address' => 'Jl. Pahlawan Seribu, Ruko Golden Boulevard Blok C-12, BSD City',
                'city' => 'Tangerang Selatan',
                'country' => 'Indonesia',
                'latitude' => -6.3016,
                'longitude' => 106.6527,
                'phone' => '+62 21 5316 8800',
                'whatsapp' => '6281288880000',
                'email' => 'hello@ftsrealty.test',
                'timezone' => 'Asia/Jakarta',
                'currency' => 'IDR',
                'default_locale' => 'id',
                'office_opens_at' => '09:00',
                'office_closes_at' => '18:00',
                'mortgage_rate' => 7.25,
                'cover_path' => sprintf(self::PHOTO, '1600596542815-ffad4c1539a9'),
                'public_status' => 'published',
            ]
        );

        $agency->users()->syncWithoutDetaching([
            $owner->id => ['role' => 'owner', 'status' => 'active'],
        ]);

        foreach ($this->properties() as $sort => $definition) {
            $images = $definition['images'];
            unset($definition['images']);

            $property = $agency->properties()->updateOrCreate(
                ['slug' => $definition['slug']],
                [...$definition, 'sort_order' => $sort, 'is_active' => true]
            );

            foreach ($images as $imageSort => [$photo, $tags, $alt]) {
                $property->images()->updateOrCreate(
                    ['image_url' => sprintf(self::PHOTO, $photo)],
                    ['tags' => $tags, 'alt_text' => $alt, 'sort_order' => $imageSort]
                );
            }
        }

        $this->seedKnowledgeBase($agency);

        $this->command?->info('Demo login: owner@ftsrealty.test — password: password');
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function properties(): array
    {
        return [
            [
                'slug' => 'rumah-cluster-asri-greenwich',
                'name' => 'Rumah Cluster Asri Greenwich',
                'description' => 'Rumah dua lantai di cluster one gate dengan taman depan dan belakang. Tata ruang terbuka, plafon tinggi, dan dapur yang menghadap taman — cocok untuk keluarga muda.',
                'translations' => [
                    'en' => ['name' => 'Asri Greenwich Cluster House', 'description' => 'A two-storey house in a one-gate cluster with front and back gardens. Open-plan living, high ceilings and a kitchen facing the garden — ideal for young families.'],
                    'ja' => ['name' => 'アスリ・グリニッジ クラスターハウス', 'description' => '前庭と裏庭のあるワンゲート管理クラスター内の2階建て住宅。開放的な間取り、高い天井、庭に面したキッチンで、若いご家族に最適です。'],
                ],
                'listing_type' => 'sale',
                'property_type' => 'house',
                'district' => 'Greenwich Park, BSD City',
                'price' => 1_850_000_000,
                'building_size_sqm' => 110,
                'land_size_sqm' => 120,
                'bedrooms' => 3,
                'bathrooms' => 2,
                'carports' => 1,
                'floors' => 2,
                'certificate' => 'shm',
                'furnishing' => 'unfurnished',
                'year_built' => 2024,
                'amenities' => ['one_gate', 'security', 'garden', 'clubhouse', 'near_school', 'water_heater'],
                'units_available' => 4,
                'is_featured' => true,
                'images' => [
                    ['1570129477492-45c003edd2be', ['exterior', 'facade'], 'Tampak depan rumah cluster'],
                    ['1600607687939-ce8a6c25118c', ['living', 'interior'], 'Ruang keluarga terbuka'],
                    ['1600210492486-724fe5c67fb0', ['kitchen'], 'Dapur menghadap taman'],
                    ['1505693416388-ac5ce068fe85', ['bedroom'], 'Kamar tidur utama'],
                ],
            ],
            [
                'slug' => 'townhouse-lavanya-modern',
                'name' => 'Townhouse Lavanya Modern',
                'description' => 'Townhouse tiga lantai bergaya tropis modern dengan rooftop pribadi dan sistem smart home. Hanya 8 unit dalam satu kawasan, dekat stasiun Rawa Buntu.',
                'translations' => [
                    'en' => ['name' => 'Lavanya Modern Townhouse', 'description' => 'A three-storey modern tropical townhouse with a private rooftop and smart-home system. Only 8 units in the compound, close to Rawa Buntu station.'],
                    'ja' => ['name' => 'ラヴァニャ モダン タウンハウス', 'description' => 'プライベートルーフトップとスマートホームを備えたモダントロピカルな3階建てタウンハウス。敷地内わずか8戸、ラワ・ブントゥ駅の近くです。'],
                ],
                'listing_type' => 'sale',
                'property_type' => 'townhouse',
                'district' => 'Rawa Buntu, Serpong',
                'price' => 2_650_000_000,
                'building_size_sqm' => 165,
                'land_size_sqm' => 96,
                'bedrooms' => 4,
                'bathrooms' => 3,
                'carports' => 2,
                'floors' => 3,
                'certificate' => 'shm',
                'furnishing' => 'semi',
                'year_built' => 2025,
                'amenities' => ['smart_home', 'rooftop', 'one_gate', 'security', 'near_station', 'kitchen_set', 'air_conditioning'],
                'units_available' => 2,
                'is_featured' => true,
                'images' => [
                    ['1600585154340-be6161a56a0c', ['exterior', 'facade'], 'Fasad townhouse modern'],
                    ['1600566753190-17f0baa2a6c3', ['living', 'interior'], 'Ruang tamu dengan jendela besar'],
                    ['1484154218962-a197022b5858', ['kitchen'], 'Dapur dengan kitchen set'],
                    ['1616594039964-ae9021a400a0', ['bedroom'], 'Kamar tidur'],
                ],
            ],
            [
                'slug' => 'rumah-hook-taman-golf',
                'name' => 'Rumah Hook Taman Golf',
                'description' => 'Rumah mewah posisi hook dengan kolam renang pribadi dan pemandangan lapangan golf. Kamar ART terpisah, carport tiga mobil, dan jalan depan 12 meter.',
                'translations' => [
                    'en' => ['name' => 'Golf Garden Corner House', 'description' => 'A luxury corner house with a private pool overlooking the golf course. Separate maid\'s quarters, a three-car carport and a 12-metre road in front.'],
                    'ja' => ['name' => 'ゴルフガーデン 角地の邸宅', 'description' => 'ゴルフ場を望むプライベートプール付きの角地の高級住宅。独立したメイドルーム、3台分のカーポート、前面道路は12メートルです。'],
                ],
                'listing_type' => 'sale',
                'property_type' => 'house',
                'district' => 'Taman Golf, BSD City',
                'price' => 5_900_000_000,
                'building_size_sqm' => 320,
                'land_size_sqm' => 300,
                'bedrooms' => 5,
                'bathrooms' => 4,
                'carports' => 3,
                'floors' => 2,
                'certificate' => 'shm',
                'furnishing' => 'semi',
                'year_built' => 2021,
                'amenities' => ['private_pool', 'golf_view', 'corner_lot', 'maid_room', 'wide_road', 'security', 'garden'],
                'units_available' => 1,
                'is_featured' => true,
                'images' => [
                    ['1613490493576-7fde63acd811', ['exterior', 'pool'], 'Rumah dengan kolam renang'],
                    ['1564013799919-ab600027ffc6', ['exterior', 'pool'], 'Kolam renang pribadi'],
                    ['1600047509807-ba8f99d2cdde', ['exterior', 'facade'], 'Tampak depan rumah hook'],
                    ['1552321554-5fefe8c9ef14', ['bathroom'], 'Kamar mandi utama'],
                ],
            ],
            [
                'slug' => 'apartemen-studio-skyline',
                'name' => 'Apartemen Studio Skyline',
                'description' => 'Unit studio di lantai 18 dengan pemandangan kota, fully furnished dan siap huni. Akses langsung ke mall dan 5 menit ke tol. Cocok untuk investasi sewa atau hunian pertama.',
                'translations' => [
                    'en' => ['name' => 'Skyline Studio Apartment', 'description' => 'A fully furnished, move-in-ready studio on the 18th floor with city views. Direct mall access and five minutes to the toll road. Suits a first home or a rental investment.'],
                    'ja' => ['name' => 'スカイライン スタジオアパートメント', 'description' => '18階のシティビュー、家具付きですぐに入居できるスタジオタイプ。モール直結、高速道路まで5分。初めての住まいや賃貸投資に。'],
                ],
                'listing_type' => 'sale',
                'property_type' => 'apartment',
                'district' => 'BSD Green Office Park',
                'price' => 680_000_000,
                'building_size_sqm' => 33,
                'land_size_sqm' => null,
                'bedrooms' => 1,
                'bathrooms' => 1,
                'carports' => 0,
                'floors' => 18,
                'certificate' => 'strata',
                'furnishing' => 'full',
                'year_built' => 2022,
                'amenities' => ['city_view', 'shared_pool', 'gym', 'security', 'air_conditioning', 'water_heater', 'parking'],
                'units_available' => 6,
                'is_featured' => false,
                'images' => [
                    ['1545324418-cc1a3fa10c00', ['exterior', 'tower'], 'Tower apartemen'],
                    ['1522708323590-d24dbb6b0267', ['living', 'interior'], 'Unit studio'],
                    ['1502672260266-1c1ef2d93688', ['living', 'interior'], 'Ruang tamu studio'],
                ],
            ],
            [
                'slug' => 'apartemen-2br-the-parc',
                'name' => 'Apartemen 2BR The Parc',
                'description' => 'Apartemen dua kamar disewakan tahunan, full furnished dengan balkon menghadap taman. Termasuk akses kolam renang, gym, dan parkir satu mobil.',
                'translations' => [
                    'en' => ['name' => 'The Parc 2-Bedroom Apartment', 'description' => 'A two-bedroom apartment for yearly rent, fully furnished with a balcony over the park. Includes pool and gym access and one parking space.'],
                    'ja' => ['name' => 'ザ・パーク 2ベッドルーム', 'description' => '公園を望むバルコニー付き、家具付きの2ベッドルーム（年単位の賃貸）。プール、ジム、駐車場1台分付き。'],
                ],
                'listing_type' => 'rent',
                'property_type' => 'apartment',
                'district' => 'The Parc, South City',
                'price' => 85_000_000,
                'building_size_sqm' => 68,
                'land_size_sqm' => null,
                'bedrooms' => 2,
                'bathrooms' => 1,
                'carports' => 1,
                'floors' => 9,
                'certificate' => 'strata',
                'furnishing' => 'full',
                'year_built' => 2020,
                'amenities' => ['balcony', 'shared_pool', 'gym', 'security', 'air_conditioning', 'kitchen_set', 'parking'],
                'units_available' => 2,
                'is_featured' => false,
                'images' => [
                    ['1560448204-e02f11c3d0e2', ['living', 'interior'], 'Ruang tamu apartemen'],
                    ['1493809842364-78817add7ffb', ['living', 'interior'], 'Area makan'],
                    ['1586023492125-27b2c045efd7', ['living', 'interior'], 'Sudut santai'],
                ],
            ],
            [
                'slug' => 'ruko-3-lantai-boulevard',
                'name' => 'Ruko 3 Lantai Boulevard',
                'description' => 'Ruko tiga lantai di jalan utama boulevard dengan lalu lintas ramai, cocok untuk kantor, klinik, atau F&B. Parkir depan luas dan dekat kawasan perkantoran.',
                'translations' => [
                    'en' => ['name' => 'Boulevard 3-Storey Shophouse', 'description' => 'A three-storey shophouse on the busy main boulevard, suited to an office, clinic or F&B business. Wide front parking and close to the office district.'],
                    'ja' => ['name' => 'ブルバード 3階建て店舗付き住宅', 'description' => '交通量の多いメインブルバード沿いの3階建て。オフィス、クリニック、飲食店に最適。前面の駐車スペースが広く、オフィス街にも近い立地です。'],
                ],
                'listing_type' => 'sale',
                'property_type' => 'shophouse',
                'district' => 'Boulevard BSD Timur',
                'price' => 4_200_000_000,
                'building_size_sqm' => 240,
                'land_size_sqm' => 90,
                'bedrooms' => 0,
                'bathrooms' => 3,
                'carports' => 2,
                'floors' => 3,
                'certificate' => 'hgb',
                'furnishing' => 'unfurnished',
                'year_built' => 2019,
                'amenities' => ['wide_road', 'parking', 'security', 'pln', 'pdam'],
                'units_available' => 1,
                'is_featured' => false,
                'images' => [
                    ['1486406146926-c627a92ad1ab', ['exterior', 'facade'], 'Deretan ruko di boulevard'],
                    ['1497366216548-37526070297c', ['interior', 'office'], 'Interior lantai kantor'],
                ],
            ],
            [
                'slug' => 'kavling-siap-bangun-the-mozia',
                'name' => 'Kavling Siap Bangun The Mozia',
                'description' => 'Kavling matang di kawasan The Mozia, siap bangun dengan izin lengkap. Listrik PLN dan air PDAM sudah masuk, lingkungan hijau dengan jogging track.',
                'translations' => [
                    'en' => ['name' => 'The Mozia Ready-to-Build Plot', 'description' => 'A serviced plot in The Mozia, ready to build with permits in place. PLN electricity and mains water are connected, in a green neighbourhood with a jogging track.'],
                    'ja' => ['name' => 'ザ・モジア 建築可能な土地', 'description' => '許可取得済みですぐに建築できるザ・モジアの造成地。電気・上水道は引き込み済み、ジョギングコースのある緑豊かな環境です。'],
                ],
                'listing_type' => 'sale',
                'property_type' => 'land',
                'district' => 'The Mozia, BSD City',
                'price' => 1_350_000_000,
                'building_size_sqm' => null,
                'land_size_sqm' => 160,
                'bedrooms' => 0,
                'bathrooms' => 0,
                'carports' => 0,
                'floors' => 1,
                'certificate' => 'shm',
                'furnishing' => null,
                'year_built' => null,
                'amenities' => ['pln', 'pdam', 'one_gate', 'jogging_track', 'garden'],
                'units_available' => 5,
                'is_featured' => false,
                'images' => [
                    ['1500382017468-9049fed747ef', ['land'], 'Lahan kavling'],
                    ['1441974231531-c6227db76b6e', ['park', 'green'], 'Area hijau di sekitar kavling'],
                ],
            ],
        ];
    }

    private function seedKnowledgeBase(Agency $agency): void
    {
        $items = [
            // Neighbourhood — shown on the "Neighbourhood" page.
            [AgencyKnowledgeItem::CATEGORY_NEIGHBOURHOOD, 'Sekolah internasional & nasional', 'Ada lebih dari 10 sekolah dalam radius 5 km, termasuk sekolah internasional berkurikulum Cambridge dan IB serta sekolah nasional unggulan dari TK hingga SMA.', ['en' => ['International & national schools', 'More than 10 schools within 5 km, including international schools teaching Cambridge and IB curricula and leading national schools from kindergarten to high school.'], 'ja' => ['インターナショナル・国内の学校', '半径5km以内に10校以上。ケンブリッジやIBカリキュラムのインターナショナルスクール、幼稚園から高校までの国内有名校があります。']], ['sekolah', 'school', 'pendidikan', 'education'], '1580582932707-520aed937b7b', '1,5 km · 5 menit'],
            [AgencyKnowledgeItem::CATEGORY_NEIGHBOURHOOD, 'Rumah sakit', 'Dua rumah sakit swasta dengan IGD 24 jam berada 2–4 km dari perumahan, dan klinik keluarga tersedia di area ruko.', ['en' => ['Hospitals', 'Two private hospitals with 24-hour emergency rooms are 2–4 km from the estates, and family clinics are in the shophouse areas.'], 'ja' => ['病院', '24時間救急のある私立病院が住宅地から2〜4kmに2か所、店舗エリアにはファミリークリニックがあります。']], ['rumah sakit', 'hospital', 'klinik', 'health'], '1519494026892-80bbd2d6fd0d', '2,4 km · 7 menit'],
            [AgencyKnowledgeItem::CATEGORY_NEIGHBOURHOOD, 'Mall & pusat belanja', 'Tiga mal besar dan pasar modern dapat dicapai dalam 10 menit, lengkap dengan supermarket, bioskop, dan pusat kuliner.', ['en' => ['Malls & shopping', 'Three large malls and a modern market are within 10 minutes, with supermarkets, cinemas and food halls.'], 'ja' => ['モール・ショッピング', '大型モール3か所とモダンマーケットまで10分以内。スーパー、映画館、フードホールがそろっています。']], ['mall', 'belanja', 'shopping', 'supermarket'], '1519567241046-7f570eee3ce6', '3 km · 10 menit'],
            [AgencyKnowledgeItem::CATEGORY_NEIGHBOURHOOD, 'Akses tol', 'Gerbang tol Jakarta–Serpong dan tol JORR 2 berjarak sekitar 3 km, sehingga Jakarta Selatan bisa dicapai sekitar 35–45 menit di luar jam sibuk.', ['en' => ['Toll road access', 'The Jakarta–Serpong and JORR 2 toll gates are about 3 km away, putting South Jakarta roughly 35–45 minutes away outside rush hour.'], 'ja' => ['高速道路アクセス', 'ジャカルタ・スルポン有料道路とJORR2の料金所まで約3km。ラッシュ時以外は南ジャカルタまで約35〜45分です。']], ['tol', 'toll', 'jalan', 'highway'], '1449824913935-59a10b8d2000', '3 km · 6 menit'],
            [AgencyKnowledgeItem::CATEGORY_NEIGHBOURHOOD, 'Stasiun KRL Rawa Buntu', 'Stasiun KRL Rawa Buntu melayani rute ke Tanah Abang dengan waktu tempuh sekitar 40 menit. Tersedia shuttle dari beberapa cluster ke stasiun.', ['en' => ['Rawa Buntu commuter station', 'Rawa Buntu commuter station serves the line to Tanah Abang in about 40 minutes. Shuttles run from several clusters to the station.'], 'ja' => ['ラワ・ブントゥ駅（KRL）', 'ラワ・ブントゥ駅からタナ・アバンまで約40分。いくつかのクラスターから駅へのシャトルがあります。']], ['stasiun', 'krl', 'train', 'station'], '1474487548417-781cb71495f3', '2 km · 6 menit'],
            [AgencyKnowledgeItem::CATEGORY_NEIGHBOURHOOD, 'Taman kota & ruang hijau', 'Kawasan ini memiliki taman kota, danau, dan jalur sepeda sepanjang lebih dari 10 km yang ramai dipakai warga di akhir pekan.', ['en' => ['City parks & green space', 'The area has city parks, a lake and more than 10 km of cycle paths that are busy with residents at weekends.'], 'ja' => ['公園・緑地', '都市公園、湖、10km以上のサイクリングロードがあり、週末は多くの住民でにぎわいます。']], ['taman', 'park', 'hijau', 'green'], '1441974231531-c6227db76b6e', '800 m · 3 menit'],

            // Buying process
            [AgencyKnowledgeItem::CATEGORY_BUYING, 'Alur pembelian rumah', '1) Survei lokasi bersama agen. 2) Bayar booking fee untuk mengunci unit. 3) Bayar DP atau ajukan KPR. 4) Tanda tangan PPJB dengan pengembang. 5) Akad kredit / pelunasan. 6) AJB di notaris dan serah terima kunci.', ['en' => ['How buying works', '1) View the property with an agent. 2) Pay a booking fee to reserve the unit. 3) Pay the down payment or apply for a mortgage. 4) Sign the PPJB with the developer. 5) Mortgage signing or full payment. 6) AJB at the notary and key handover.'], 'ja' => ['購入の流れ', '1) 担当者と内見。2) 予約金で物件を確保。3) 頭金のお支払いまたは住宅ローン申込み。4) デベロッパーとPPJB（売買予約契約）を締結。5) ローン契約または一括払い。6) 公証人のもとでAJB（売買証書）を作成し、鍵の引き渡し。']], ['proses', 'alur', 'beli', 'process', 'booking'], null, null],
            [AgencyKnowledgeItem::CATEGORY_BUYING, 'Booking fee', 'Booking fee sebesar Rp 10–25 juta (tergantung pengembang) mengunci unit selama 14 hari. Booking fee akan dipotong dari DP, dan dikembalikan jika KPR ditolak bank dengan surat penolakan resmi.', ['en' => ['Booking fee', 'A booking fee of IDR 10–25 million (depending on the developer) reserves the unit for 14 days. It is deducted from the down payment and refunded if a bank rejects the mortgage with an official rejection letter.'], 'ja' => ['予約金', '予約金は1,000万〜2,500万ルピア（デベロッパーにより異なります）で、14日間物件を確保します。頭金から差し引かれ、銀行の正式な否認通知でローンが否決された場合は返金されます。']], ['booking fee', 'tanda jadi', 'reservation'], null, null],
            [AgencyKnowledgeItem::CATEGORY_BUYING, 'Survei lokasi', 'Survei lokasi gratis setiap hari pukul 09:00–17:30, didampingi agen. Jadwalkan minimal satu hari sebelumnya agar kunci unit dan agen dapat disiapkan.', ['en' => ['Site visits', 'Free site visits every day between 09:00 and 17:30, accompanied by an agent. Book at least one day ahead so the unit keys and an agent can be arranged.'], 'ja' => ['内見', '内見は毎日9:00〜17:30、担当者同行で無料です。鍵と担当者を手配するため、前日までにご予約ください。']], ['survei', 'viewing', 'kunjungan', 'site visit'], null, null],

            // Financing
            [AgencyKnowledgeItem::CATEGORY_FINANCING, 'KPR dengan bank partner', 'Kami bekerja sama dengan beberapa bank nasional. Bunga fixed promo mulai sekitar 7% per tahun untuk 3–5 tahun pertama, lalu mengikuti bunga floating bank. Tenor hingga 25 tahun.', ['en' => ['Mortgages with partner banks', 'We work with several national banks. Promotional fixed rates start around 7% a year for the first 3–5 years, then follow the bank\'s floating rate. Tenors up to 25 years.'], 'ja' => ['提携銀行の住宅ローン', '複数の国内銀行と提携しています。最初の3〜5年は年7%前後からの固定金利、その後は銀行の変動金利となります。返済期間は最長25年です。']], ['kpr', 'mortgage', 'bank', 'bunga', 'cicilan'], null, null],
            [AgencyKnowledgeItem::CATEGORY_FINANCING, 'Uang muka (DP)', 'DP umumnya 10–20% dari harga rumah. Beberapa pengembang menawarkan DP yang bisa dicicil hingga 12 bulan. Untuk apartemen, bank biasanya meminta DP minimal 20%.', ['en' => ['Down payment', 'Down payments are usually 10–20% of the price. Some developers let you pay it in instalments over up to 12 months. For apartments, banks usually require at least 20%.'], 'ja' => ['頭金', '頭金は通常、価格の10〜20%です。最長12か月の分割払いが可能なデベロッパーもあります。アパートメントの場合、銀行は通常20%以上を求めます。']], ['dp', 'uang muka', 'down payment'], null, null],
            [AgencyKnowledgeItem::CATEGORY_FINANCING, 'Dokumen pengajuan KPR', 'Siapkan KTP, KK, NPWP, surat nikah (jika sudah menikah), slip gaji 3 bulan terakhir, rekening koran 3–6 bulan, dan surat keterangan kerja. Wiraswasta melampirkan SIUP/NIB dan laporan keuangan.', ['en' => ['Mortgage documents', 'Prepare your ID card, family card, tax number, marriage certificate (if married), the last 3 payslips, 3–6 months of bank statements and an employment letter. Self-employed applicants add a business licence and financial statements.'], 'ja' => ['ローン申込書類', 'KTP（身分証）、KK（家族証明）、NPWP（納税者番号）、婚姻証明（既婚の場合）、直近3か月の給与明細、3〜6か月分の銀行取引明細、在職証明をご用意ください。自営業の方は事業許可証と財務諸表も必要です。']], ['dokumen', 'syarat', 'documents', 'requirements'], null, null],

            // Legal
            [AgencyKnowledgeItem::CATEGORY_LEGAL, 'SHM, HGB, dan Strata', 'SHM (Sertifikat Hak Milik) adalah hak kepemilikan terkuat dan hanya untuk WNI. HGB adalah hak membangun dengan jangka waktu dan bisa ditingkatkan ke SHM untuk rumah tinggal. Apartemen menggunakan SHM Sarusun (strata title).', ['en' => ['SHM, HGB and strata titles', 'SHM is the strongest freehold title and is only for Indonesian citizens. HGB is a time-limited right to build that can be upgraded to SHM for homes. Apartments use a strata title (SHM Sarusun).'], 'ja' => ['SHM・HGB・区分所有権', 'SHM（所有権証書）は最も強い権利で、インドネシア国民のみ取得できます。HGBは期限付きの建設権で、住宅の場合はSHMへの変更が可能です。アパートメントは区分所有権（SHM Sarusun）です。']], ['shm', 'hgb', 'sertifikat', 'certificate', 'strata'], null, null],
            [AgencyKnowledgeItem::CATEGORY_LEGAL, 'Pajak dan biaya transaksi', 'Pembeli menanggung BPHTB 5% dari nilai transaksi dikurangi NPOPTKP, biaya notaris/PPAT, dan biaya balik nama. Penjual menanggung PPh final 2,5%. Untuk unit baru dari pengembang, harga dapat sudah termasuk PPN.', ['en' => ['Taxes and transaction costs', 'Buyers pay BPHTB of 5% of the transaction value less the tax-free threshold, plus notary/PPAT and title-transfer fees. Sellers pay a 2.5% final income tax. New units from developers may already include VAT.'], 'ja' => ['税金・取引費用', '買主は取引額から非課税枠を差し引いた額の5%のBPHTB（取得税）、公証人・PPAT費用、名義変更費用を負担します。売主は2.5%の最終所得税を負担します。デベロッパーの新築物件は付加価値税込みの場合があります。']], ['pajak', 'bphtb', 'notaris', 'tax', 'biaya'], null, null],
            [AgencyKnowledgeItem::CATEGORY_LEGAL, 'Pembeli warga negara asing', 'WNA dapat membeli rumah tapak atau apartemen dengan status Hak Pakai, dengan batas harga minimum sesuai ketentuan daerah. Agen kami dapat menghubungkan Anda dengan notaris yang berpengalaman menangani pembeli asing.', ['en' => ['Foreign buyers', 'Foreigners can buy a landed house or apartment under a Right of Use (Hak Pakai) title, subject to a regional minimum price. Our agents can connect you with a notary experienced with foreign buyers.'], 'ja' => ['外国籍の方の購入', '外国籍の方は、地域ごとの最低価格を満たす一戸建てまたはアパートメントを使用権（Hak Pakai）で購入できます。外国人の購入に詳しい公証人をご紹介します。']], ['wna', 'asing', 'foreign', 'expat', 'hak pakai'], null, null],

            // FAQ
            [AgencyKnowledgeItem::CATEGORY_FAQ, 'Apakah harga masih bisa nego?', 'Harga unit baru dari pengembang mengikuti price list resmi, namun sering ada promo bulanan. Untuk properti secondary, harga dapat dinegosiasikan — agen kami akan menyampaikan penawaran Anda ke pemilik.', ['en' => ['Is the price negotiable?', 'New units follow the developer\'s official price list, though monthly promotions are common. Resale prices are negotiable — our agent will pass your offer to the owner.'], 'ja' => ['価格交渉はできますか？', '新築物件はデベロッパーの公式価格表に従いますが、月替わりのキャンペーンがよくあります。中古物件は交渉可能で、担当者がオーナーにご提示をお伝えします。']], ['nego', 'negosiasi', 'diskon', 'negotiate', 'discount'], null, null],
            [AgencyKnowledgeItem::CATEGORY_FAQ, 'Apakah ada biaya jasa agen untuk pembeli?', 'Tidak ada biaya jasa agen untuk pembeli maupun penyewa. Komisi agen dibayarkan oleh pengembang atau pemilik properti.', ['en' => ['Do buyers pay an agent fee?', 'There is no agent fee for buyers or tenants. The commission is paid by the developer or the property owner.'], 'ja' => ['買主に仲介手数料はかかりますか？', '買主・借主に仲介手数料はかかりません。手数料はデベロッパーまたはオーナーが負担します。']], ['komisi', 'fee', 'biaya agen', 'commission'], null, null],
            [AgencyKnowledgeItem::CATEGORY_FAQ, 'Berapa lama minimal sewa apartemen?', 'Sewa apartemen minimal 1 tahun dengan pembayaran di muka, ditambah deposit setara 1–2 bulan sewa yang dikembalikan di akhir masa sewa.', ['en' => ['What is the minimum rental term?', 'Apartment rentals are for at least one year, paid in advance, plus a deposit of one to two months\' rent that is returned at the end of the lease.'], 'ja' => ['賃貸の最短契約期間は？', 'アパートメントの賃貸は最短1年で前払い、さらに1〜2か月分の保証金が必要です（契約終了時に返金）。']], ['sewa', 'rent', 'deposit', 'lease'], null, null],

            // General
            [AgencyKnowledgeItem::CATEGORY_GENERAL, 'Tentang FTS Realty', 'Tim kami terdiri dari 12 agen bersertifikat yang berpengalaman di kawasan BSD dan Serpong lebih dari 10 tahun, serta melayani dalam Bahasa Indonesia, Inggris, dan Jepang.', ['en' => ['About FTS Realty', 'Our team of 12 certified agents has more than 10 years of experience in BSD and Serpong, and serves clients in Indonesian, English and Japanese.'], 'ja' => ['FTS Realtyについて', 'BSDとスルポンで10年以上の経験を持つ12名の有資格エージェントが、インドネシア語・英語・日本語で対応いたします。']], ['tentang', 'about', 'agen', 'team'], null, null],
        ];

        foreach ($items as $sort => [$category, $title, $body, $translations, $tags, $photo, $distance]) {
            $agency->knowledgeItems()->updateOrCreate(
                ['category' => $category, 'title' => $title],
                [
                    'body' => $body,
                    'translations' => collect($translations)->map(fn ($pair) => ['title' => $pair[0], 'body' => $pair[1]])->all(),
                    'tags' => $tags,
                    'image_url' => $photo ? sprintf(self::PHOTO, $photo) : null,
                    'distance' => $distance,
                    'is_active' => true,
                    'sort_order' => $sort,
                ]
            );
        }
    }
}
