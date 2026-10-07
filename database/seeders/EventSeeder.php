<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\City;
use App\Models\Event;
use App\Models\OrganizerPage;
use App\Models\TicketType;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class EventSeeder extends Seeder
{
    public function run(): void
    {
        $owner = User::first() ?? User::factory()->create([
            'name' => 'MboaEvents Admin',
            'email' => 'admin@mboaevents.cm',
        ]);

        $organizer = OrganizerPage::firstOrCreate(
            ['slug' => 'mboaevents-officiel'],
            [
                'user_id' => $owner->id,
                'name' => 'MboaEvents Officiel',
                'bio' => "La chaîne officielle des événements sélectionnés par l'équipe MboaEvents.",
                'verified_at' => now(),
            ]
        );

        $categories = Category::pluck('id', 'name');
        $cities = City::pluck('id', 'name');

        $demoEvents = [
            ['title' => 'Atelier photographie mobile', 'category' => 'Atelier', 'mode' => 'in_person', 'city' => 'Yaoundé', 'days' => 5, 'price' => 3000],
            ['title' => 'Femmes & leadership 237', 'category' => 'Conférence', 'mode' => 'in_person', 'city' => 'Douala', 'days' => 9, 'price' => 0],
            ['title' => 'Excel avancé pour entrepreneurs', 'category' => 'Webinaire', 'mode' => 'online', 'city' => 'Yaoundé', 'days' => 11, 'price' => 2500],
            ['title' => 'Séminaire agro-business', 'category' => 'Séminaire', 'mode' => 'in_person', 'city' => 'Bafoussam', 'days' => 16, 'price' => 5000],
            ['title' => 'Introduction au marketing digital', 'category' => 'Webinaire', 'mode' => 'online', 'city' => 'Douala', 'days' => 21, 'price' => 0],
            ['title' => 'Nuit acoustique à Kribi', 'category' => 'Conférence', 'mode' => 'in_person', 'city' => 'Kribi', 'days' => 26, 'price' => 7000],
        ];

        foreach ($demoEvents as $demo) {
            $startsAt = now()->addDays($demo['days'])->setTime(9, 0);

            $event = Event::updateOrCreate(
                ['slug' => Str::slug($demo['title'])],
                [
                    'organizer_page_id' => $organizer->id,
                    'category_id' => $categories[$demo['category']] ?? null,
                    'city_id' => $cities[$demo['city']] ?? null,
                    'title' => $demo['title'],
                    'tagline' => 'Un moment à ne pas manquer, organisé par MboaEvents.',
                    'description' => "Trois jours de conférences, d'ateliers et de rencontres pour faire avancer l'écosystème camerounais. Détails complets, programme et intervenants sur cette page.",
                    'highlights' => [
                        'Keynote d\'ouverture par un leader du secteur',
                        'Ateliers pratiques en petits groupes',
                        'Networking avec plus de 200 participants',
                        'Accès aux replays pendant 30 jours',
                    ],
                    'tags' => ['Tech', 'Business', 'Networking'],
                    'faq' => [
                        ['q' => 'Comment récupérer mon billet ?', 'a' => 'Un QR code vous sera envoyé par email 24h avant l\'événement.'],
                        ['q' => 'Y a-t-il un tarif étudiant ?', 'a' => 'Oui, un tarif réduit de 40% est disponible sur justificatif.'],
                        ['q' => 'Puis-je me faire rembourser ?', 'a' => 'Remboursement intégral jusqu\'à 48h avant l\'événement.'],
                    ],
                    'mode' => $demo['mode'],
                    'venue_name' => $demo['mode'] === 'in_person' ? $demo['city'] . ' Convention Center' : null,
                    'address' => $demo['mode'] === 'in_person' ? $demo['city'] . ', Cameroun' : null,
                    'timezone' => 'Africa/Douala',
                    'starts_at' => $startsAt,
                    'ends_at' => (clone $startsAt)->setTime(17, 0),
                    'status' => 'published',
                    'capacity' => 500,
                    'registration_count' => random_int(80, 320),
                    'viewers_count' => random_int(6, 28),
                    'average_rating' => 4.8,
                    'review_count' => 142,
                    'lowest_price' => $demo['price'],
                    'is_free' => $demo['price'] === 0,
                ]
            );

            // Billets
            TicketType::updateOrCreate(
                ['event_id' => $event->id, 'name' => $demo['price'] === 0 ? 'Entrée gratuite' : 'Standard'],
                [
                    'is_free' => $demo['price'] === 0,
                    'price' => $demo['price'],
                    'quantity_total' => 100,
                    'quantity_sold' => random_int(20, 80),
                    'perks' => ['Accès aux conférences', 'Pauses café', 'Badge participant'],
                    'tag' => 'Populaire',
                    'position' => 1,
                    'is_active' => true,
                ]
            );

            // Programme
            $event->programItems()->delete();
            foreach ([1, 2, 3] as $day) {
                foreach (
                    [
                        ['09:00', 'Accueil & networking', 'Équipe MboaEvents', 'Networking'],
                        ['10:00', 'Keynote d\'ouverture', 'Nadia Fotso', 'Keynote'],
                        ['14:00', 'Atelier pratique', 'Éric Mboma', 'Atelier'],
                        ['18:00', 'Cocktail du soir', '—', 'Social'],
                    ] as $i => [$time, $title, $speaker, $tag]
                ) {
                    $event->programItems()->create([
                        'day_number' => $day,
                        'day_label' => "Jour {$day}",
                        'date_label' => $startsAt->copy()->addDays($day - 1)->translatedFormat('d M'),
                        'starts_at' => $time,
                        'title' => $title,
                        'speaker' => $speaker,
                        'tag' => $tag,
                        'position' => $i,
                    ]);
                }
            }

            // Intervenants
            $event->speakers()->delete();
            foreach (
                [
                    ['Nadia Fotso', 'CEO · Wave Africa', 'Keynote'],
                    ['Éric Mboma', 'Head of Growth · Jumia', 'Atelier'],
                    ['Aïcha Bello', 'Design Director · Paystack', 'Conférence'],
                    ['Serge Kengne', 'AI Lead · Orange Digital', 'Conférence'],
                ] as $i => [$name, $role, $tag]
            ) {
                $event->speakers()->create([
                    'name' => $name,
                    'role' => $role,
                    'tag' => $tag,
                    'position' => $i,
                ]);
            }

            // Galerie
            $event->update([
                'gallery' => [
                    'events/gallery/g1.jpg',
                    'events/gallery/g2.jpg',
                    'events/gallery/g3.jpg',
                ],
            ]);
        }
    }
}
