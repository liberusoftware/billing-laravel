<?php

namespace App\Services;

use App\Models\CannedResponse;
use Illuminate\Database\Eloquent\Collection;

class CannedResponseService
{
    /**
     * Get all canned responses
     */
    public function getAll(?int $teamId = null, ?string $category = null): Collection
    {
        $query = CannedResponse::where(
            'is_active',
            true
        );

        if ($teamId) {
            $query->where(
                function ($q) use ($teamId): void {
                    $q->where(
                        'team_id',
                        $teamId
                    )
                        ->orWhereNull('team_id'); // Include global responses
                }
            );
        }

        if ($category) {
            $query->where(
                'category',
                $category
            );
        }

        return $query->orderBy('title')->get();
    }

    /**
     * Get by shortcode
     */
    public function getByShortcode(string $shortcode, ?int $teamId = null): ?CannedResponse
    {
        $query = CannedResponse::where(
            'shortcode',
            $shortcode
        )
            ->where(
                'is_active',
                true
            );

        if ($teamId) {
            $query->where(
                function ($q) use ($teamId): void {
                    $q->where(
                        'team_id',
                        $teamId
                    )
                        ->orWhereNull('team_id');
                }
            );
        }

        return $query->first();
    }

    /**
     * Use a canned response and replace variables
     */
    public function use(CannedResponse $response, array $variables = []): string
    {
        $response->use();

        return $response->replaceVariables($variables);
    }

    /**
     * Search canned responses
     */
    public function search(string $query, ?int $teamId = null): Collection
    {
        $queryBuilder = CannedResponse::where(
            'is_active',
            true
        )
            ->where(
                function ($q) use ($query): void {
                    $q->where(
                        'title',
                        'like',
                        "%{$query}%"
                    )
                        ->orWhere(
                            'content',
                            'like',
                            "%{$query}%"
                        )
                        ->orWhere(
                            'shortcode',
                            'like',
                            "%{$query}%"
                        );
                }
            );

        if ($teamId) {
            $queryBuilder->where(
                function ($q) use ($teamId): void {
                    $q->where(
                        'team_id',
                        $teamId
                    )
                        ->orWhereNull('team_id');
                }
            );
        }

        return $queryBuilder->orderBy(
            'usage_count',
            'desc'
        )->get();
    }

    /**
     * Get categories
     */
    public function getCategories(?int $teamId = null): array
    {
        $query = CannedResponse::query();

        if ($teamId) {
            $query->where(
                function ($q) use ($teamId): void {
                    $q->where(
                        'team_id',
                        $teamId
                    )
                        ->orWhereNull('team_id');
                }
            );
        }

        return $query->distinct('category')
            ->whereNotNull('category')
            ->pluck('category')
            ->toArray();
    }

    /**
     * Get most used responses
     */
    public function getMostUsed(int $limit = 10, ?int $teamId = null): Collection
    {
        $query = CannedResponse::where(
            'is_active',
            true
        )
            ->orderByDesc('usage_count');

        if ($teamId) {
            $query->where(
                function ($q) use ($teamId): void {
                    $q->where(
                        'team_id',
                        $teamId
                    )
                        ->orWhereNull('team_id');
                }
            );
        }

        return $query->limit($limit)->get();
    }

    /**
     * Get available variables for templates
     */
    public static function getAvailableVariables(): array
    {
        return [
            'client_name' => 'Client Name',
            'client_email' => 'Client Email',
            'ticket_id' => 'Ticket ID',
            'ticket_subject' => 'Ticket Subject',
            'invoice_number' => 'Invoice Number',
            'invoice_amount' => 'Invoice Amount',
            'due_date' => 'Due Date',
            'company_name' => 'Company Name',
            'support_email' => 'Support Email',
            'support_phone' => 'Support Phone',
        ];
    }
}
