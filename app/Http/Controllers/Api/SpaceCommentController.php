<?php

namespace App\Http\Controllers\Api;

use App\Actions\SpaceComment\CreateSpaceCommentAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Exception;

class SpaceCommentController extends Controller
{
    protected $createKeyCommentAction;

    public function __construct(CreateSpaceCommentAction $createKeyCommentAction)
    {
        $this->createKeyCommentAction = $createKeyCommentAction;
    }

    public function store(Request $request, $uuid)
    {
        try {
            // Validate Space Exists by parsing the UUID from the route or checking it inside the action/repository
            // For now, let's validate the request data first

            $validator = Validator::make($request->all(), [
                'comment' => 'required|string',
                'rating' => 'required|integer|min:1|max:5',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Verify space existence (This could be moved to Action or Middleware)
            // But per instructions, handle it here or ensure 404 if not found.
            // Since we receive UUID, we need to ensure it corresponds to a valid space.
            $space = \App\Models\Space::where('uuid', $uuid)->first();

            if (!$space) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Space not found'
                ], 404);
            }

            $data = $request->only(['comment', 'rating']);
            $data['space_id'] = $space->uuid;
            $data['user_id'] = auth()->user()->uuid;

            $comment = $this->createKeyCommentAction->execute($data);

            return response()->json([
                'status' => 'success',
                'data' => $comment,
                'message' => 'Comment created successfully'
            ], 201);
        } catch (Exception $e) {
            \Log::error('Error creating space comment: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'An unexpected error occurred',
                'error' => $e->getMessage() // In production hide this
            ], 400); // Using 400 as a generic client error wrapper instead of 500
        }
    }
}
