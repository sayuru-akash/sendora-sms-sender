<?php

namespace Tests\Feature;

use App\Models\SavedSegment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SavedSegmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_segments_can_be_searched_case_insensitively(): void
    {
        $user = User::factory()->staff()->create();
        SavedSegment::factory()->create([
            'name' => 'Payment Followups',
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->get('/segments?search=FOLLOWUPS');

        $response->assertOk();
    }
}
