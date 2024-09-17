<?php

namespace Modules\BusinessManagement\Http\Controllers\Web\New\Admin\BusinessSetup;

use App\Http\Controllers\BaseController;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;
use Modules\BusinessManagement\Http\Requests\BusinessInfoStoreOrUpdateRequest;
use Modules\BusinessManagement\Http\Requests\BusinessSettingStoreOrUpdateRequest;
use Modules\BusinessManagement\Service\Interface\BusinessSettingServiceInterface;

class BusinessInfoController extends BaseController
{
    use AuthorizesRequests;

    protected $businessSettingService;

    public function __construct(BusinessSettingServiceInterface $businessSettingService)
    {
        parent::__construct($businessSettingService);
        $this->businessSettingService = $businessSettingService;
    }

    public function index(?Request $request, string $type = null): View|Collection|LengthAwarePaginator|null|callable|RedirectResponse
    {
//        $data = checkMaintenanceMode();
//        if ($data['maintenance_status'] == 0) {
//            $maintenanceMode = $this->businessSettingService
//                ->findOneBy(criteria: ['key_name' => 'maintenance_mode', 'settings_type' => BUSINESS_INFORMATION]);
//            $maintenanceModeData = [
//                'key_name' => 'maintenance_mode',
//                'value' => 0,
//                'settings_type' => BUSINESS_INFORMATION
//            ];
//            if ($maintenanceMode) {
//                $this->businessSettingService->update(id: $maintenanceMode->id, data: $maintenanceModeData);
//            } else {
//                $this->businessSettingService->create(data: $maintenanceModeData);
//            }
//
//        }
        $this->authorize('business_view');
        $settings = $this->businessSettingService
            ->getBy(criteria: ['settings_type' => BUSINESS_INFORMATION]);

        return view('businessmanagement::admin.business-setup.index', compact('settings'));
    }

    public function store(BusinessInfoStoreOrUpdateRequest $request)
    {
        $this->authorize('business_edit');
        $this->businessSettingService->storeBusinessInfo($request->validated());
        Toastr::success(BUSINESS_SETTING_UPDATE_200['message']);
        return back();
    }

    public function updateBusinessSetting(Request $request): JsonResponse
    {
        $this->authorize('business_edit');
        $businessInfo = $this->businessSettingService->findOneBy(criteria: ['key_name' => $request['name'], 'settings_type' => $request['type']]);
        if ($businessInfo) {
            $data = $this->businessSettingService
                ->update(id: $businessInfo->id, data: ['key_name' => $request['name'], 'settings_type' => $request['type'], 'value' => $request['value']]);
        } else {
            $data = $this->businessSettingService
                ->create(data: ['key_name' => $request['name'], 'settings_type' => $request['type'], 'value' => $request['value']]);
        }
        return response()->json($data);
    }

    public function settings()
    {
        $settings = $this->businessSettingService
            ->getBy(criteria: ['settings_type' => 'business_settings']);
        return view('businessmanagement::admin.business-setup.settings', compact('settings'));
    }

    public function updateSettings(BusinessSettingStoreOrUpdateRequest $request): RedirectResponse
    {
        $this->authorize('business_edit');
        $this->businessSettingService->updateSetting($request->validated());
        Toastr::success(BUSINESS_SETTING_UPDATE_200['message']);
        return back();
    }

    public function maintenance(Request $request)
    {
        $this->authorize('super-admin');
        if ($request->has('status')) {
            $data = $this->businessSettingService->maintenance(data: $request->all());
            $checkMaintenanceMode = checkMaintenanceMode();
            if ($checkMaintenanceMode['maintenance_status'] == 0) {
                $topic = "driver_maintenance_mode_off";
                $topic1 = "customer_maintenance_mode_off";
                $title = "Server running now";
                $description = "You can work now";
                $type = "maintenance_mode_off";
                sendTopicNotification(topic: $topic, title: $title, description: $description, type: $type);
                sendTopicNotification(topic: $topic1, title: $title, description: $description, type: $type);
            }

            return response()->json($data);
        }
        $this->businessSettingService->advanceMaintenance(data: $request->all());
        $checkMaintenanceMode = checkMaintenanceMode();

        if (array_key_exists('maintenance_status', $checkMaintenanceMode) && $checkMaintenanceMode['maintenance_status'] == 1) {
            if (array_key_exists('selected_maintenance_system', $checkMaintenanceMode) && array_key_exists('user_app', $checkMaintenanceMode['selected_maintenance_system'])) {
                if ($checkMaintenanceMode['selected_maintenance_system']['user_app'] == 1) {
                    $topicUser = "customer_maintenance_mode_on";
                    $titleUser = "Server is under maintenance";
                    $descriptionUser = "You can not work now";
                    $typeUser = "maintenance_mode_on";
                } else {
                    $topicUser = "customer_maintenance_mode_off";
                    $titleUser = "Server running now";
                    $descriptionUser = "You can work now";
                    $typeUser = "maintenance_mode_off";
                }
                sendTopicNotification(topic: $topicUser, title: $titleUser, description: $descriptionUser, type: $typeUser);
                if ($checkMaintenanceMode['selected_maintenance_system']['driver_app'] == 1) {
                    $topic = "driver_maintenance_mode_on";
                    $title = "Server is under maintenance";
                    $description = "You can not work now";
                    $type = "maintenance_mode_on";
                } else {
                    $topic = "driver_maintenance_mode_off";
                    $title = "Server running now";
                    $description = "You can work now";
                    $type = "maintenance_mode_off";
                }
                sendTopicNotification(topic: $topic, title: $title, description: $description, type: $type);
            }
        }
        Toastr::success(BUSINESS_SETTING_UPDATE_200['message']);
        return back();
    }
}
