<?php
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\FocalPerson\DvRequestController;
use App\Http\Controllers\IMSD\DvRequestProcessingController;
use App\Http\Controllers\Incoming\GIPDvRequestProcessingController;
use App\Http\Controllers\Auth\FocalPersonController;
use App\Http\Controllers\Auth\FocalPersonRegisterController;
use App\Http\Controllers\Admin\AccountingUnitController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\ProfileController;
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
            Route::get('/{dvRequest}/logs', [DvRequestController::class, 'logs'])->name('logs'); // Logs
            Route::delete('/{dvRequest}', [DvRequestController::class, 'destroy'])->name('destroy');
            });
});


Route::middleware(['auth:web', 'role:IMSD'])->prefix('RegionalOffices/IMSD')->group(function () {

    // Index page — list all DV requests for IMSD
    Route::get('/index', [DvRequestProcessingController::class, 'index'])
        ->name('RegionalOffices.IMSD.index');

    Route::get('/fetch-all', [DvRequestProcessingController::class, 'fetchAll'])
        ->name('RegionalOffices.IMSD.fetchAll');
    // Show / process a single DV request
    Route::get('/dvrequests/{id}', [DvRequestProcessingController::class, 'show'])
        ->name('RegionalOffices.IMSD.verifydv');

    // Verify / approve a DV request
    Route::post('/dvrequests/{id}/verify', [DvRequestProcessingController::class, 'verify'])
        ->name('IMSD.dvrequests.verify');
});


Route::middleware(['auth:web', 'role:Incoming-GIP'])->prefix('Incoming/GIP')->group(function () {

    // Index page — list all DV requests for Incoming GIP
     Route::get('/index', [GIPDvRequestProcessingController::class, 'index'])
        ->name('Incoming.GIP.index');
        // routes/web.php
    Route::get('/dvrequests/verify', [GIPDvRequestProcessingController::class, 'redirectToFirstPending'])
    ->name('Incoming.GIP.verifygipdv');

    // Show / process a single DV request 
    Route::get('/dvrequests/{id}', [GIPDvRequestProcessingController::class, 'show'])
        ->name('Incoming.GIP.dvrequests.withid');
        
    Route::get('/fetch-all', [GIPDvRequestProcessingController::class, 'fetchAll'])
        ->name('Incoming.GIP.fetchAll');

    Route::post('/dvrequests/{id}/verify', [GIPDvRequestProcessingController::class, 'verify'])
        ->name('Incoming.GIP.dvrequests.verify');
    
      Route::get('/dvrequests/{id}/fetch', [GIPDvRequestProcessingController::class, 'fetch'])
            ->name('Incoming.GIP.dvrequests.fetch');
});
