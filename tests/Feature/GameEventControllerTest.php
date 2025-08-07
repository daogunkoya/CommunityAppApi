<?php

use App\Models\GameEvent;
use App\Models\User;
use App\Models\GameType;

use App\Repositories\GameEventRepository;
use App\Services\GameEventService;


use Laravel\Passport\Passport;

use App\Enums\SkillLevel;

beforeEach(function () {
    app()->bind(GameEventRepository::class, fn () => new GameEventRepository());

    app()->bind(GameEventService::class, fn () => new GameEventService(app(GameEventRepository::class)));

     Passport::actingAs(User::factory()->create());
});

test('tests Game Event Controller', function () {

    // $token = User::factory()->create()->createToken('test-token')->plainTextToken;

    $userGameInterests = GameType::factory()->count(3)->create();

    $users = User::factory()->count(3)->create();

    $users->each(function ($user) use ($userGameInterests) {
        $user->gameInterests()->attach($userGameInterests->pluck('id')->toArray());
    });
    //$user->gameInterests()->attach($userGameInterests->pluck('id')->toArray());

    $gameEvents = GameEvent::factory()->count(3)->create();

    $gameEvents->each(function ($gameEvent) use ($users) {
        $gameEvent->participants()->attach($users->pluck('id')->toArray());
    });


    $response = $this->get('/api/events');
       $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'data' => [
                '*' => [
                    'id',
                    'title',
                    'sport',
                    'location',
                    'starts_at',
                    'organiser',
                ]
            ]
        ])
        ;

        // Verify we got some data back
        expect($response['data'])->toHaveCount(3);

});
