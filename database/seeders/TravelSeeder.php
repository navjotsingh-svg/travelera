<?php

namespace Database\Seeders;

use App\Models\Cab;
use App\Models\Destination;
use App\Models\Flight;
use App\Models\Hotel;
use App\Models\TravelPackage;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class TravelSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'traveler@travelera.test'],
            [
                'name' => 'Aarav Traveler',
                'phone' => '9876543210',
                'password' => 'password',
                'email_verified_at' => now(),
            ]
        );

        $destinations = [
            [
                'name' => 'Goa Beaches',
                'city' => 'Goa',
                'country' => 'India',
                'slug' => 'goa',
                'description' => 'Sun, sand, seafood and a slower pace. Goa is the easy weekend escape with beaches, forts and nightlife.',
                'image' => 'https://images.unsplash.com/photo-1512343879784-a960bf40e7f2?auto=format&fit=crop&w=1400&q=80',
                'is_featured' => true,
            ],
            [
                'name' => 'Pink City Jaipur',
                'city' => 'Jaipur',
                'country' => 'India',
                'slug' => 'jaipur',
                'description' => 'Palaces, bazaars and royal architecture. Jaipur is the heart of Rajasthan’s colour and craft.',
                'image' => 'https://images.unsplash.com/photo-1477587458883-47145f105cce?auto=format&fit=crop&w=1400&q=80',
                'is_featured' => true,
            ],
            [
                'name' => 'Garden City Bengaluru',
                'city' => 'Bengaluru',
                'country' => 'India',
                'slug' => 'bengaluru',
                'description' => 'Parks, tech campuses, cafés and weekend getaways. Bengaluru is the easy south-India hub.',
                'image' => 'https://images.unsplash.com/photo-1596176530529-78163a4f7af2?auto=format&fit=crop&w=1400&q=80',
                'is_featured' => false,
            ],
            [
                'name' => 'Mumbai Lights',
                'city' => 'Mumbai',
                'country' => 'India',
                'slug' => 'mumbai',
                'description' => 'The city that never sleeps — sea views, street food, cinema and business towers in one frame.',
                'image' => 'https://images.unsplash.com/photo-1566552881560-0be862a7c445?auto=format&fit=crop&w=1400&q=80',
                'is_featured' => true,
            ],
            [
                'name' => 'Delhi Capital',
                'city' => 'Delhi',
                'country' => 'India',
                'slug' => 'delhi',
                'description' => 'Monuments, markets and Mughal gardens. Delhi is history and modern India side by side.',
                'image' => 'https://images.unsplash.com/photo-1587474260584-136574528ed5?auto=format&fit=crop&w=1400&q=80',
                'is_featured' => true,
            ],
            [
                'name' => 'Dubai Skyline',
                'city' => 'Dubai',
                'country' => 'UAE',
                'slug' => 'dubai',
                'description' => 'Desert luxury, malls, beaches and iconic towers. Dubai is a stopover that becomes the trip.',
                'image' => 'https://images.unsplash.com/photo-1512453979798-5ea266f8880c?auto=format&fit=crop&w=1400&q=80',
                'is_featured' => true,
            ],
            [
                'name' => 'Paris Romance',
                'city' => 'Paris',
                'country' => 'France',
                'slug' => 'paris',
                'description' => 'Cafés, museums and the Seine. Paris still sets the bar for a city break.',
                'image' => 'https://images.unsplash.com/photo-1502602898657-3e91760cbb34?auto=format&fit=crop&w=1400&q=80',
                'is_featured' => true,
            ],
            [
                'name' => 'Bali Escape',
                'city' => 'Bali',
                'country' => 'Indonesia',
                'slug' => 'bali',
                'description' => 'Rice terraces, temples, surf and villas. Bali is the classic tropical reset.',
                'image' => 'https://images.unsplash.com/photo-1537996194471-e657df975ab4?auto=format&fit=crop&w=1400&q=80',
                'is_featured' => true,
            ],
            [
                'name' => 'Singapore City',
                'city' => 'Singapore',
                'country' => 'Singapore',
                'slug' => 'singapore',
                'description' => 'Gardens, hawker food and a skyline over the bay. Compact, clean and easy to explore.',
                'image' => 'https://images.unsplash.com/photo-1525625293386-3f8f99389edd?auto=format&fit=crop&w=1400&q=80',
                'is_featured' => true,
            ],
            [
                'name' => 'London Calling',
                'city' => 'London',
                'country' => 'United Kingdom',
                'slug' => 'london',
                'description' => 'Royal parks, theatre, museums and neighbourhoods with their own pulse.',
                'image' => 'https://images.unsplash.com/photo-1486299267070-83823f5448dd?auto=format&fit=crop&w=1400&q=80',
                'is_featured' => true,
            ],
            [
                'name' => 'Tokyo Pulse',
                'city' => 'Tokyo',
                'country' => 'Japan',
                'slug' => 'tokyo',
                'description' => 'Neon nights, quiet temples, world-class food and trains that run on a pin.',
                'image' => 'https://images.unsplash.com/photo-1540959733332-eab4deabeeaf?auto=format&fit=crop&w=1400&q=80',
                'is_featured' => false,
            ],
        ];

        foreach ($destinations as $destination) {
            Destination::query()->updateOrCreate(['slug' => $destination['slug']], $destination);
        }

        $iataCodes = [
            'Goa' => 'GOI',
            'Jaipur' => 'JAI',
            'Mumbai' => 'BOM',
            'Delhi' => 'DEL',
            'Bengaluru' => 'BLR',
            'Dubai' => 'DXB',
            'Paris' => 'PAR',
            'Bali' => 'DPS',
            'Singapore' => 'SIN',
            'London' => 'LON',
            'Tokyo' => 'TYO',
        ];

        foreach ($iataCodes as $city => $code) {
            Destination::query()->where('city', $city)->update(['iata_code' => $code]);
        }

        $hotels = [
            [
                'name' => 'Sea Pearl Resort',
                'slug' => 'sea-pearl-resort-goa',
                'city' => 'Goa',
                'country' => 'India',
                'address' => 'Calangute Beach Road, Goa',
                'description' => 'Beach-facing rooms, a lagoon pool and seafood dinners a short walk from Calangute.',
                'star_rating' => 5,
                'guest_rating' => 9.1,
                'price_per_night' => 12400,
                'rooms_available' => 12,
                'amenities' => ['Free WiFi', 'Infinity pool', 'Breakfast', 'Spa', 'Beach access'],
                'image' => 'https://images.unsplash.com/photo-1566073771259-6a8506099945?auto=format&fit=crop&w=1400&q=80',
            ],
            [
                'name' => 'Rajmahal Palace Hotel',
                'slug' => 'rajmahal-palace-jaipur',
                'city' => 'Jaipur',
                'country' => 'India',
                'address' => 'Civil Lines, Jaipur',
                'description' => 'Heritage rooms, courtyard dining and a rooftop with views of the old city.',
                'star_rating' => 5,
                'guest_rating' => 9.4,
                'price_per_night' => 15800,
                'rooms_available' => 8,
                'amenities' => ['Free WiFi', 'Heritage stay', 'Breakfast', 'Spa', 'Airport pickup'],
                'image' => 'https://images.unsplash.com/photo-1542314831-068cd1dbfeeb?auto=format&fit=crop&w=1400&q=80',
            ],
            [
                'name' => 'Marine Drive Suites',
                'slug' => 'marine-drive-suites-mumbai',
                'city' => 'Mumbai',
                'country' => 'India',
                'address' => 'Netaji Subhash Chandra Bose Road, Mumbai',
                'description' => 'Sea-facing suites on Marine Drive with a sky lounge and fast airport transfers.',
                'star_rating' => 4,
                'guest_rating' => 8.7,
                'price_per_night' => 9800,
                'rooms_available' => 16,
                'amenities' => ['Free WiFi', 'Sea view', 'Gym', 'Restaurant', 'Airport pickup'],
                'image' => 'https://images.unsplash.com/photo-1551882547-ff40c63fe5fa?auto=format&fit=crop&w=1400&q=80',
            ],
            [
                'name' => 'Lotus Connaught Hotel',
                'slug' => 'lotus-connaught-delhi',
                'city' => 'Delhi',
                'country' => 'India',
                'address' => 'Connaught Place, New Delhi',
                'description' => 'A central Delhi stay with metro access, a rooftop bar and business-ready rooms.',
                'star_rating' => 4,
                'guest_rating' => 8.4,
                'price_per_night' => 7200,
                'rooms_available' => 20,
                'amenities' => ['Free WiFi', 'Breakfast', 'Gym', 'Bar', 'Meeting rooms'],
                'image' => 'https://images.unsplash.com/photo-1564501049412-61c2a3083791?auto=format&fit=crop&w=1400&q=80',
            ],
            [
                'name' => 'Burj Vista Hotel',
                'slug' => 'burj-vista-dubai',
                'city' => 'Dubai',
                'country' => 'UAE',
                'address' => 'Downtown Dubai',
                'description' => 'Skyline rooms facing the Burj Khalifa, with a spa, pool deck and mall access.',
                'star_rating' => 5,
                'guest_rating' => 9.2,
                'price_per_night' => 18600,
                'rooms_available' => 10,
                'amenities' => ['Free WiFi', 'Pool', 'Spa', 'Breakfast', 'City view'],
                'image' => 'https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?auto=format&fit=crop&w=1400&q=80',
            ],
            [
                'name' => 'Seine Garden Inn',
                'slug' => 'seine-garden-inn-paris',
                'city' => 'Paris',
                'country' => 'France',
                'address' => 'Saint-Germain-des-Prés, Paris',
                'description' => 'A boutique Left Bank hotel with courtyard breakfasts and walkable museum days.',
                'star_rating' => 4,
                'guest_rating' => 8.9,
                'price_per_night' => 16400,
                'rooms_available' => 9,
                'amenities' => ['Free WiFi', 'Breakfast', 'Concierge', 'Courtyard', 'Metro nearby'],
                'image' => 'https://images.unsplash.com/photo-1520250497591-112f2f40a3f4?auto=format&fit=crop&w=1400&q=80',
            ],
            [
                'name' => 'Ubud Canopy Villas',
                'slug' => 'ubud-canopy-villas-bali',
                'city' => 'Bali',
                'country' => 'Indonesia',
                'address' => 'Ubud, Gianyar, Bali',
                'description' => 'Private pool villas in the jungle canopy, with yoga decks and farm-to-table dining.',
                'star_rating' => 5,
                'guest_rating' => 9.5,
                'price_per_night' => 14200,
                'rooms_available' => 6,
                'amenities' => ['Private pool', 'Breakfast', 'Yoga', 'Spa', 'Airport transfer'],
                'image' => 'https://images.unsplash.com/photo-1571003123894-1f0594d2b5d9?auto=format&fit=crop&w=1400&q=80',
            ],
            [
                'name' => 'Marina Bay Stay',
                'slug' => 'marina-bay-stay-singapore',
                'city' => 'Singapore',
                'country' => 'Singapore',
                'address' => 'Bayfront Avenue, Singapore',
                'description' => 'A modern waterfront hotel next to Gardens by the Bay and the city skyline.',
                'star_rating' => 5,
                'guest_rating' => 9.0,
                'price_per_night' => 17600,
                'rooms_available' => 14,
                'amenities' => ['Free WiFi', 'Pool', 'Breakfast', 'Gym', 'Bay view'],
                'image' => 'https://images.unsplash.com/photo-1568084680786-a84f91d1153c?auto=format&fit=crop&w=1400&q=80',
            ],
            [
                'name' => 'Thames House Hotel',
                'slug' => 'thames-house-london',
                'city' => 'London',
                'country' => 'United Kingdom',
                'address' => 'South Bank, London',
                'description' => 'River views, a quiet library lounge and easy walks to the London Eye and theatres.',
                'star_rating' => 4,
                'guest_rating' => 8.6,
                'price_per_night' => 15100,
                'rooms_available' => 11,
                'amenities' => ['Free WiFi', 'Breakfast', 'Bar', 'River view', 'Theatre concierge'],
                'image' => 'https://images.unsplash.com/photo-1445019980597-93fa8acb246c?auto=format&fit=crop&w=1400&q=80',
            ],
        ];

        foreach ($hotels as $hotel) {
            Hotel::query()->updateOrCreate(['slug' => $hotel['slug']], $hotel);
        }

        $cabs = [
            ['name' => 'City Mini', 'vehicle_type' => 'Hatchback', 'capacity' => 3, 'city' => 'Delhi', 'price_per_km' => 12, 'base_fare' => 99, 'image' => 'https://images.unsplash.com/photo-1549317661-bd32c8ce0db2?auto=format&fit=crop&w=1200&q=80', 'description' => 'Compact AC hatchback for short city hops and metro-station pickups.', 'is_available' => true],
            ['name' => 'Prime Sedan', 'vehicle_type' => 'Sedan', 'capacity' => 4, 'city' => 'Mumbai', 'price_per_km' => 16, 'base_fare' => 149, 'image' => 'https://images.unsplash.com/photo-1550355291-bbee04a92027?auto=format&fit=crop&w=1200&q=80', 'description' => 'Airport-ready sedan with extra boot space and a professional chauffeur.', 'is_available' => true],
            ['name' => 'Family SUV', 'vehicle_type' => 'SUV', 'capacity' => 6, 'city' => 'Goa', 'price_per_km' => 22, 'base_fare' => 249, 'image' => 'https://images.unsplash.com/photo-1519641471654-76ce0107ad1b?auto=format&fit=crop&w=1200&q=80', 'description' => 'Spacious SUV for beach hops, North–South Goa days and group trips.', 'is_available' => true],
            ['name' => 'Royal Innova', 'vehicle_type' => 'MPV', 'capacity' => 7, 'city' => 'Jaipur', 'price_per_km' => 20, 'base_fare' => 199, 'image' => 'https://images.unsplash.com/photo-1489824904134-891ab64532f1?auto=format&fit=crop&w=1200&q=80', 'description' => 'Comfortable MPV for fort circuits, Amber and city sightseeing.', 'is_available' => true],
            ['name' => 'Airport Premier', 'vehicle_type' => 'Sedan', 'capacity' => 3, 'city' => 'Delhi', 'price_per_km' => 18, 'base_fare' => 299, 'image' => 'https://images.unsplash.com/photo-1503376780353-7e6692767b70?auto=format&fit=crop&w=1200&q=80', 'description' => 'Meet-and-greet airport cab with flight tracking and bottled water.', 'is_available' => true],
            ['name' => 'Dubai Executive', 'vehicle_type' => 'Luxury Sedan', 'capacity' => 3, 'city' => 'Dubai', 'price_per_km' => 45, 'base_fare' => 499, 'image' => 'https://images.unsplash.com/photo-1525609004556-c46c7d6cf023?auto=format&fit=crop&w=1200&q=80', 'description' => 'Chauffeured luxury sedan for hotel transfers and downtown meetings.', 'is_available' => true],
            ['name' => 'Bali Driver Day', 'vehicle_type' => 'SUV', 'capacity' => 4, 'city' => 'Bali', 'price_per_km' => 28, 'base_fare' => 899, 'image' => 'https://images.unsplash.com/photo-1469854523086-cc02fe5d8800?auto=format&fit=crop&w=1200&q=80', 'description' => 'Full-day SUV with a local driver for temples, terraces and beach clubs.', 'is_available' => true],
            ['name' => 'Singapore Comfort', 'vehicle_type' => 'Sedan', 'capacity' => 4, 'city' => 'Singapore', 'price_per_km' => 32, 'base_fare' => 259, 'image' => 'https://images.unsplash.com/photo-1449965408869-eaa3f722e40d?auto=format&fit=crop&w=1200&q=80', 'description' => 'Clean sedan for Changi arrivals, hotel hops and late-night food runs.', 'is_available' => true],
        ];

        foreach ($cabs as $cab) {
            Cab::query()->updateOrCreate(
                ['name' => $cab['name'], 'city' => $cab['city']],
                $cab
            );
        }

        $packages = [
            [
                'destination' => 'goa',
                'title' => 'Goa Beach Weekender',
                'slug' => 'goa-beach-weekender',
                'duration_days' => 3,
                'price' => 18999,
                'description' => 'Flights, a 4-star beach hotel and a sunset cruise. Built for a Friday-to-Sunday reset.',
                'includes' => ['Return flights', '2 nights hotel', 'Breakfast', 'Airport transfers', 'Sunset cruise'],
                'image' => 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?auto=format&fit=crop&w=1400&q=80',
                'is_featured' => true,
            ],
            [
                'destination' => 'jaipur',
                'title' => 'Royal Rajasthan Trail',
                'slug' => 'royal-rajasthan-trail',
                'duration_days' => 5,
                'price' => 32999,
                'description' => 'Jaipur palaces, a heritage stay and a guided Amber Fort morning with a private cab.',
                'includes' => ['4 nights heritage hotel', 'Daily breakfast', 'Private cab', 'Amber Fort guide', 'Welcome dinner'],
                'image' => 'https://images.unsplash.com/photo-1524492412937-b28074a5d7c5?auto=format&fit=crop&w=1400&q=80',
                'is_featured' => true,
            ],
            [
                'destination' => 'dubai',
                'title' => 'Dubai City Lights',
                'slug' => 'dubai-city-lights',
                'duration_days' => 4,
                'price' => 54999,
                'description' => 'Downtown hotel, desert safari and a Burj Khalifa slot — the classic first Dubai trip.',
                'includes' => ['Return flights', '3 nights hotel', 'Desert safari', 'Burj Khalifa tickets', 'Airport transfers'],
                'image' => 'https://images.unsplash.com/photo-1489516408517-0c0a15662682?auto=format&fit=crop&w=1400&q=80',
                'is_featured' => true,
            ],
            [
                'destination' => 'bali',
                'title' => 'Bali Villa Escape',
                'slug' => 'bali-villa-escape',
                'duration_days' => 6,
                'price' => 67999,
                'description' => 'Ubud villa nights, a private driver and a Uluwatu sunset dinner.',
                'includes' => ['5 nights villa', 'Daily breakfast', 'Private driver', 'Uluwatu dinner', 'Airport transfers'],
                'image' => 'https://images.unsplash.com/photo-1518548419970-58e3b4079ab2?auto=format&fit=crop&w=1400&q=80',
                'is_featured' => true,
            ],
            [
                'destination' => 'paris',
                'title' => 'Paris Weekend Classic',
                'slug' => 'paris-weekend-classic',
                'duration_days' => 4,
                'price' => 79999,
                'description' => 'Left Bank hotel, Seine cruise and museum skip-the-line passes.',
                'includes' => ['Return flights', '3 nights hotel', 'Seine cruise', 'Museum passes', 'Metro cards'],
                'image' => 'https://images.unsplash.com/photo-1431274172761-fca922e65480?auto=format&fit=crop&w=1400&q=80',
                'is_featured' => false,
            ],
            [
                'destination' => 'singapore',
                'title' => 'Singapore Family Fun',
                'slug' => 'singapore-family-fun',
                'duration_days' => 5,
                'price' => 62999,
                'description' => 'Marina Bay stay, Gardens by the Bay and a Universal Studios day.',
                'includes' => ['Return flights', '4 nights hotel', 'Gardens by the Bay', 'Universal Studios', 'Airport transfers'],
                'image' => 'https://images.unsplash.com/photo-1496939376851-89342e90adcd?auto=format&fit=crop&w=1400&q=80',
                'is_featured' => false,
            ],
        ];

        foreach ($packages as $package) {
            $destination = Destination::query()->where('slug', $package['destination'])->first();
            unset($package['destination']);
            $package['destination_id'] = $destination->id;
            TravelPackage::query()->updateOrCreate(['slug' => $package['slug']], $package);
        }

        Flight::query()->delete();

        $routes = [
            ['IndiGo', '6E 214', 'Delhi', 'DEL', 'Mumbai', 'BOM', 135, 4899, 'economy', '06:15'],
            ['Air India', 'AI 887', 'Mumbai', 'BOM', 'Delhi', 'DEL', 140, 5299, 'economy', '08:40'],
            ['Vistara', 'UK 955', 'Delhi', 'DEL', 'Goa', 'GOI', 160, 6199, 'economy', '09:20'],
            ['IndiGo', '6E 531', 'Bengaluru', 'BLR', 'Goa', 'GOI', 75, 3599, 'economy', '11:05'],
            ['Air India', 'AI 441', 'Delhi', 'DEL', 'Dubai', 'DXB', 230, 15499, 'economy', '10:30'],
            ['Emirates', 'EK 513', 'Mumbai', 'BOM', 'Dubai', 'DXB', 195, 18799, 'business', '13:15'],
            ['Singapore Airlines', 'SQ 403', 'Delhi', 'DEL', 'Singapore', 'SIN', 330, 24899, 'economy', '22:10'],
            ['Air France', 'AF 225', 'Delhi', 'DEL', 'Paris', 'CDG', 520, 41200, 'economy', '01:40'],
            ['British Airways', 'BA 142', 'Delhi', 'DEL', 'London', 'LHR', 545, 39800, 'economy', '02:55'],
            ['IndiGo', '6E 202', 'Jaipur', 'JAI', 'Mumbai', 'BOM', 115, 4299, 'economy', '07:45'],
            ['Vistara', 'UK 812', 'Mumbai', 'BOM', 'Goa', 'GOI', 75, 3899, 'economy', '16:20'],
            ['Air India', 'AI 312', 'Delhi', 'DEL', 'Bengaluru', 'BLR', 165, 5599, 'economy', '18:35'],
        ];

        $airlineImages = [
            'IndiGo' => 'https://images.unsplash.com/photo-1436491865332-7a61a109cc05?auto=format&fit=crop&w=1200&q=80',
            'Air India' => 'https://images.unsplash.com/photo-1529074963764-98f45c47344b?auto=format&fit=crop&w=1200&q=80',
            'Vistara' => 'https://images.unsplash.com/photo-1569154941061-e231b4725ef1?auto=format&fit=crop&w=1200&q=80',
            'Emirates' => 'https://images.unsplash.com/photo-1542296332-2e4473faf563?auto=format&fit=crop&w=1200&q=80',
            'Singapore Airlines' => 'https://images.unsplash.com/photo-1570710891163-6d3b5c47248b?auto=format&fit=crop&w=1200&q=80',
            'Air France' => 'https://images.unsplash.com/photo-1464037866556-6812c9d1c72e?auto=format&fit=crop&w=1200&q=80',
            'British Airways' => 'https://images.unsplash.com/photo-1474302774965-041b4092063a?auto=format&fit=crop&w=1200&q=80',
        ];

        foreach (range(0, 9) as $dayOffset) {
            foreach ($routes as $index => $route) {
                [$airline, $number, $origin, $originCode, $destination, $destinationCode, $minutes, $price, $cabin, $time] = $route;
                $departure = Carbon::parse(now()->addDays($dayOffset)->toDateString().' '.$time);

                Flight::query()->create([
                    'airline' => $airline,
                    'flight_number' => $number,
                    'origin' => $origin,
                    'origin_code' => $originCode,
                    'destination' => $destination,
                    'destination_code' => $destinationCode,
                    'departure_at' => $departure,
                    'arrival_at' => (clone $departure)->addMinutes($minutes),
                    'duration_minutes' => $minutes,
                    'cabin_class' => $cabin,
                    'price' => $price + ($dayOffset * 120) + ($index * 15),
                    'seats_available' => 12 + (($index + $dayOffset) % 18),
                    'image' => $airlineImages[$airline],
                ]);
            }
        }
    }
}
