<?php
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\FocalPerson\DvRequestController;
use App\Http\Controllers\BudgetOfficer\ObligateDVORSController;
use App\Http\Controllers\IMSD\DvRequestProcessingController;
use App\Http\Controllers\Incoming\GIPDvRequestProcessingController;
use App\Http\Controllers\Incoming\TupadDvRequestProcessingController;
use App\Http\Controllers\Auth\FocalPersonController;
use App\Http\Controllers\FocalPerson\FocalProfileController;
use App\Http\Controllers\Auth\FocalPersonRegisterController;
use App\Http\Controllers\Admin\AccountingUnitController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\FocalPerson\NotificationController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Authentication Routes

Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('login', [LoginController::class, 'login'])->name('login.submit');
Route::post('logout', [LoginController::class, 'logout'])->name('logout');


Route::get('/auth/focalpersonregister', [FocalPersonRegisterController::class, 'showRegistrationForm'])->name('focalperson.register.form');
Route::post('/auth/focalpersonregister', [FocalPersonRegisterController::class, 'register'])->name('focalperson.register');





Route::get('/home', function () {
    return view('home');
})->name('home')->middleware('auth');



Route::middleware(['auth','role:Admin'])->group(function () {
      Route::get('/Admin/dashboard', [DashboardController::class, 'index'])
        ->name('Admin.dashboard');

Route::resource('AccountingUnits', AccountingUnitController::class)
    ->names('admin.accountingunits');

});
Route::middleware(['auth','role:Admin'])->group(function () {

    Route::resource('roles', RoleController::class)
    ->names('admin.roles');
    Route::get('permissions', [RoleController::class, 'permissionsIndex'])->name('admin.permissions.index');
    Route::get('permissions/create', [RoleController::class, 'permissionsCreate'])->name('admin.permissions.create');
    Route::post('permissions', [RoleController::class, 'permissionsStore'])->name('admin.permissions.store');
    Route::get('permissions/{permission}/edit', [RoleController::class, 'permissionsEdit'])->name('admin.permissions.edit');
    Route::put('permissions/{permission}', [RoleController::class, 'permissionsUpdate'])->name('admin.permissions.update');
    Route::delete('permissions/{permission}', [RoleController::class, 'permissionsDestroy'])->name('admin.permissions.destroy');
});
   
    Route::middleware(['auth:focal', 'role:Focal_Person'])->group(function () {
        Route::get('/FocalPerson/dvrequest/index', [DvRequestController::class, 'index'])
            ->name('FocalPerson.dvrequest.index');
            Route::prefix('FocalPerson/dvrequest')->name('FocalPerson.dvrequest.')->group(function () {
            Route::get('/submitdvors', [DvRequestController::class, 'create'])->name('submitdvors'); // Submission form
            Route::post('/', [DvRequestController::class, 'store'])->name('store');       // Store request
            Route::get('/{dvRequest}', [DvRequestController::class, 'show'])->name('show'); // <-- add this
            Route::get('/FocalPerson/dvrequest/search', [DvRequestController::class, 'search'])
            ->name('FocalPerson.dvrequest.search');
            Route::get('/{dvRequest}/logs', [DvRequestController::class, 'logs'])->name('logs'); // Logs
            Route::delete('/{dvRequest}', [DvRequestController::class, 'destroy'])->name('destroy');
            Route::get('/{dvRequest}/latest-step', [DvRequestController::class, 'latestStep'])
            ->name('latestStep');
            });
        Route::prefix('FocalPerson/profile')->name('FocalPerson.profile.')->group(function () {
        Route::get('/', [FocalProfileController::class, 'show'])->name('show');
        Route::get('/edit', [FocalProfileController::class, 'edit'])->name('edit');
        Route::post('/update', [FocalProfileController::class, 'update'])->name('update');
    });
    Route::prefix('FocalPerson/notifications')->name('FocalPerson.notifications.')->group(function () {
        Route::get('/fetch', [NotificationController::class, 'fetch'])->name('fetch');
        Route::post('/read/{id}', [NotificationController::class, 'markAsRead'])->name('markAsRead');
        Route::post('/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('markAllAsRead');
    });
});


Route::middleware(['auth:web', 'role:IMSD'])->prefix('RegionalOffices/IMSD')->group(function () {

    // Index page — list all DV requests for IMSD
    Route::get('/index', [DvRequestProcessingController::class, 'index'])
        ->name('RegionalOffices.IMSD.index');
          Route::get('/dvrequests/fetchimsdRecent', [DvRequestProcessingController::class, 'fetchimsdRecent'])
        ->name('RegionalOffices.IMSD.fetchimsdRecent');
    Route::get('/fetch-all', [DvRequestProcessingController::class, 'fetchAll'])
        ->name('RegionalOffices.IMSD.fetchAll');
    Route::get('/dvrequests/{id}', [DvRequestProcessingController::class, 'show'])
        ->name('RegionalOffices.IMSD.verifydv');
    Route::post('/dvrequests/{id}/verify', [DvRequestProcessingController::class, 'verify'])
        ->name('IMSD.dvrequests.verify');
    Route::get('/history', [DvRequestProcessingController::class, 'history'])
        ->name('history');
    Route::get('/redirect-to-first', [DvRequestProcessingController::class, 'redirectToFirstPending'])
    ->name('RegionalOffices.IMSD.redirectFirst');
    Route::get('/dvrequests/{id}/attachments/edit', [DvRequestProcessingController::class, 'editAttachments'])
        ->name('dvRequest.editAttachments');
    Route::put('/dvrequests/{id}/attachments', [DvRequestProcessingController::class, 'updateAttachments'])
        ->name('dvRequest.updateAttachments');
});


Route::middleware(['auth:web', 'role:Incoming-GIP'])->prefix('Incoming/GIP')->group(function () {

    // Index page — list all DV requests for Incoming GIP
     Route::get('/index', [GIPDvRequestProcessingController::class, 'index'])
        ->middleware('permission:gip.view')
        ->name('Incoming.GIP.index');
        // routes/web.php
    Route::get('/dvrequests/verify', [GIPDvRequestProcessingController::class, 'redirectToFirstPending'])
    ->middleware('permission:gip.approve')
    ->name('Incoming.GIP.verifygipdv');
    Route::get('/dvrequests/{id}/fetch', [GIPDvRequestProcessingController::class, 'fetch'])
            ->middleware('permission:gip.edit')
            ->name('Incoming.GIP.dvrequests.fetch');
    Route::get('/dvrequests/fetchgipRecent', [GIPDvRequestProcessingController::class, 'fetchgipRecent'])
    ->middleware('permission:gip.view')
    ->name('Incoming.GIP.dvrequests.fetchgipRecent');
    Route::get('/dvrequests/{id}', [GIPDvRequestProcessingController::class, 'show'])
        ->middleware('permission:gip.view')
        ->name('Incoming.GIP.dvrequests.withid');
    Route::get('/history', [GIPDvRequestProcessingController::class, 'history'])
        ->middleware('permission:gip.view')
        ->name('dvrequests.history');
    Route::get('/fetch-all', [GIPDvRequestProcessingController::class, 'fetchAll'])
        ->middleware('permission:gip.view')
        ->name('Incoming.GIP.fetchAll');
    Route::post('/dvrequests/{id}/verify', [GIPDvRequestProcessingController::class, 'verify'])
        ->middleware('permission:gip.approve')
        ->name('Incoming.GIP.dvrequests.verify');
    Route::post('/dvrequests/{id}/reject', [GIPDvRequestProcessingController::class, 'reject'])
        ->name('Incoming.GIP.dvrequests.reject');
    
});

Route::middleware(['auth:web', 'role:Incoming-TUPAD'])->prefix('Incoming/TUPAD')->group(function () {
     Route::get('/index', [TupadDvRequestProcessingController::class, 'index'])
        ->middleware('permission:tupad.view')
        ->name('Incoming.TUPAD.index');
    Route::get('/dvrequests/verify', [TupadDvRequestProcessingController::class, 'redirectToFirstPending'])
    ->middleware('permission:tupad.approve')
    ->name('Incoming.TUPAD.verifytupaddv');
      Route::get('/dvrequests/fetchtupadRecent', [TupadDvRequestProcessingController::class, 'fetchtupadRecent'])
    ->middleware('permission:tupad.view')
    ->name('Incoming.TUPAD.dvrequests.fetchtupadRecent');
    Route::get('/dvrequests/{id}', [TupadDvRequestProcessingController::class, 'show'])
        ->middleware('permission:tupad.view')
        ->name('Incoming.TUPAD.dvrequests.withid');
    Route::get('/history', [TupadDvRequestProcessingController::class, 'history'])
        ->middleware('permission:tupad.view')
        ->name('dvrequests.history');
    Route::get('/fetch-all', [TupadDvRequestProcessingController::class, 'fetchAll'])
        ->middleware('permission:tupad.view')
        ->name('Incoming.TUPAD.fetchAll');
    Route::post('/dvrequests/{id}/verify', [TupadDvRequestProcessingController::class, 'verify'])
        ->middleware('permission:tupad.approve')
        ->name('Incoming.TUPAD.dvrequests.verify');
    Route::post('/dvrequests/{id}/reject', [TupadDvRequestProcessingController::class, 'reject'])
        ->name('Incoming.TUPAD.dvrequests.reject');
      Route::get('/dvrequests/{id}/fetch', [TupadDvRequestProcessingController::class, 'fetch'])
            ->middleware('permission:tupad.view')
            ->name('Incoming.TUPAD.dvrequests.fetch');
  
});

Route::middleware(['auth:web', 'role:Budget_Officer'])->prefix('/BudgetOfficer')->name('BudgetOfficer.')->group(function () {
        
        Route::get('/dvrequests', [ObligateDVORSController::class, 'index'])->name('dvrequests.index');
        Route::get('/dvrequests/fetch', [ObligateDVORSController::class, 'fetchDvRequests'])
            ->name('dvrequests.fetch');
        Route::get('/dvrequests/recent', [ObligateDVORSController::class, 'fetchRecent'])
            ->name('dvrequests.recent');  
        Route::get('/dvrequests/{id}', [ObligateDVORSController::class, 'show'])
            ->name('dvrequests.show');
        Route::post('/dvrequests/{id}/store', [ObligateDVORSController::class, 'store'])
            ->name('dvrequests.store');
    });