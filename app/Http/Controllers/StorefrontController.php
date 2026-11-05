<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Filament\Client\Pages\OrderForm;
use App\Models\OrderFormTemplate;
use App\Models\SubscriptionPlan;
use Illuminate\Contracts\View\View;

class StorefrontController extends Controller
{
    public function __invoke(): View
    {
        $templates = OrderFormTemplate::query()->where('is_active', true)->orderBy('name')->get();
        $plans = SubscriptionPlan::query()
            ->where('is_active', true)
            ->whereIn('id', $templates->flatMap(fn (OrderFormTemplate $template): array => $template->offeredPlanIds())->unique())
            ->orderBy('price')
            ->get();

        $offers = $templates->map(fn (OrderFormTemplate $template): array => [
            'template' => $template,
            'plans' => $plans->filter(fn (SubscriptionPlan $plan): bool => $template->offersPlan($plan->id)),
            'checkout_url' => OrderForm::getUrl(['template' => $template->slug], panel: 'client'),
        ])->filter(fn (array $offer): bool => $offer['plans']->isNotEmpty());

        return view('welcome', compact('offers'));
    }
}
