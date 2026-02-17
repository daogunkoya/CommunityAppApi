<?php

use App\Models\Comment;
use App\Models\Discussion;
use App\Models\Game;
use App\Models\User;
use Laravel\Passport\Passport;

beforeEach(function () {
        Passport::actingAs(User::factory()->create());
});

it('test home shows a json structure of discussuins and game', function () {

    $user = User::factory()->create();

    $discussions = Discussion::factory()
        ->has(Comment::factory()->count(3))
        ->count(3)->create(['user_id' => $user->id]);

    $game = Game::factory()->count(3)->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->get('api/home')
        ->assertStatus(200)
        ->assertJsonStructure([
            "data" =>[
            'discussions',
            'upcoming_games',
            ]
        ]);

});
