<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Tld;
use App\Services\DomainService;
use App\Services\Registrars\Contracts\RegistrarClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DomainSearchController extends Controller
{
    public function __invoke(Request $request, DomainService $domains): JsonResponse
    {
        $domain = $request->validate([
            'domain' => ['required', 'string', 'regex:/^[a-z0-9-]+\.[a-z.]+$/i'],
        ])['domain'];

        $enom = $domains->clientFor('enom');

        $result = $this->lookup($domain, $enom);

        // ponytail: fixed suggestion TLDs; pull from enabled Tlds if the list ever needs to be dynamic.
        [$sld] = explode('.', $domain, 2);
        $suggestions = [];
        foreach (['com', 'net', 'org'] as $tld) {
            $candidate = "{$sld}.{$tld}";
            if ($candidate !== $domain) {
                $suggestions[] = $this->lookup($candidate, $enom);
            }
        }

        return response()->json($result + ['suggestions' => $suggestions]);
    }

    /**
     * @return array{domain: string, available: bool, price: float|null}
     */
    private function lookup(string $domain, RegistrarClient $client): array
    {
        $available = $client->checkAvailability($domain);
        $parts = explode('.', $domain);
        $tld = '.'.end($parts);

        return [
            'domain' => $domain,
            'available' => $available,
            'price' => $available ? $this->priceFor($tld) : null,
        ];
    }

    private function priceFor(string $tld): ?float
    {
        $price = Tld::where('name', $tld)->first()?->calculatePrice();

        return $price === null ? null : round((float) $price, 2);
    }
}
