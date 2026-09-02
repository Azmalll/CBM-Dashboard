<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EquipmentController;
use App\Http\Controllers\MeasurementPointController;
use App\Http\Controllers\InspectionController;
use App\Http\Controllers\MeasurementResultController;
use App\Http\Controllers\EquipmentInspectionController;
use App\Http\Controllers\EquipmentInspectionReportController;
use App\Http\Controllers\OdxImportController;
use App\Http\Controllers\UnitCorrectionController;
use App\Http\Middleware\RoleMiddleware;


/*
|--------------------------------------------------------------------------
| AUTHENTICATION
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {

    Route::get(
        '/login',
        [LoginController::class, 'create']
    )->name('login');

    Route::post(
        '/login',
        [LoginController::class, 'store']
    )->name('login.store');

});


Route::post(
    '/logout',
    [LoginController::class, 'destroy']
)->middleware('auth')->name('logout');


/*
|--------------------------------------------------------------------------
| AUTHENTICATED APPLICATION
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | MAIN MENU
    |--------------------------------------------------------------------------
    */

    Route::get('/', function () {
        return view('welcome');
    })->name('home');


    /*
    |--------------------------------------------------------------------------
    | DASHBOARD
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/dashboard/{equipment_id?}/{measurement_point_id?}',
        [DashboardController::class, 'index']
    )->name('dashboard');


    /*
    |--------------------------------------------------------------------------
    | EQUIPMENT
    |--------------------------------------------------------------------------
    |
    | Viewer: index + show only.
    | Admin: create/store/edit/update/destroy.
    |
    */

    Route::resource(
        'equipment',
        EquipmentController::class
    )->except([
        'index',
        'show',
    ])->middleware(RoleMiddleware::class . ':admin');


    Route::resource(
        'equipment',
        EquipmentController::class
    )->only([
        'index',
        'show',
    ]);


    /*
    |--------------------------------------------------------------------------
    | MEASUREMENT POINT
    |--------------------------------------------------------------------------
    */

    Route::resource(
        'measurement-point',
        MeasurementPointController::class
    )->except([
        'index',
        'show',
    ])->middleware(RoleMiddleware::class . ':admin');


    Route::resource(
        'measurement-point',
        MeasurementPointController::class
    )->only([
        'index',
        'show',
    ]);


    /*
    |--------------------------------------------------------------------------
    | INSPECTION SESSION
    |--------------------------------------------------------------------------
    */

    Route::resource(
        'inspection',
        InspectionController::class
    )->except([
        'index',
        'show',
    ])->middleware(RoleMiddleware::class . ':admin');


    Route::resource(
        'inspection',
        InspectionController::class
    )->only([
        'index',
        'show',
    ]);


    /*
    |--------------------------------------------------------------------------
    | MEASUREMENT RESULT
    |--------------------------------------------------------------------------
    */

    Route::resource(
        'measurement-result',
        MeasurementResultController::class
    )->except([
        'index',
        'show',
    ])->middleware(RoleMiddleware::class . ':admin');


    Route::resource(
        'measurement-result',
        MeasurementResultController::class
    )->only([
        'index',
        'show',
    ]);


    /*
    |--------------------------------------------------------------------------
    | UPDATE INSPECTOR - SINGLE MEASUREMENT
    |--------------------------------------------------------------------------
    */

    Route::post(
        '/measurement-result/{measurementResult}/update-inspector',
        [
            MeasurementResultController::class,
            'updateInspector',
        ]
    )
        ->middleware(RoleMiddleware::class . ':admin')
        ->name('measurement-result.update-inspector');


    /*
    |--------------------------------------------------------------------------
    | BULK ASSIGN INSPECTOR
    |--------------------------------------------------------------------------
    */

    Route::post(
        '/measurement-result/bulk-assign-inspector',
        [
            MeasurementResultController::class,
            'bulkAssignInspector',
        ]
    )
        ->middleware(RoleMiddleware::class . ':admin')
        ->name('measurement-result.bulk-assign-inspector');


    /*
    |--------------------------------------------------------------------------
    | EQUIPMENT INSPECTION - VIEW
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/equipment-inspection/{equipmentInspection}',
        [EquipmentInspectionController::class, 'show']
    )->name('equipment-inspection.show');


    /*
    |--------------------------------------------------------------------------
    | EQUIPMENT INSPECTION - EDIT
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/equipment-inspection/{equipmentInspection}/edit',
        [EquipmentInspectionController::class, 'edit']
    )
        ->middleware(RoleMiddleware::class . ':admin')
        ->name('equipment-inspection.edit');


    Route::put(
        '/equipment-inspection/{equipmentInspection}',
        [EquipmentInspectionController::class, 'update']
    )
        ->middleware(RoleMiddleware::class . ':admin')
        ->name('equipment-inspection.update');


    /*
|--------------------------------------------------------------------------
| TEMPORARY DEBUG - REMOVE AFTER USE
| Shows production DB host (no password). Admin only.
|--------------------------------------------------------------------------
*/

