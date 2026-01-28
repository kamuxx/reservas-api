<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Actions\SpaceComment\CreateSpaceCommentAction;
use App\Repositories\Contracts\SpaceCommentRepositoryInterface;
use Mockery;
use App\Models\SpaceComment;

class SpaceCommentUnitTest extends TestCase
{
    /** @test */
    public function action_calls_repository_store()
    {
        $repositoryMock = Mockery::mock(SpaceCommentRepositoryInterface::class);
        $data = [
            'space_id' => 'some-uuid',
            'user_id' => 'user-uuid',
            'comment' => 'Unit test comment',
            'rating' => 5,
        ];

        $repositoryMock->shouldReceive('store')
            ->once()
            ->with($data)
            ->andReturn(new SpaceComment($data));

        $action = new CreateSpaceCommentAction($repositoryMock);
        $result = $action->execute($data);

        $this->assertInstanceOf(SpaceComment::class, $result);
        $this->assertEquals('Unit test comment', $result->comment);
    }
}
