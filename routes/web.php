<?php
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DVORS\VoucherController;
use App\Http\Controllers\FocalPerson\DvRequestController;
use App\Http\Controllers\BudgetOfficer\ObligateDVORSController;
use App\Http\Controllers\BudgetOfficer\ADLController;
use App\Http\Controllers\Outgoing\OutgoingDvController;
use App\Http\Controllers\AccountingIII\RANCAController;
use App\Http\Controllers\IMSD\DvRequestProcessingController;
use App\Http\Controllers\Incoming\GIPDvRequestProcessingController;
use App\Http\Controllers\Incoming\TEVDvRequestProcessingController;
use App\Http\Controllers\Incoming\TupadDvRequestProcessingController;
use App\Http\Controllers\Auth\FocalPersonController;
use App\Http\Controllers\FocalPerson\FocalProfileController;
use App\Http\Controllers\Auth\FocalPersonRegisterController;
use App\Http\Controllers\Admin\AccountingUnitController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\FocalPerson\NotificationController;
use App\Http\Controllers\AccountingUnitProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});


Route::get('/voucher/view', [VoucherController::class, 'preview'])->name('voucher.view');
Route::get('/voucher/pdf', [VoucherController::class, 'generatePDF'])->name('voucher.pdf');

Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('login', [LoginController::class, 'login'])->name('login.submit');
Route::post('logout', [LoginController::class, 'logout'])->name('logout');

Route::get('/auth/focalpersonregister', [FocalPersonRegisterController::class, 'showRegistrationForm'])->name('focalperson.register.form');
Route::post('/auth/focalpersonregister', [FocalPersonRegisterController::class, 'register'])->name('focalperson.register');


Route::middleware(['auth:web'])->group(function (){
    Route::get('profile', [AccountingUnitProfileController::class, 'show'])->name('web.profile.show');
    Route::get('profile/edit', [AccountingUnitProfileController::class, 'edit'])->name('web.profile.edit');
    Route::post('profile/update', [AccountingUnitProfileController::class, 'update'])->name('web.profile.update');
});


Route::get('/home', function () {
    return view('home');
})->name('home')->middleware('auth');


