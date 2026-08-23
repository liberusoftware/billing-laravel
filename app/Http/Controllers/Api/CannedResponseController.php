<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CannedResponse;
use App\Services\CannedResponseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CannedResponseController extends Controller
{
    public function __construct(
        protected CannedResponseService $cannedResponseService
    ) {}

    /**
     * Get all canned responses
     */
    public function index(Request $request): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        $category = $request->get('category');

        $responses = $this->cannedResponseService->getAll(
            $teamId,
            $category
        );

        return response()->json(
            [
                'data' => $responses,
            ]
        );
    }

    /**
     * Get canned response by shortcode
     */
    public function show(string $shortcode, Request $request): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        $response = $this->cannedResponseService->getByShortcode(
            $shortcode,
            $teamId
        );

        if (! $response instanceof CannedResponse) {
            return response()->json(
                [
                    'message' => 'Canned response not found',
                ],
                404
            );
        }

        return response()->json(
            [
                'data' => $response,
            ]
        );
    }

    /**
     * Use a canned response
     */
    public function use(string $shortcode, Request $request): JsonResponse
    {
        $request->validate(
            [
                'variables' => 'nullable|array',
            ]
        );

        $teamId = $request->user()?->current_team_id;
        $response = $this->cannedResponseService->getByShortcode(
            $shortcode,
            $teamId
        );

        if (! $response instanceof CannedResponse) {
            return response()->json(
                [
                    'message' => 'Canned response not found',
                ],
                404
            );
        }

        $content = $this->cannedResponseService->use(
            $response,
            $request->get(
                'variables',
                []
            )
        );

        return response()->json(
            [
                'content' => $content,
            ]
        );
    }

    /**
     * Search canned responses
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate(
            [
                'q' => 'required|string|min:2',
            ]
        );

        $teamId = $request->user()?->current_team_id;
        $responses = $this->cannedResponseService->search(
            $request->q,
            $teamId
        );

        return response()->json(
            [
                'data' => $responses,
            ]
        );
    }

    /**
     * Get categories
     */
    public function categories(Request $request): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        $categories = $this->cannedResponseService->getCategories($teamId);

        return response()->json(
            [
                'data' => $categories,
            ]
        );
    }

    /**
     * Get most used responses
     */
    public function mostUsed(Request $request): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        $limit = $request->get(
            'limit',
            10
        );

        $responses = $this->cannedResponseService->getMostUsed(
            $limit,
            $teamId
        );

        return response()->json(
            [
                'data' => $responses,
            ]
        );
    }

    /**
     * Get available variables
     */
    public function variables(): JsonResponse
    {
        return response()->json(
            [
                'data' => CannedResponseService::getAvailableVariables(),
            ]
        );
    }
}
