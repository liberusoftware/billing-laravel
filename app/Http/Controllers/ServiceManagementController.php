<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use App\Services\ServiceProvisioningService;
use Exception;
use Illuminate\Http\Request;

class ServiceManagementController extends Controller
{
    public function __construct(protected ServiceProvisioningService $serviceProvisioningService) {}

    public function provisionService(Request $request)
    {
        $validatedData = $request->validate(
            [
                'subscription_id' => 'required|exists:subscriptions,id',
            ]
        );

        $subscription = Subscription::findOrFail($validatedData['subscription_id']);

        try {
            $result = $this->serviceProvisioningService->provisionService($subscription);

            return response()->json(
                [
                    'message' => 'Service provisioned successfully',
                    'result' => $result,
                ]
            );
        } catch (Exception $e) {
            return response()->json(
                [
                    'message' => 'Service provisioning failed',
                    'error' => $e->getMessage(),
                ],
                400
            );
        }
    }

    public function manageService(Request $request)
    {
        $validatedData = $request->validate(
            [
                'subscription_id' => 'required|exists:subscriptions,id',
                'action' => 'required|in:suspend,unsuspend,terminate',
            ]
        );

        $subscription = Subscription::findOrFail($validatedData['subscription_id']);

        try {
            $result = $this->serviceProvisioningService->manageService(
                $subscription,
                $validatedData['action']
            );

            return response()->json(
                [
                    'message' => 'Service managed successfully',
                    'result' => $result,
                ]
            );
        } catch (Exception $e) {
            return response()->json(
                [
                    'message' => 'Service management failed',
                    'error' => $e->getMessage(),
                ],
                400
            );
        }
    }
}
