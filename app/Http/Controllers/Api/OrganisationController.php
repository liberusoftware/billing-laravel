<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\OrganisationType;
use App\Http\Controllers\Controller;
use App\Models\BrandDomain;
use App\Models\OrganisationBrand;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class OrganisationController extends Controller
{
    public function children(Request $request): JsonResponse
    {
        return response()->json(
            Team::query()->where('parent_team_id', $this->teamId($request))
                ->withCount('brands')->paginate(50)
        );
    }

    public function storeChild(Request $request): JsonResponse
    {
        $provider = Team::query()->findOrFail($this->teamId($request));
        $data = $request->validate([
            'owner_user_id' => 'required|exists:users,id',
            'name' => 'required|string|max:255',
            'organisation_type' => ['required', Rule::enum(OrganisationType::class)],
            'slug' => 'nullable|string|max:100|alpha_dash|unique:teams,slug',
            'database_mode' => 'sometimes|in:shared,isolated',
            'branding' => 'nullable|array',
        ]);
        $ownerBelongsToProvider = $provider->user_id === (int) $data['owner_user_id']
            || $provider->users()->whereKey($data['owner_user_id'])->exists();
        abort_unless($ownerBelongsToProvider, Response::HTTP_UNPROCESSABLE_ENTITY, 'The owner must belong to the provider organisation.');

        $team = Team::query()->create([
            'parent_team_id' => $this->teamId($request),
            'user_id' => $data['owner_user_id'],
            'name' => $data['name'],
            'organisation_type' => $data['organisation_type'],
            'slug' => $data['slug'] ?? Str::slug($data['name']).'-'.Str::lower(Str::random(6)),
            'database_mode' => $data['database_mode'] ?? 'shared',
            'branding' => $data['branding'] ?? null,
            'personal_team' => false,
        ]);

        return response()->json($team, Response::HTTP_CREATED);
    }

    public function updateChild(Request $request, int $organisation): JsonResponse
    {
        $team = $this->child($request, $organisation);
        $team->update($request->validate([
            'name' => 'sometimes|string|max:255',
            'organisation_type' => ['sometimes', Rule::enum(OrganisationType::class)],
            'slug' => ['sometimes', 'string', 'max:100', 'alpha_dash', Rule::unique('teams', 'slug')->ignore($team->id)],
            'custom_domain' => ['nullable', 'string', 'max:255', Rule::unique('teams', 'custom_domain')->ignore($team->id)],
            'database_mode' => 'sometimes|in:shared,isolated',
            'branding' => 'nullable|array',
        ]));

        return response()->json($team->refresh());
    }

    public function archive(Request $request, int $organisation): JsonResponse
    {
        $team = $this->child($request, $organisation);
        $team->update(['archived_at' => now()]);

        return response()->json($team->refresh());
    }

    public function brands(Request $request, int $organisation): JsonResponse
    {
        return response()->json($this->child($request, $organisation)->brands()->with('domains')->get());
    }

    public function storeBrand(Request $request, int $organisation): JsonResponse
    {
        $team = $this->child($request, $organisation);
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => [
                'required', 'string', 'alpha_dash', 'max:100',
                Rule::unique('organisation_brands', 'slug')->where('team_id', $team->id),
            ],
            'is_primary' => 'sometimes|boolean',
            'theme' => 'nullable|array',
            'email_branding' => 'nullable|array',
        ]);

        return response()->json($team->brands()->create($data), Response::HTTP_CREATED);
    }

    public function storeDomain(Request $request, int $brand): JsonResponse
    {
        $model = OrganisationBrand::query()
            ->whereHas('team', fn ($query) => $query->where('parent_team_id', $this->teamId($request)))
            ->findOrFail($brand);
        $data = $request->validate([
            'domain' => [
                'required', 'string', 'max:255',
                'regex:/^(?=.{1,253}$)(?:[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?\\.)+[a-zA-Z]{2,63}$/',
                'unique:brand_domains,domain',
            ],
            'is_primary' => 'sometimes|boolean',
        ]);

        return response()->json($model->domains()->create($data), Response::HTTP_CREATED);
    }

    public function verifyDomain(Request $request, int $domain): JsonResponse
    {
        $model = BrandDomain::query()
            ->whereHas(
                'brand.team',
                fn ($query) => $query->where('parent_team_id', $this->teamId($request))
            )->findOrFail($domain);
        $model->update(['is_verified' => true, 'verified_at' => now()]);

        return response()->json($model->refresh());
    }

    public function resolveBrand(Request $request): JsonResponse
    {
        $domain = BrandDomain::query()
            ->where('domain', strtolower($request->getHost()))
            ->where('is_verified', true)
            ->with('brand.team')
            ->firstOrFail();

        return response()->json($domain->brand);
    }

    private function child(Request $request, int $id): Team
    {
        return Team::query()->where('parent_team_id', $this->teamId($request))->findOrFail($id);
    }

    private function teamId(Request $request): ?int
    {
        /** @var User|null $user */
        $user = $request->user();

        return $user?->current_team_id;
    }
}
