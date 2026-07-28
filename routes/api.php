<?php

declare(strict_types=1);

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CallRateController;
use App\Http\Controllers\Api\CannedResponseController;
use App\Http\Controllers\Api\ClientContactController;
use App\Http\Controllers\Api\CrossPlatformIntegrationController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\DidNumberController;
use App\Http\Controllers\Api\GitIntegrationController;
use App\Http\Controllers\Api\GitWebhookController;
use App\Http\Controllers\Api\InboundEmailController;
use App\Http\Controllers\Api\InfrastructureController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\IspServiceController;
use App\Http\Controllers\Api\KnowledgeBaseController;
use App\Http\Controllers\Api\LicenseDownloadController;
use App\Http\Controllers\Api\LicenseValidationController;
use App\Http\Controllers\Api\OrganisationController;
use App\Http\Controllers\Api\PackageGroupController;
use App\Http\Controllers\Api\QuoteController;
use App\Http\Controllers\Api\ResellerController;
use App\Http\Controllers\Api\SubscriptionController;
use App\Http\Controllers\Api\VoipAccountController;
use App\Http\Controllers\Api\WebhookController;
use App\Http\Controllers\ClientNoteController;
use App\Http\Controllers\InstallationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Health check (no auth required)
Route::get('health', fn () => response()->json([
    'status' => 'ok',
    'timestamp' => now()->toISOString(),
    'version' => config('app.version', '1.0.0'),
]));

// Public routes
Route::post('auth/token', [AuthController::class, 'token'])
    ->middleware('throttle:5,1');