Route::get(
    '/_db-info',
    function () {
        abort_unless(auth()->user()?->isAdmin(), 403);

        return response()
            ->view('debug.db-info')
            ->header('Cache-Control', 'no-store');
    }
)
    ->middleware(RoleMiddleware::class . ':admin');


    /*
|--------------------------------------------------------------------------
| OPERATING PARAMETERS - EDIT
|--------------------------------------------------------------------------
*/

Route::get(
    '/equipment-inspection/{equipmentInspection}/operating-parameters/edit',
    [EquipmentInspectionController::class, 'editOperatingParameters']
)
    ->middleware(RoleMiddleware::class . ':admin')
    ->name('equipment-inspection.operating-parameters.edit');


Route::put(
    '/equipment-inspection/{equipmentInspection}/operating-parameters',
    [EquipmentInspectionController::class, 'updateOperatingParameters']
)
    ->middleware(RoleMiddleware::class . ':admin')
    ->name('equipment-inspection.operating-parameters.update');

    /*
|--------------------------------------------------------------------------
| UNIT CORRECTION (BULK)
|--------------------------------------------------------------------------
*/

Route::get(
    '/unit-correction',
    [UnitCorrectionController::class, 'index']
)
    ->middleware(RoleMiddleware::class . ':admin')
    ->name('unit-correction.index');

Route::post(
    '/unit-correction/preview',
    [UnitCorrectionController::class, 'preview']
)
    ->middleware(RoleMiddleware::class . ':admin')
    ->name('unit-correction.preview');

Route::post(
    '/unit-correction/apply',
    [UnitCorrectionController::class, 'apply']
)
    ->middleware(RoleMiddleware::class . ':admin')
    ->name('unit-correction.apply');

Route::post(
    '/unit-correction/{logId}/undo',
    [UnitCorrectionController::class, 'undo']
)
    ->middleware(RoleMiddleware::class . ':admin')
    ->name('unit-correction.undo');
    /*

    
    |--------------------------------------------------------------------------
    | ADD MEASUREMENT
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/equipment-inspection/{equipmentInspection}/measurement/create',
        [MeasurementResultController::class, 'create']
    )
        ->middleware(RoleMiddleware::class . ':admin')
        ->name('equipment-inspection.measurement.create');


    Route::post(
        '/equipment-inspection/{equipmentInspection}/measurement',
        [MeasurementResultController::class, 'store']
    )
        ->middleware(RoleMiddleware::class . ':admin')
        ->name('equipment-inspection.measurement.store');


    /*
    |--------------------------------------------------------------------------
    | VIBRATION TREND
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/equipment-inspection/{equipmentInspection}/trend',
        [EquipmentInspectionController::class, 'trend']
    )->name('equipment-inspection.trend');


    /*
    |--------------------------------------------------------------------------
    | ANALYSIS REPORT
    |--------------------------------------------------------------------------
    |
    | Viewer can open the PDF.
    | Admin can upload / replace it.
    |
    */

    Route::get(
        '/equipment-inspection/{equipmentInspection}/analysis-report',
        [EquipmentInspectionReportController::class, 'show']
    )->name('equipment-inspection.analysis-report.show');


    Route::post(
        '/equipment-inspection/{equipmentInspection}/analysis-report',
        [EquipmentInspectionReportController::class, 'upload']
    )
        ->middleware(RoleMiddleware::class . ':admin')
        ->name('equipment-inspection.analysis-report.upload');


    /*
    |--------------------------------------------------------------------------
    | REPORT
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/equipment-inspection/{equipmentInspection}/report',
        [EquipmentInspectionController::class, 'report']
    )->name('equipment-inspection.report');


    /*
    |--------------------------------------------------------------------------
    | ODX IMPORT
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/odx-import',
        [OdxImportController::class, 'create']
    )
        ->middleware(RoleMiddleware::class . ':admin')
        ->name('odx-import.create');


    Route::post(
        '/odx-import',
        [OdxImportController::class, 'store']
    )
        ->middleware(RoleMiddleware::class . ':admin')
        ->name('odx-import.store');
    
});