<?php

use App\Http\Controllers\Api\V1\AccountManagement\AdminController;
use App\Http\Controllers\Api\V1\AccountManagement\BranchController;
use App\Http\Controllers\Api\V1\AccountManagement\CompanyController;
use App\Http\Controllers\Api\V1\AccountManagement\RestaurantController;
use App\Http\Controllers\Api\V1\AccountManagement\RoleController;
use App\Http\Controllers\Api\V1\AccountManagement\UserController;
use App\Http\Controllers\Api\V1\SystemSetting\AccessControlController;
use App\Http\Controllers\Api\V1\SystemSetting\AuditController;
use App\Http\Controllers\Api\V1\SystemSetting\BackendLocaleController;
use App\Http\Controllers\Api\V1\SystemSetting\HolidayPreferenceController;
use App\Http\Controllers\Api\V1\SystemSetting\LocaleController;
use App\Http\Controllers\Api\V1\SystemSetting\PermissionController;
use App\Http\Controllers\Api\V1\SystemSetting\SelectionController;
use App\Http\Controllers\Api\V1\SystemSetting\SystemSettingController;
use App\Http\Controllers\Api\V1\SystemSetting\SystemSettingTypeController;
use App\Http\Controllers\Api\V1\MenuManagement\MenuCategoryController;
use App\Http\Controllers\Api\V1\MenuManagement\MenuItemController;
use App\Http\Controllers\Api\V1\MenuManagement\MenuServedDateController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('/health', function () {
        return response()->json([
            'code' => '0000',
            'message' => 'Admin API is healthy.',
            'data' => [
                'app' => config('app.name'),
                'environment' => app()->environment(),
                'laravel' => app()->version(),
                'php' => PHP_VERSION,
            ],
        ]);
    });

    Route::prefix('account-management')->group(function () {
        Route::get('roles/options', [RoleController::class, 'options']);
        Route::post('roles/permissions', [RoleController::class, 'addPermission']);
        Route::apiResource('roles', RoleController::class);

        Route::get('admins/options', [AdminController::class, 'options']);
        Route::apiResource('admins', AdminController::class)->parameters(['admins' => 'admin']);

        Route::get('companies/options', [CompanyController::class, 'options']);
        Route::apiResource('companies', CompanyController::class)->parameters(['companies' => 'company']);

        Route::get('branches/options', [BranchController::class, 'options']);
        Route::apiResource('branches', BranchController::class)->parameters(['branches' => 'branch']);

        Route::get('restaurants/options', [RestaurantController::class, 'options']);
        Route::apiResource('restaurants', RestaurantController::class)->parameters(['restaurants' => 'restaurant']);

        Route::get('users/options', [UserController::class, 'options']);
        Route::get('users/search-username', [UserController::class, 'searchUsername']);
        Route::get('users/payment-method', [UserController::class, 'getPaymentMethod']);
        Route::apiResource('users', UserController::class)->parameters(['users' => 'user']);
    });

    Route::prefix('system-setting')->group(function () {
        Route::get('permissions/options', [PermissionController::class, 'options']);
        Route::apiResource('permissions', PermissionController::class);

        Route::apiResource('locales', LocaleController::class)->only(['index', 'store', 'show', 'update']);

        Route::get('system-settings/options', [SystemSettingController::class, 'options']);
        Route::patch('system-settings/{systemSetting}/toggle-status', [SystemSettingController::class, 'toggleStatus']);
        Route::apiResource('system-settings', SystemSettingController::class)->parameters(['system-settings' => 'systemSetting']);

        Route::apiResource('system-setting-types', SystemSettingTypeController::class)->parameters(['system-setting-types' => 'systemSettingType']);

        Route::get('selections/options', [SelectionController::class, 'options']);
        Route::apiResource('selections', SelectionController::class)->only(['index', 'store', 'show', 'update']);

        Route::get('access-controls/options', [AccessControlController::class, 'options']);
        Route::apiResource('access-controls', AccessControlController::class)->parameters(['access-controls' => 'accessControl']);

        Route::get('backend-locales/options', [BackendLocaleController::class, 'options']);
        Route::apiResource('backend-locales', BackendLocaleController::class)->only(['index', 'store', 'show', 'update'])->parameters(['backend-locales' => 'backendLocale']);

        Route::apiResource('audits', AuditController::class)->only(['index', 'show']);

        Route::post('holiday-preferences/toggle-weekend', [HolidayPreferenceController::class, 'toggleWeekend']);
        Route::post('holiday-preferences/toggle-holiday', [HolidayPreferenceController::class, 'toggleHoliday']);
    });

    Route::prefix('menu-management')->group(function () {
        Route::get('menu-categories/options', [MenuCategoryController::class, 'options']);
        Route::get('menu-categories/{menuCategory}/details', [MenuItemController::class, 'details']);
        Route::apiResource('menu-categories', MenuCategoryController::class)->parameters(['menu-categories' => 'menuCategory']);

        Route::get('menu-items/options', [MenuItemController::class, 'options']);
        Route::post('menu-items/import-store', [MenuItemController::class, 'importStore']);
        Route::get('menu-items/export', [MenuItemController::class, 'export']);
        Route::apiResource('menu-items', MenuItemController::class)->parameters(['menu-items' => 'menuItem']);

        Route::delete('menu-served-dates/delete', [MenuServedDateController::class, 'destroyByRequest']);
        Route::delete('menu-served-dates/{menuServedDate}', [MenuServedDateController::class, 'destroy']);
    });
});