// Protected routes
Route::middleware(['auth:sanctum', 'throttle:api'])->group(function (): void {
    // User endpoint
    Route::get('/user', fn (Request $request) => $request->user());

    // Token management
    Route::delete('auth/token', [AuthController::class, 'revokeToken']);
    Route::delete('auth/tokens', [AuthController::class, 'revokeAllTokens']);

    // Installation endpoint (operators only)
    Route::post('/install', [InstallationController::class, 'install'])
        ->middleware('role:super_admin');

    // Invoice endpoints
    Route::middleware('ability:invoices:read')->group(function (): void {
        Route::get('invoices', [InvoiceController::class, 'index'])->name('invoices.index');
        Route::get('invoices/{invoice}/download', [InvoiceController::class, 'download'])->name('invoices.download');
        Route::get('invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
    });
    Route::middleware('ability:invoices:write')->group(function (): void {
        Route::post('invoices', [InvoiceController::class, 'store'])->name('invoices.store');
        Route::match(['put', 'patch'], 'invoices/{invoice}', [InvoiceController::class, 'update'])->name('invoices.update');
        Route::delete('invoices/{invoice}', [InvoiceController::class, 'destroy'])->name('invoices.destroy');
    });

    // Subscription endpoints
    Route::middleware('ability:subscriptions:read')->group(function (): void {
        Route::get('subscriptions', [SubscriptionController::class, 'index'])->name('subscriptions.index');
        Route::get('subscriptions/{subscription}', [SubscriptionController::class, 'show'])->name('subscriptions.show');
    });
    Route::middleware('ability:subscriptions:write')->group(function (): void {
        Route::post('subscriptions', [SubscriptionController::class, 'store'])->name('subscriptions.store');
        Route::match(['put', 'patch'], 'subscriptions/{subscription}', [SubscriptionController::class, 'update'])->name('subscriptions.update');
        Route::delete('subscriptions/{subscription}', [SubscriptionController::class, 'destroy'])->name('subscriptions.destroy');
        Route::post('subscriptions/{subscription}/cancel', [SubscriptionController::class, 'cancel']);
        Route::post('subscriptions/{subscription}/renew', [SubscriptionController::class, 'renew']);
    });

    // Customer endpoints
    Route::middleware('ability:customers:read')->group(function (): void {
        Route::get('customers', [CustomerController::class, 'index'])->name('customers.index');
        Route::get('customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');
        Route::get('customers/{customer}/invoices', [CustomerController::class, 'invoices']);
        Route::get('customers/{customer}/subscriptions', [CustomerController::class, 'subscriptions']);
        Route::get('customers/{customer}/contacts', [ClientContactController::class, 'index']);
        Route::get('customers/{customer}/contacts/{contact}', [ClientContactController::class, 'show']);
    });
    Route::middleware('ability:customers:write')->group(function (): void {
        Route::post('customers', [CustomerController::class, 'store'])->name('customers.store');
        Route::match(['put', 'patch'], 'customers/{customer}', [CustomerController::class, 'update'])->name('customers.update');
        Route::delete('customers/{customer}', [CustomerController::class, 'destroy'])->name('customers.destroy');
        Route::post('customers/{customer}/contacts', [ClientContactController::class, 'store']);
        Route::put('customers/{customer}/contacts/{contact}', [ClientContactController::class, 'update']);
        Route::delete('customers/{customer}/contacts/{contact}', [ClientContactController::class, 'destroy']);
        Route::post('customers/{customer}/contacts/{contact}/make-primary', [ClientContactController::class, 'makePrimary']);
    });

    // Client Notes endpoints
    Route::middleware('ability:client-notes:read')->group(function (): void {
        Route::get('client-notes', [ClientNoteController::class, 'index']);
    });
    Route::middleware('ability:client-notes:write')->group(function (): void {
        Route::post('client-notes', [ClientNoteController::class, 'store']);
        Route::delete('client-notes/{note}', [ClientNoteController::class, 'destroy']);
    });

    // Webhook endpoints (single manage ability)
    Route::middleware('ability:webhooks:manage')->group(function (): void {
        Route::apiResource('webhooks', WebhookController::class);
        Route::post('webhooks/{webhook}/test', [WebhookController::class, 'test']);
        Route::get('webhooks/{webhook}/events', [WebhookController::class, 'events']);
        Route::get('webhook-event-types', [WebhookController::class, 'eventTypes']);
        Route::post('webhook-events/{event}/retry', [WebhookController::class, 'retryEvent']);
    });

    // Canned Response endpoints (read-only taxonomy)
    Route::middleware('ability:canned-responses:read')->group(function (): void {
        Route::get('canned-responses', [CannedResponseController::class, 'index']);
        Route::get('canned-responses/search', [CannedResponseController::class, 'search']);
        Route::get('canned-responses/categories', [CannedResponseController::class, 'categories']);
        Route::get('canned-responses/most-used', [CannedResponseController::class, 'mostUsed']);
        Route::get('canned-responses/variables', [CannedResponseController::class, 'variables']);
        Route::get('canned-responses/{shortcode}', [CannedResponseController::class, 'show']);
    });
    Route::middleware('ability:canned-responses:write')->group(function (): void {
        Route::post('canned-responses/{shortcode}/use', [CannedResponseController::class, 'use']);
    });

    // Quote/Proposal endpoints (Blesta)
    Route::middleware('ability:quotes:read')->group(function (): void {
        Route::get('quotes', [QuoteController::class, 'index'])->name('quotes.index');
        Route::get('quotes/{quote}', [QuoteController::class, 'show'])->name('quotes.show');
        Route::get('quotes-statistics', [QuoteController::class, 'statistics']);
    });
    Route::middleware('ability:quotes:write')->group(function (): void {
        Route::post('quotes', [QuoteController::class, 'store'])->name('quotes.store');
        Route::match(['put', 'patch'], 'quotes/{quote}', [QuoteController::class, 'update'])->name('quotes.update');
        Route::delete('quotes/{quote}', [QuoteController::class, 'destroy'])->name('quotes.destroy');
        Route::post('quotes/{quote}/send', [QuoteController::class, 'send']);
        Route::post('quotes/{quote}/accept', [QuoteController::class, 'accept']);
        Route::post('quotes/{quote}/decline', [QuoteController::class, 'decline']);
        Route::post('quotes/{quote}/convert', [QuoteController::class, 'convert']);
    });

    // Package Group endpoints (Blesta)
    Route::middleware('ability:package-groups:read')->group(function (): void {
        Route::get('package-groups', [PackageGroupController::class, 'index'])->name('package-groups.index');
        Route::get('package-groups/{packageGroup}', [PackageGroupController::class, 'show'])->name('package-groups.show');
    });
    Route::middleware('ability:package-groups:write')->group(function (): void {
        Route::post('package-groups', [PackageGroupController::class, 'store'])->name('package-groups.store');
        Route::match(['put', 'patch'], 'package-groups/{packageGroup}', [PackageGroupController::class, 'update'])->name('package-groups.update');
        Route::delete('package-groups/{packageGroup}', [PackageGroupController::class, 'destroy'])->name('package-groups.destroy');
        Route::post('package-groups/{packageGroup}/packages', [PackageGroupController::class, 'addPackage']);
        Route::delete('package-groups/{packageGroup}/packages/{plan}', [PackageGroupController::class, 'removePackage']);
        Route::post('package-groups/{packageGroup}/reorder', [PackageGroupController::class, 'reorder']);
    });

    Route::middleware('ability:isp-services:read')->group(function (): void {
        Route::get('isp-services', [IspServiceController::class, 'index'])->name('isp-services.index');
        Route::get('isp-services/{ispService}', [IspServiceController::class, 'show'])->name('isp-services.show');
    });
    Route::middleware('ability:isp-services:write')->group(function (): void {
        Route::post('isp-services', [IspServiceController::class, 'store'])->name('isp-services.store');
        Route::match(['put', 'patch'], 'isp-services/{ispService}', [IspServiceController::class, 'update'])
            ->name('isp-services.update');
        Route::delete('isp-services/{ispService}', [IspServiceController::class, 'destroy'])
            ->name('isp-services.destroy');
        Route::post('isp-services/{ispService}/activate', [IspServiceController::class, 'activate']);
        Route::post('isp-services/{ispService}/synchronize', [IspServiceController::class, 'synchronize']);
        Route::post('isp-services/{ispService}/suspend', [IspServiceController::class, 'suspend']);
        Route::post('isp-services/{ispService}/accounting', [IspServiceController::class, 'accounting']);
    });

    Route::middleware('ability:voip:read')->group(function (): void {
        Route::get('voip/accounts', [VoipAccountController::class, 'index']);
        Route::get('voip/accounts/{voipAccount}', [VoipAccountController::class, 'show']);
        Route::get('voip/rates', [CallRateController::class, 'index']);
        Route::get('voip/dids', [DidNumberController::class, 'index']);
    });
    Route::middleware('ability:voip:write')->group(function (): void {
        Route::post('voip/accounts', [VoipAccountController::class, 'store']);
        Route::match(['put', 'patch'], 'voip/accounts/{voipAccount}', [VoipAccountController::class, 'update']);
        Route::delete('voip/accounts/{voipAccount}', [VoipAccountController::class, 'destroy']);
        Route::post('voip/accounts/{voipAccount}/provision', [VoipAccountController::class, 'provision']);
        Route::post('voip/accounts/{voipAccount}/cdrs', [VoipAccountController::class, 'ingestCdr']);
        Route::post('voip/rates', [CallRateController::class, 'store']);
        Route::match(['put', 'patch'], 'voip/rates/{callRate}', [CallRateController::class, 'update']);
        Route::delete('voip/rates/{callRate}', [CallRateController::class, 'destroy']);
        Route::post('voip/dids', [DidNumberController::class, 'store']);
        Route::post('voip/dids/{didNumber}/assign', [DidNumberController::class, 'assign']);
        Route::delete('voip/dids/{didNumber}', [DidNumberController::class, 'destroy']);
    });

    Route::middleware('ability:git:read')->group(function (): void {
        Route::get('git/connections', [GitIntegrationController::class, 'connections']);
        Route::get('git/repositories', [GitIntegrationController::class, 'repositories']);
        Route::get('git/repositories/{repository}', [GitIntegrationController::class, 'repository']);
    });
    Route::middleware('ability:git:write')->group(function (): void {
        Route::post('git/connections', [GitIntegrationController::class, 'storeConnection']);
        Route::patch('git/connections/{connection}', [GitIntegrationController::class, 'updateConnection']);
        Route::delete('git/connections/{connection}', [GitIntegrationController::class, 'destroyConnection']);
        Route::post('git/connections/{connection}/sync', [GitIntegrationController::class, 'sync']);
        Route::post('git/repositories/{repository}/releases', [GitIntegrationController::class, 'createRelease']);
        Route::post('git/releases/{release}/deployment', [GitIntegrationController::class, 'trackDeployment']);
    });

    Route::middleware('ability:infrastructure:read')->group(function (): void {
        Route::get('infrastructure/assets', [InfrastructureController::class, 'assets']);
        Route::get('infrastructure/ip-pools', [InfrastructureController::class, 'pools']);
        Route::get('infrastructure/ip-pools/{pool}/addresses', [InfrastructureController::class, 'addresses']);
    });
    Route::middleware('ability:infrastructure:write')->group(function (): void {
        Route::post('infrastructure/assets', [InfrastructureController::class, 'storeAsset']);
        Route::patch('infrastructure/assets/{asset}', [InfrastructureController::class, 'updateAsset']);
        Route::post('infrastructure/ip-pools', [InfrastructureController::class, 'storePool']);
        Route::post('infrastructure/ip-pools/{pool}/allocate', [InfrastructureController::class, 'allocate']);
        Route::post('infrastructure/ip-addresses/{address}/release', [InfrastructureController::class, 'release']);
    });

    Route::middleware('ability:organisations:read')->group(function (): void {
        Route::get('organisations', [OrganisationController::class, 'children']);
        Route::get('organisations/{organisation}/brands', [OrganisationController::class, 'brands']);
    });
    Route::middleware('ability:organisations:write')->group(function (): void {
        Route::post('organisations', [OrganisationController::class, 'storeChild']);
        Route::patch('organisations/{organisation}', [OrganisationController::class, 'updateChild']);
        Route::post('organisations/{organisation}/archive', [OrganisationController::class, 'archive']);
        Route::post('organisations/{organisation}/brands', [OrganisationController::class, 'storeBrand']);
        Route::post('organisation-brands/{brand}/domains', [OrganisationController::class, 'storeDomain']);
        Route::post('brand-domains/{domain}/verify', [OrganisationController::class, 'verifyDomain']);
    });
    Route::middleware('ability:resellers:read')->group(function (): void {
        Route::get('resellers', [ResellerController::class, 'index']);
        Route::get('resellers/{agreement}', [ResellerController::class, 'show']);
    });
    Route::middleware('ability:resellers:write')->group(function (): void {
        Route::post('resellers', [ResellerController::class, 'store']);
        Route::post('resellers/{agreement}/delegate', [ResellerController::class, 'delegate']);
        Route::post('reseller-delegations/{delegation}/revenue', [ResellerController::class, 'recordRevenue']);
        Route::post('reseller-transactions/{transaction}/settle', [ResellerController::class, 'settle']);
    });

    Route::middleware('ability:integrations:read')->group(function (): void {
        Route::get('integrations/connections', [CrossPlatformIntegrationController::class, 'index']);
        Route::get(
            'integrations/connections/{connection}/pull/{resource}',
            [CrossPlatformIntegrationController::class, 'pullResource']
        );
        Route::get('integrations/events', [CrossPlatformIntegrationController::class, 'eventLog']);
    });
    Route::middleware('ability:integrations:write')->group(function (): void {
        Route::post('integrations/connections', [CrossPlatformIntegrationController::class, 'store']);
        Route::patch('integrations/connections/{connection}', [CrossPlatformIntegrationController::class, 'update']);
        Route::delete('integrations/connections/{connection}', [CrossPlatformIntegrationController::class, 'destroy']);
        Route::post(
            'integrations/sync/{resource}/{id}',
            [CrossPlatformIntegrationController::class, 'pushResource']
        );
        Route::post('integrations/leads/{lead}/convert', [CrossPlatformIntegrationController::class, 'convertLead']);
        Route::post(
            'integrations/customers/{customer}/bill-opportunity',
            [CrossPlatformIntegrationController::class, 'billOpportunity']
        );
        Route::post('integrations/events/{event}/replay', [CrossPlatformIntegrationController::class, 'replayEvent']);
    });
});

Route::post('git/webhooks/{connection}', GitWebhookController::class)
    ->middleware('throttle:120,1');
Route::get('branding', [OrganisationController::class, 'resolveBrand'])
    ->middleware('throttle:120,1');

// Public Knowledge Base endpoints (no auth required)
Route::prefix('knowledge-base')->group(function (): void {
    Route::get('categories', [KnowledgeBaseController::class, 'categories']);
    Route::get('search', [KnowledgeBaseController::class, 'search']);
    Route::get('featured', [KnowledgeBaseController::class, 'featured']);
    Route::get('popular', [KnowledgeBaseController::class, 'popular']);
    Route::get('articles/{slug}', [KnowledgeBaseController::class, 'show']);
    Route::post('articles/{slug}/helpful', [KnowledgeBaseController::class, 'markHelpful']);
    Route::post('articles/{slug}/not-helpful', [KnowledgeBaseController::class, 'markNotHelpful']);
    Route::get('categories/{categoryId}/articles', [KnowledgeBaseController::class, 'byCategory']);
});

// Inbound email webhook: HMAC-verified (X-Webhook-Signature over the raw body,
// see VerifyInboundEmailSignature — fails closed if INBOUND_EMAIL_SECRET unset)
// so the sender `from` can't be spoofed; throttled against unauthenticated abuse.
Route::post('inbound-email', [InboundEmailController::class, 'store'])
    ->middleware(['inbound-email.verify', 'throttle:60,1']);

// Public license endpoints (SDK calls anonymously with a license key).
// Throttled: these are unauthenticated, so the limit is the guard against
// key-enumeration and download DoS.
Route::post('v1/license/validate', [LicenseValidationController::class, 'validateLicense'])
    ->middleware('throttle:30,1');
Route::post('v1/license/download', [LicenseDownloadController::class, 'download'])
    ->middleware('throttle:30,1');
