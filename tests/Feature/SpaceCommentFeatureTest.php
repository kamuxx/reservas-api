<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Space;
use App\Models\User;
use App\Models\SpaceComment;

class SpaceCommentFeatureTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        // Seed statuses and basic data if necessary, or rely on Factories if they handle it.
        // Assuming factories are self-contained or we need to run seeder partials.
        // For 'spaces', we might need status table seeded.
        $this->seed(\Database\Seeders\StatusTableSeeder::class);
        $this->seed(\Database\Seeders\SpaceTypeTableSeeder::class);
    }

    /** @test */
    public function it_can_create_a_comment_for_a_space()
    {
        $user = User::factory()->create();
        $space = Space::factory()->create();

        $data = [
            'comment' => 'This is a great space!',
            'rating' => 5,
        ];

        $response = $this->actingAs($user, 'api')
            ->postJson(route('spaces.comments.store', $space->uuid), $data);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
                'message' => 'Comment created successfully',
            ]);

        $this->assertDatabaseHas('space_comments', [
            'space_id' => $space->uuid,
            'user_id' => $user->uuid,
            'comment' => 'This is a great space!',
            'rating' => 5,
        ]);
    }

    /** @test */
    public function it_validates_input_data()
    {
        $user = User::factory()->create();
        $space = Space::factory()->create();

        $response = $this->actingAs($user, 'api')
            ->postJson(route('spaces.comments.store', $space->uuid), []);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'status',
                'message',
                'errors' => ['comment', 'rating']
            ]);
    }

    /** @test */
    public function it_rejects_invalid_rating()
    {
        $user = User::factory()->create();
        $space = Space::factory()->create();

        $data = [
            'comment' => 'Bad rating value',
            'rating' => 6, // Invalid
        ];

        $response = $this->actingAs($user, 'api')
            ->postJson(route('spaces.comments.store', $space->uuid), $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['rating']);
    }

    /** @test */
    public function it_returns_404_if_space_not_found()
    {
        $user = User::factory()->create();
        $fakeUuid = \Illuminate\Support\Str::uuid();

        $data = [
            'comment' => 'Ghost space',
            'rating' => 4,
        ];

        $response = $this->actingAs($user, 'api')
            ->postJson(route('spaces.comments.store', $fakeUuid), $data);

        $response->assertStatus(404)
            ->assertJson([
                'status' => 'error',
                'message' => 'Space not found'
            ]);
    }
}