Route::middleware(['auth', 'role:Admin|Accounting_III'])->group(function () {
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
    Route::get('admin/settings', [SettingsController::class, 'edit'])->name('admin.settings.edit');
    Route::post('admin/settings', [SettingsController::class, 'update'])->name('admin.settings.update');
});



    Route::middleware(['auth:focal', 'role:Focal_Person'])->group(function () {
         Route::get('/dvrequest/history', [DvRequestController::class, 'history'])
        ->name('FocalPerson.dvrequest.history');
         Route::get('/dvrequest/history/search', [DvRequestController::class, 'searchHistory'])  
        ->name('FocalPerson.dvrequest.history.search');
        Route::get('/FocalPerson/dvrequest/index', [DvRequestController::class, 'index'])
        ->middleware('permission:focalperson.view')
        ->name('FocalPerson.dvrequest.index');
        Route::prefix('FocalPerson/dvrequest')->name('FocalPerson.dvrequest.')->group(function () {
        Route::get('/submitdvors', [DvRequestController::class, 'create'])
         ->middleware('permission:focalperson.create')
        ->name('submitdvors'); 
        Route::post('/', [DvRequestController::class, 'store'])
         ->middleware('permission:focalperson.create')
        ->name('store');      
        Route::get('/{dvRequest}/edit', [DvRequestController::class, 'edit'])
         ->middleware('permission:focalperson.edit')
        ->name('edit');
        Route::put('/{dvRequest}', [DvRequestController::class, 'update'])
         ->middleware('permission:focalperson.edit')
        ->name('update');
        Route::get('/{dvRequest}', [DvRequestController::class, 'show'])
         ->middleware('permission:focalperson.view')
        ->name('show');
        Route::get('/search', [DvRequestController::class, 'search'])
         ->middleware('permission:focalperson.view')
        ->name('FocalPerson.dvrequest.search');
         Route::get('/{id}/preview', [DvRequestController::class, 'preview'])
        ->name('preview');
        Route::get('/{id}/generate-pdf', [DvRequestController::class, 'generatePDF'])
        ->name('generatePDF');
        Route::get('/{dvRequest}/logs', [DvRequestController::class, 'logs'])
         ->middleware('permission:focalperson.view')
        ->name('logs');
       
    
       
        Route::delete('/{dvRequest}', [DvRequestController::class, 'destroy'])
        ->middleware('permission:focalperson.delete')
        ->name('destroy');

        //Javascript Route
        Route::get('/{dvRequest}/latest-step', [DvRequestController::class, 'latestStep'])
        ->name('latestStep');
        Route::get('/refresh', [DvRequestController::class, 'refresh'])
        ->name('refresh');

        });
        Route::prefix('FocalPerson/profile')->name('FocalPerson.profile.')->group(function () {
        Route::get('/show', [FocalProfileController::class, 'show'])
        ->middleware('permission:focalperson.view')
        ->name('show');
        Route::get('/edit', [FocalProfileController::class, 'edit'])
        ->middleware('permission:focalperson.edit')
        ->name('edit');
        Route::post('/update', [FocalProfileController::class, 'update'])
        ->middleware('permission:focalperson.edit')
        ->name('update');
    });
    Route::prefix('FocalPerson/notifications')->name('FocalPerson.notifications.')->group(function () {
        Route::get('/fetch', [NotificationController::class, 'fetch'])->name('fetch');
        Route::post('/read/{id}', [NotificationController::class, 'markAsRead'])->name('markAsRead');
        Route::post('/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('markAllAsRead');
    });
});


Route::middleware(['auth:web', 'role:Admin|IMSD'])->prefix('RegionalOffices/IMSD')->group(function () {

    // Index page — list all DV requests for IMSD
    Route::get('/index', [DvRequestProcessingController::class, 'index'])
    ->name('RegionalOffices.IMSD.index');
    Route::get('/dvrequests/fetchimsdRecent', [DvRequestProcessingController::class, 'fetchimsdRecent'])
    ->name('RegionalOffices.IMSD.dvrequests.fetchimsdRecent');
   Route::get('/RegionalOffices/IMSD/fetchAll', [DvRequestProcessingController::class, 'fetchAll'])
    ->name('RegionalOffices.IMSD.fetchAll');
    Route::get('/dvrequests/{id}', [DvRequestProcessingController::class, 'show'])
    ->name('RegionalOffices.IMSD.verifydv');
     Route::post('/dvrequests/{id}/receive', [DvRequestProcessingController::class, 'receive'])
    ->name('IMSD.dvrequests.receive');
    Route::post('/dvrequests/{id}/start-processing', 
    [DvRequestProcessingController::class, 'startProcessing'])->name('IMSD.dvrequests.startProcessing');

    Route::post('/dvrequests/{id}/verify', [DvRequestProcessingController::class, 'verify'])
    ->name('IMSD.dvrequests.verify');
    Route::post('/dvrequests/{id}/reject', [DvRequestProcessingController::class, 'reject'])
    ->name('IMSD.dvrequests.reject');
    Route::get('/history', [DvRequestProcessingController::class, 'history'])
    ->name('history');
    Route::get('/RegionalOffices/IMSD/load-form/{id}',[DvRequestProcessingController::class, 'loadForm'])
    ->name('RegionalOffices.IMSD.loadForm');
    Route::get('/redirect-to-first', [DvRequestProcessingController::class, 'redirectToFirstPending'])
    ->name('RegionalOffices.IMSD.redirectFirst');
    Route::get('/dvrequests/{id}/attachments/edit', [DvRequestProcessingController::class, 'editAttachments'])
    ->name('dvRequest.editAttachments');
    Route::put('/dvrequests/{id}/attachments', [DvRequestProcessingController::class, 'updateAttachments'])
    ->name('dvRequest.updateAttachments');
    Route::post('/dvrequests/{id}/refresh-lock', [DvRequestProcessingController::class, 'refreshLock'])
    ->name('dvrequests.refreshLock');
    Route::post('/dvrequests/{id}/release-lock', [DvRequestProcessingController::class, 'releaseLock'])
    ->name('dvrequests.releaseLock');
    Route::get('/RegionalOffices/IMSD/dvrequests/{id}/tupad-attachments/{modality}', 
    [DvRequestProcessingController::class, 'getTupadAttachmentsByModality'])
    ->name('RegionalOffices.IMSD.dvrequests.tupadAttachments');
});


Route::middleware(['auth:web', 'role:Admin|Incoming-GIP'])->prefix('Incoming/GIP')->group(function () {
     Route::get('/index', [GIPDvRequestProcessingController::class, 'index'])
    ->middleware('permission:gip.view')
    ->name('Incoming.GIP.index');
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
    Route::post('/dvrequests/{id}/receive', [GIPDvRequestProcessingController::class, 'receive'])
    ->middleware('permission:gip.approve')
    ->name('Incoming.GIP.dvrequests.receive');
     Route::post('/dvrequests/{id}/start-processing', [GIPDvRequestProcessingController::class, 'startProcessing'])
     ->middleware('permission:gip.approve')
    ->name('Incoming.GIP.dvrequests.startProcessing');
    Route::get('/history', [GIPDvRequestProcessingController::class, 'history'])
    ->middleware('permission:gip.view')
    ->name('dvrequests.giphistory');
    Route::get('/fetch-all', [GIPDvRequestProcessingController::class, 'fetchAll'])
    ->middleware('permission:gip.view')
    ->name('Incoming.GIP.fetchAll');
    Route::post('/dvrequests/{id}/verify', [GIPDvRequestProcessingController::class, 'verify'])
    ->middleware('permission:gip.approve')
    ->name('Incoming.GIP.dvrequests.verify');
    Route::post('/dvrequests/{id}/reject', [GIPDvRequestProcessingController::class, 'reject'])
    ->name('Incoming.GIP.dvrequests.reject');
    Route::put('incoming/gip/dvrequests/{id}/attachments', [GIPDvRequestProcessingController::class, 'updateAttachments'])
    ->name('Incoming.GIP.updateAttachments');
    Route::post('/dvrequests/{id}/refresh-lock', [GIPDvRequestProcessingController::class, 'refreshLock'])
    ->name('Incoming.GIP.dvrequests.refreshLock');
    Route::post('/dvrequests/{id}/release-lock', [GIPDvRequestProcessingController::class, 'releaseLock'])
    ->name('Incoming.GIP.dvrequests.releaseLock');

});

Route::middleware(['auth:web', 'role:Admin|Incoming-TUPAD'])->prefix('Incoming/TUPAD')->group(function () {
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
       Route::post('/dvrequests/{id}/receive', [TupadDvRequestProcessingController::class, 'receive'])
    ->middleware('permission:tupad.approve')
    ->name('Incoming.TUPAD.dvrequests.receive');

    Route::post('/dvrequests/{id}/start-processing', [TupadDvRequestProcessingController::class, 'startProcessing'])
    ->middleware('permission:tupad.approve')
    ->name('dvrequests.startProcessing');
    Route::get('/history', [TupadDvRequestProcessingController::class, 'history'])
    ->middleware('permission:tupad.view')
    ->name('dvrequests.tupadhistory');
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
  Route::put('/dvrequests/{id}/attachments', [TupadDvRequestProcessingController::class, 'updateAttachments'])
        ->name('Incoming.TUPAD.dvrequests.updateAttachments');
       Route::post('/dvrequests/{id}/refresh-lock', [TupadDvRequestProcessingController::class, 'refreshLock'])
    ->name('Incoming.TUPAD.dvrequests.refreshLock');

    Route::post('/dvrequests/{id}/release-lock', [TupadDvRequestProcessingController::class, 'releaseLock'])
    ->name('Incoming.TUPAD.dvrequests.releaseLock');

  
});
Route::middleware(['auth:web', 'role:Admin|Incoming-TEV'])->prefix('Incoming/TEV')->group(function () {
    Route::get('/index', [TEVDvRequestProcessingController::class, 'index'])
    ->name('Incoming.TEV.index');
    Route::get('/dvrequests/verify', [TEVDvRequestProcessingController::class, 'redirectToFirstPending'])
    ->name('Incoming.TEV.verifytevdv');
    Route::get('/dvrequests/fetchtevRecent', [TEVDvRequestProcessingController::class, 'fetchtevRecent'])
    ->name('Incoming.TEV.dvrequests.fetchtevRecent');
    Route::get('/dvrequests/{id}', [TEVDvRequestProcessingController::class, 'show']) 
    ->name('Incoming.TEV.dvrequests.withid');
       Route::post('/dvrequests/{id}/receive', [TEVDvRequestProcessingController::class, 'receive'])
    ->name('Incoming.TEV.dvrequests.receive');
    Route::post('/dvrequests/{id}/start-processing', [TEVDvRequestProcessingController::class, 'startProcessing'])
    ->name('dvrequests.startProcessing');
    Route::get('/history', [TEVDvRequestProcessingController::class, 'history'])
    ->name('dvrequests.tevhistory');
    Route::get('/fetch-all', [TEVDvRequestProcessingController::class, 'fetchAll'])
    ->name('Incoming.TEV.fetchAll');
    Route::post('/dvrequests/{id}/verify', [TEVDvRequestProcessingController::class, 'verify'])
    ->name('Incoming.TEV.dvrequests.verify');
    Route::post('/dvrequests/{id}/reject', [TEVDvRequestProcessingController::class, 'reject'])
    ->name('Incoming.TEV.dvrequests.reject');
    Route::get('/dvrequests/{id}/fetch', [TEVDvRequestProcessingController::class, 'fetch'])
    ->name('Incoming.TEV.dvrequests.fetch');
    Route::post('/dvrequests/{id}/refresh-lock', [TEVDvRequestProcessingController::class, 'refreshLock'])
    ->name('Incoming.TEV.dvrequests.refreshLock');
    Route::post('/dvrequests/{id}/release-lock', [TEVDvRequestProcessingController::class, 'releaseLock'])
    ->name('Incoming.TEV.dvrequests.releaseLock');

  
});
Route::middleware(['auth:web', 'role:Admin|Budget_Officer'])->prefix('/BudgetOfficer')->name('BudgetOfficer.')->group(function () {
    
    // ====================================
    // DV REQUESTS ROUTES
    // ====================================
    
    Route::get('/dvrequests', [ObligateDVORSController::class, 'index'])
        ->middleware('permission:budget.view')
        ->name('dvrequests.index');
        
    Route::get('/dvrequests/fetch', [ObligateDVORSController::class, 'fetchDvRequests'])
        ->middleware('permission:budget.view')
        ->name('dvrequests.fetch');
        
    Route::get('/dvrequests/recent', [ObligateDVORSController::class, 'fetchRecent'])
        ->middleware('permission:budget.view')
        ->name('dvrequests.recent');
        
    Route::get('/dvrequests/verify', [ObligateDVORSController::class, 'redirectToFirstPending'])
        ->middleware('permission:budget.view')
        ->name('dvrequests.verifyors');
        
    Route::get('/history', [ObligateDVORSController::class, 'history'])
        ->middleware('permission:budget.view')
        ->name('dvrequests.loghistory');
        
    // View specific DV request (this should be LAST among GET routes)
    Route::get('/dvrequests/{id}', [ObligateDVORSController::class, 'show'])
        ->middleware('permission:budget.view')
        ->name('dvrequests.withid');
    
    // ====================================
    // DV REQUEST ACTIONS (POST/PUT)
    // ====================================
    
    Route::post('/dvrequests/{id}/receive', [ObligateDVORSController::class, 'receive'])
        ->middleware('permission:budget.edit')
        ->name('dvrequests.receive');
        
    Route::post('/dvrequests/{id}/start-processing', [ObligateDVORSController::class, 'startProcessing'])
        ->middleware('permission:budget.edit')
        ->name('dvrequests.startProcessing');
        
    Route::put('/dvrequests/{id}/update-amount', [ObligateDVORSController::class, 'updateAmount'])
        ->middleware('permission:budget.edit')
        ->name('dvrequests.updateAmount');
        
    Route::post('/dvrequests/{id}/obligate', [ObligateDVORSController::class, 'store'])
        ->middleware('permission:budget.approve')
        ->name('dvrequests.store');
        
    Route::post('/dvrequests/{id}/onhold', [ObligateDVORSController::class, 'onhold'])
        ->middleware('permission:budget.onhold')
        ->name('dvrequests.onhold');
        
    Route::post('/dvrequests/{id}/revise', [ObligateDVORSController::class, 'revise'])
        ->middleware('permission:budget.edit')
        ->name('dvrequests.revise');
    
    // ====================================
    // LOCK MANAGEMENT
    // ====================================
    
    Route::post('/dvrequests/{id}/refresh-lock', [ObligateDVORSController::class, 'refreshLock'])
        ->middleware('permission:budget.view')
        ->name('dvrequests.refreshLock');
        
    Route::post('/dvrequests/{id}/release-lock', [ObligateDVORSController::class, 'releaseLock'])
        ->middleware('permission:budget.view')
        ->name('dvrequests.releaseLock');
    
    // ====================================
    // ADL CODE ROUTES (NEW)
    // ====================================
    
    // Store new ADL Code
    Route::post('/adl/store', [ObligateDVORSController::class, 'storeADLCode'])
        ->middleware('permission:budget.edit')
        ->name('adl.store');
    
    // Check if ADL Code exists (GET method is better for checking)
    Route::get('/adl/check', [ObligateDVORSController::class, 'checkADLCode'])
        ->middleware('permission:budget.view')
        ->name('adl.check');
});
                    
Route::middleware(['auth:web','role:Admin|Outgoing'])->prefix('/Outgoing')->name('Outgoing.')->group(function () {
        Route::get('/dvrequests', [OutgoingDvController::class, 'index'])
        ->middleware('permission:outgoing.view')
        ->name('dvrequests.index');
        Route::get('/dvrequests/fetch', [OutgoingDvController::class, 'fetchDvRequests'])
        ->middleware('permission:outgoing.view')
        ->name('dvrequests.fetch');
        Route::get('/dvrequests/recent', [OutgoingDvController::class, 'fetchDvRecent'])
        ->middleware('permission:outgoing.view')
        ->name('dvrequests.recent');
        Route::get('/dvrequests/{id}/preview', [OutgoingDvController::class, 'previewDv'])->name('dvrequests.preview');
        Route::get('/dvrequests/{id}/print', [OutgoingDvController::class, 'printOfficialDv'])->name('dvrequests.print');
        Route::get('/dvrequests/{id}/generate-pdf', [OutgoingDvController::class, 'generatePdf'])->name('dvrequests.generate-pdf');
        Route::get('/dvrequests/dvloghistory', [OutgoingDvController::class, 'history'])
        ->middleware('permission:outgoing.view')
        ->name('dvrequests.dvloghistory');
        Route::get('/dvrequests/verify', [OutgoingDvController::class, 'redirectToFirstPending'])
        ->middleware('permission:outgoing.view')
        ->name('dvrequests.verifydv');
        Route::get('/dvrequests/{id}', [OutgoingDvController::class, 'show'])
        ->middleware('permission:outgoing.view')
        ->name('dvrequests.withid');
        Route::post('/dvrequests/{id}/receive', [OutgoingDvController::class, 'receive'])
        ->middleware('permission:outgoing.view')
        ->name('dvrequests.receive');
         Route::post('/dvrequests/{id}/start-processing', [OutgoingDvController::class, 'startProcessing'])
        ->middleware('permission:outgoing.view')
        ->name('dvrequests.startProcessing');
        Route::post('/dvrequests/{id}/finalize', [OutgoingDvController::class, 'finalize'])
         ->middleware('permission:outgoing.approve')
         ->name('dvrequests.finalize');
        Route::post('/dvrequests/{id}/reject', [OutgoingDvController::class, 'reject'])
        ->middleware('permission:outgoing.reject')
        ->name('dvrequests.reject');
         Route::put('/dvrequests/{id}/attachments', [OutgoingDvController::class, 'updateAttachments'])
        ->name('dvrequests.updateAttachments');
        Route::post('/dvrequests/{id}/refresh-lock', [OutgoingDvController::class, 'refreshLock'])
        ->name('dvrequests.refreshLock');
        Route::post('/dvrequests/{id}/release-lock', [OutgoingDvController::class, 'releaseLock'])
        ->name('dvrequests.releaseLock');
    });

//Accountant III
//Accountant III
Route::middleware(['auth:web','role:Admin|Accounting_III'])->prefix('Accounting')->name('Accounting.')->group(function () {
    // Fix the history route - remove duplicate prefix
    Route::get('/history', [RANCAController::class, 'history'])->name('history');
    
    // Your existing routes
    Route::get('/dvrequests', [RANCAController::class, 'index'])
    ->name('dvrequests.index');
    
    Route::get('/dvrequests/fetch', [RANCAController::class, 'fetchDvRequests'])
    ->name('dvrequests.fetch');
    Route::get('/dvrequests/recent', [RANCAController::class, 'fetchDvRecent'])
    ->name('dvrequests.recent');

    Route::get('/dvrequests/{id}', [RANCAController::class, 'show'])
    ->name('dvrequests.show');
    Route::get('/redirect-first-pending', [RANCAController::class, 'redirectToFirstPending'])
    ->name('dvrequest.redirectFirstPending');
    Route::post('/dvrequests/{id}/receive', [RANCAController::class, 'receive'])
    ->name('dvrequests.receive');
    Route::post('/dvrequests/{id}/start-processing', [RANCAController::class, 'startProcessing'])
    ->name('dvrequests.start');
    Route::put('/dvrequests/{id}/update-amount', [RANCAController::class, 'updateAmount'])
    ->name('dvrequests.updateAmount');
    Route::post('/dvrequests/{id}/validate', [RANCAController::class, 'validateDV'])
    ->name('dvrequests.validate');

    Route::post('/dvrequests/{id}/release-lock', [RANCAController::class, 'releaseLock'])   
    ->name('dvrequests.releaseLock');
    Route::post('/dvrequests/{id}/refresh-lock', [RANCAController::class, 'refreshLock'])
    ->name('dvrequests.refreshLock');
});