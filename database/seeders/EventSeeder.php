<?php

namespace Database\Seeders;

use App\Models\Event;
use Illuminate\Database\Seeder;

class EventSeeder extends Seeder
{
    public function run(): void
    {
        Event::query()->delete();

        Event::insert([
            [
                'title' => 'Coldplay: Music of the Spheres World Tour',
                'description' => 'Experience Coldplay live with a full stadium production, immersive visuals, and fan-favorite anthems.',
                'date_time' => '2026-04-18 19:30:00',
                'location' => 'Wembley Stadium, London',
                'capacity' => 90000,
                'price' => 120.00,
                'status' => 'published',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Taylor Swift: The Eras Tour',
                'description' => 'A career-spanning live concert featuring songs from every era, with cinematic staging and storytelling.',
                'date_time' => '2026-05-02 20:00:00',
                'location' => 'MetLife Stadium, New Jersey',
                'capacity' => 82000,
                'price' => 180.00,
                'status' => 'published',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Ed Sheeran +-=÷x Tour',
                'description' => 'An intimate-in-a-stadium performance blending acoustic moments with large-scale singalong hits.',
                'date_time' => '2026-05-16 19:00:00',
                'location' => 'Accor Stadium, Sydney',
                'capacity' => 83000,
                'price' => 95.00,
                'status' => 'published',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Beyoncé: Renaissance Live',
                'description' => 'A high-energy concert experience with elite choreography, iconic fashion, and powerhouse vocals.',
                'date_time' => '2026-06-07 20:30:00',
                'location' => 'SoFi Stadium, Los Angeles',
                'capacity' => 70000,
                'price' => 210.00,
                'status' => 'published',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'The Weeknd: After Hours Til Dawn',
                'description' => 'A dark-pop arena spectacle with cinematic lighting, visual storytelling, and chart-topping tracks.',
                'date_time' => '2026-06-21 20:00:00',
                'location' => 'Stade de France, Paris',
                'capacity' => 81000,
                'price' => 140.00,
                'status' => 'published',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Imagine Dragons: Loom World Tour',
                'description' => 'A modern rock show packed with percussion-heavy performances and stadium-wide audience moments.',
                'date_time' => '2026-07-05 19:30:00',
                'location' => 'Olympiastadion, Berlin',
                'capacity' => 74000,
                'price' => 88.00,
                'status' => 'published',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Bruno Mars Live in Concert',
                'description' => 'A funk-pop show with live band brilliance, dance-heavy performances, and nonstop crowd energy.',
                'date_time' => '2026-07-19 20:00:00',
                'location' => 'Madison Square Garden, New York',
                'capacity' => 20000,
                'price' => 160.00,
                'status' => 'published',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Adele: One Night Only',
                'description' => 'A soulful evening of ballads and classics in a premium arena setting with full live orchestra.',
                'date_time' => '2026-08-02 20:00:00',
                'location' => 'The O2 Arena, London',
                'capacity' => 20000,
                'price' => 220.00,
                'status' => 'published',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
