<?php

namespace Database\Seeders;

use App\Models\Discussion;
use App\Models\Comment;
use App\Models\User;
use App\Models\Game;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();


        Discussion::truncate();
        Comment::truncate();
        Game::truncate();
        User::truncate();


        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'Teka@gmail.com',
        ]);

        Discussion::factory(10)
            ->has(Comment::factory()->count(3))
            ->create(['user_id' => $user->id]);

        Game::factory(10)->create(['user_id' => $user->id]);
    }
}
