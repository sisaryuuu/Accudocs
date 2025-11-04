<?php
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\QRCode\DOLEQRController;
use App\Http\Controllers\DVORS\VoucherController;
use App\Http\Controllers\FocalPerson\DvRequestController;
use App\Http\Controllers\BudgetOfficer\ObligateDVORSController;
use App\Http\Controllers\BudgetOfficer\ORSRequestAgingController;
use App\Http\Controllers\BudgetOfficer\ADLController;
use App\Http\Controllers\Outgoing\OutgoingDvController;
use App\Http\Controllers\Outgoing\OutgoingRequestAgingController;
use App\Http\Controllers\AccountingIII\RANCAController;
use App\Http\Controllers\AccountingIII\RANCARequestAgingController;
use App\Http\Controllers\IMSD\DvRequestAgingController;
use App\Http\Controllers\Incoming\GIPRequestAgingController;
use App\Http\Controllers\IMSD\DvRequestProcessingController;
use App\Http\Controllers\Incoming\GIPDvRequestProcessingController;
use App\Http\Controllers\Incoming\TEVDvRequestProcessingController;
use App\Http\Controllers\Incoming\TEVRequestAgingController;
use App\Http\Controllers\Incoming\TupadDvRequestProcessingController;
use App\Http\Controllers\Incoming\TUPADRequestAgingController;
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
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use App\Http\Requests\FocalEmailVerificationRequest;
use App\Http\Requests\AccountingUnitEmailVerificationRequest;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\AnalyticsController;



// Dashboard Routes
Route::get('/admin/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');
Route::get('/admin/dashboard/fetch-data', [DashboardController::class, 'fetchData'])->name('admin.dashboard.fetch');
Route::get('/admin/dashboard/data', [DashboardController::class, 'fetchData'])->name('admin.dashboard.data');
Route::get('/admin/dashboard/filter', [DashboardController::class, 'filter'])->name('admin.dashboard.filter');
Route::post('/admin/dashboard/refresh', [DashboardController::class, 'refresh'])->name('admin.dashboard.refresh'); // Add this line

Route::get('/test-chart', function () {
    return view('test-chart');
});


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


Route::middleware(['auth:web', 'verified'])->group(function (){
    Route::get('profile', [AccountingUnitProfileController::class, 'show'])->name('web.profile.show');
    Route::get('profile/edit', [AccountingUnitProfileController::class, 'edit'])->name('web.profile.edit');
    Route::post('profile/update', [AccountingUnitProfileController::class, 'update'])->name('web.profile.update');
});



Route::middleware(['auth:web','verified', 'role:Admin|Accounting_III'])->group(function () {
    Route::get('/Admin/dashboard', [DashboardController::class, 'index'])
        ->name('Admin.dashboard');

    Route::resource('admin/accountingunits', AccountingUnitController::class)
        ->names('admin.accountingunits');
});
Route::middleware(['auth:web', 'verified', 'role:Admin'])->group(function () {
    
    // Accounting Units routes
    Route::get('admin/accountingunits/{accountingunit}', [AccountingUnitController::class, 'show'])
        ->name('admin.accountingunits.show');
    Route::post('admin/accountingunits/bulk-action', [AccountingUnitController::class, 'bulkAction'])
        ->name('admin.accountingunits.bulk-action');
    Route::get('admin/accountingunits/search', [AccountingUnitController::class, 'search'])
        ->name('admin.accountingunits.search');
    Route::put('/admin/accountingunits/{accountingunit}', [AccountingUnitController::class, 'update'])
        ->name('admin.accountingunits.update');
    
    // ✅ Add resend verification route
    Route::post('/admin/accountingunits/{accountingunit}/resend-verification', 
        [AccountingUnitController::class, 'resendVerification'])
        ->name('admin.accountingunits.resend-verification');

    // ✅ Roles routes
    Route::resource('admin/roles', RoleController::class)
        ->names('admin.roles');
    
    // ✅ Permissions routes (ADD THESE)
    Route::get('admin/permissions', [RoleController::class, 'permissionsIndex'])
        ->name('admin.permissions.index');
    Route::get('admin/permissions/create', [RoleController::class, 'permissionsCreate'])
        ->name('admin.permissions.create');
    Route::post('admin/permissions', [RoleController::class, 'permissionsStore'])
        ->name('admin.permissions.store');
    Route::get('admin/permissions/{permission}/edit', [RoleController::class, 'permissionsEdit'])
        ->name('admin.permissions.edit');
    Route::put('admin/permissions/{permission}', [RoleController::class, 'permissionsUpdate'])
        ->name('admin.permissions.update');
    Route::delete('admin/permissions/{permission}', [RoleController::class, 'permissionsDestroy'])
        ->name('admin.permissions.destroy');
    
    // Settings routes
    Route::get('admin/settings', [SettingsController::class, 'edit'])
        ->name('admin.settings.edit');
    Route::post('admin/settings', [SettingsController::class, 'update'])
        ->name('admin.settings.update');
});

Route::middleware(['auth:web'])->group(function () {
    Route::get('/accountingunit/email/verify', function () {
        return view('auth.accunitverifyemail');
    })->name('accountingunit.verification.notice');

    Route::post('/accountingunit/email/verification-notification', function (Request $request) {
        $request->user('web')->sendEmailVerificationNotification();
        return back()->with('success', 'Verification link sent!');
    })->middleware('throttle:6,1')->name('accountingunit.verification.send');
});

Route::get('/accountingunit/email/verify/{id}/{hash}', function (AccountingUnitEmailVerificationRequest $request) {
    $request->fulfill();
    return redirect()->route('login')
        ->with('success', 'Email verified successfully!');
})->middleware(['auth:web'])->name('accountingunit.verification.verify');



Route::get('/focal/email/verify', function () {
    return view('auth.focalverifyemail');
})->middleware(['auth:focal'])->name('verification.notice');

Route::get('/focal/email/verify/{id}/{hash}', function (FocalEmailVerificationRequest $request) {
    $request->fulfill();
    
    return redirect()->route('FocalPerson.dvrequest.index')
        ->with('success', 'Email verified successfully!');
})->middleware(['auth:focal'])->name('focal.verification.verify');

Route::post('/focal/email/verification-notification', function (Request $request) {
    $request->user('focal')->sendEmailVerificationNotification();
    
    return back()->with('success', 'Verification link sent!');
})->middleware(['auth:focal', 'throttle:6,1'])->name('focal.verification.send');


// Protected routes that require email verification
Route::middleware(['auth:focal', 'verified', 'role:Focal_Person'])->group(function () {
    // History routes
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
      
        Route::get('/{dv}/qr', [DvRequestController::class, 'showQR'])
            ->middleware('permission:focalperson.view')
            ->name('qr');
        
        Route::post('/{dvRequest}/confirm-receipt', [DvRequestController::class, 'confirmReceipt'])
            ->middleware('permission:focalperson.edit')
            ->name('confirmReceipt');

        Route::delete('/{dvRequest}', [DvRequestController::class, 'destroy'])
            ->middleware('permission:focalperson.delete')
            ->name('destroy');

        // Javascript Routes
        Route::get('/{dvRequest}/latest-step', [DvRequestController::class, 'latestStep'])
            ->name('latestStep');
        Route::get('/refresh', [DvRequestController::class, 'refresh'])
            ->name('refresh');
        Route::post('/{dvRequest}/auto-complete', [DvRequestController::class, 'autoComplete'])
            ->name('autoComplete');
        Route::post('/move-rejected-to-history', [DvRequestController::class, 'moveRejectedToHistory'])
            ->name('moveRejectedToHistory');
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

Route::get('/api/check-auth', function() {
    return response()->json([
        'authenticated' => auth()->check(),
        'user' => auth()->check() ? [
            'name' => auth()->user()->name,
            'roles' => auth()->user()->getRoleNames()
        ] : null
    ]);
});

Route::prefix('api')->group(function () {
    // Statistics endpoints
    Route::get('/cashier/stats', [DOLEQRController::class, 'getCashierStats'])->name('cashier.stats');
    Route::get('/regional-director/stats', [DOLEQRController::class, 'getRegionalDirectorStats'])->name('regional.director.stats');
    
    // DV details endpoint
    Route::get('/dv/{id}/details', [DOLEQRController::class, 'getDvDetails'])->name('dv.details');
    
    // Trigger endpoints (for testing or manual updates)
    Route::post('/dv/{id}/status-update', [DOLEQRController::class, 'triggerStatusUpdate'])->name('dv.trigger.status');
    Route::post('/dv/{id}/new-added', [DOLEQRController::class, 'triggerNewDvAdded'])->name('dv.trigger.new');
    Route::post('/dv/{id}/removed', [DOLEQRController::class, 'triggerDvRemoved'])->name('dv.trigger.removed');
});

Route::get('/dv/qr/update-status/{token}', [DOLEQRController::class, 'qrStatusUpdateInterface'])
    ->name('dv.qr.interface');

Route::post('/dv/qr/update-status/{token}', [DOLEQRController::class, 'updateStatusViaQr'])
    ->name('dv.qr.update-status');

Route::get('/dv/verify/{reference}', [DOLEQRController::class, 'verifyDv'])
    ->name('dv.verify');

// Utility route to generate QR code for any DV
Route::get('/dv/{id}/generate-qr', [DOLEQRController::class, 'generateQrCode'])
    ->name('dv.generate-qr');

Route::get('/api/dv/{id}/qr-code', [DOLEQRController::class, 'getQrCode'])
    ->name('dv.get-qr');

Route::prefix('regional-director')->name('regional.director.')->middleware(['auth:web','verified', 'role:Regional_Director'])->group(function () {
    Route::get('/dashboard', [DOLEQRController::class, 'regionalDirectorDashboard'])->name('dashboard');
    Route::get('/scanner', [DOLEQRController::class, 'regionalDirectorScanner'])->name('scanner');
    Route::get('/scannable-dvs', [DOLEQRController::class, 'scannableDvs'])->name('scannable-dvs');
    Route::post('/bulk-receive', [DOLEQRController::class, 'bulkReceiveDVs'])->name('bulk-receive');
    Route::post('/bulk-sign-approve', [DOLEQRController::class, 'bulkSignApproveDVs'])->name('bulk-sign-approve'); // ✅ ADDED
    Route::get('/history', [DOLEQRController::class, 'regionalDirectorHistory'])->name('history');
    Route::get('/stats', [DOLEQRController::class, 'getRegionalDirectorStats'])->name('stats'); // ✅ ADDED if not exists
});

// Cashier Routes  
Route::prefix('cashier')->name('cashier.')->middleware(['auth:web', 'verified', 'role:Cashier'])->group(function () {
    Route::get('/dashboard', [DOLEQRController::class, 'cashierDashboard'])->name('dashboard');
    Route::get('/scanner', [DOLEQRController::class, 'cashierScanner'])->name('scanner');
    Route::get('/todays-transactions', [DOLEQRController::class, 'getTodaysTransactions'])->name('todays-transactions');
    Route::get('/pending-payments', [DOLEQRController::class, 'cashierDashboard'])->name('pending-payments');
    Route::post('/bulk-receive', [DOLEQRController::class, 'cashierBulkReceive'])->name('bulk-receive');
    Route::post('/bulk-release', [DOLEQRController::class, 'cashierBulkRelease'])->name('bulk-release'); // ✅ ADDED
    Route::get('/history', [DOLEQRController::class, 'cashierHistory'])->name('history');
    Route::get('/stats', [DOLEQRController::class, 'getCashierStats'])->name('stats'); // ✅ ADDED if not exists
});

Route::get('/qr/scanner', function () {
    return view('qr.scanner');
})->name('qr.scanner');

Route::get('/dv/verify/{reference}', [DvRequestController::class, 'verifyQR'])->name('dv.verify');

Route::post('/dv/verify/manual', [DvRequestController::class, 'verifyManual'])->name('dv.verify.manual');

Route::middleware(['auth:web', 'verified', 'role:Admin|IMSD'])->prefix('RegionalOffices/IMSD')->group(function () {

    // Index page — list all DV requests for IMSD
    Route::get('/index', [DvRequestProcessingController::class, 'index'])
    ->name('RegionalOffices.IMSD.index');
    
    Route::get('/dvrequests/fetchimsdRecent', [DvRequestProcessingController::class, 'fetchimsdRecent'])
    ->name('RegionalOffices.IMSD.dvrequests.fetchimsdRecent');
   
    Route::get('/fetchAll', [DvRequestProcessingController::class, 'fetchAll']) // Remove duplicate prefix
    ->name('RegionalOffices.IMSD.fetchAll');
    
    Route::get('/dvrequests/{id}', [DvRequestProcessingController::class, 'show'])
    ->name('RegionalOffices.IMSD.verifydv');
    
    Route::post('/dvrequests/{id}/receive', [DvRequestProcessingController::class, 'receive'])
    ->name('IMSD.dvrequests.receive');
    
    Route::post('/dvrequests/{id}/start-processing', [DvRequestProcessingController::class, 'startProcessing'])
    ->name('IMSD.dvrequests.startProcessing');
    
    Route::post('/dvrequests/{id}/verify', [DvRequestProcessingController::class, 'verify'])
    ->name('IMSD.dvrequests.verify');
    
    Route::post('/dvrequests/{id}/reject', [DvRequestProcessingController::class, 'reject'])
    ->name('IMSD.dvrequests.reject');
   
    Route::get('/history', [DvRequestProcessingController::class, 'verificationHistory'])
    ->name('RegionalOffices.IMSD.history');
    
    Route::get('/load-form/{id}', [DvRequestProcessingController::class, 'loadForm']) // Remove duplicate prefix
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
    
    Route::get('/dvrequests/{id}/tupad-attachments/{modality}', [DvRequestProcessingController::class, 'getTupadAttachmentsByModality']) // Remove duplicate prefix
    ->name('RegionalOffices.IMSD.dvrequests.tupadAttachments');
    
    // Aging routes
    Route::get('/aging-documents', [DvRequestAgingController::class, 'getAgingDocuments'])
    ->name('IMSD.aging.documents');
    
    Route::get('/aging-statistics', [DvRequestAgingController::class, 'getAgingStatistics'])
    ->name('IMSD.aging.statistics');
    
    Route::post('/send-aging-alerts', [DvRequestAgingController::class, 'sendAgingAlerts'])
    ->name('IMSD.aging.send');

    Route::post('/aging-alerts/{id}/acknowledge', [DvRequestAgingController::class, 'acknowledgeAlert'])
    ->name('IMSD.aging.acknowledge');
});


Route::middleware(['auth:web', 'verified', 'role:Admin|Incoming-GIP'])->prefix('Incoming/GIP')->group(function () {
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

     Route::get('/aging/documents', [GIPRequestAgingController::class, 'getAgingDocuments'])
        ->middleware('permission:gip.view')
        ->name('GIP.aging.documents');
    
    Route::get('/aging/statistics', [GIPRequestAgingController::class, 'getAgingStatistics'])
        ->middleware('permission:gip.view')
        ->name('GIP.aging.statistics');
    
    Route::post('/aging-alerts/{alertId}/acknowledge', [GIPRequestAgingController::class, 'acknowledgeAlert'])
        ->middleware('permission:gip.approve')
        ->name('GIP.aging.acknowledge');
    
    // Optional: Route to send aging alerts manually (for testing)
    Route::post('/aging/send-alerts', [GIPRequestAgingController::class, 'sendAgingAlerts'])
        ->middleware('permission:gip.approve')
        ->name('GIP.aging.sendAlerts');

});

Route::middleware(['auth:web', 'verified', 'role:Admin|Incoming-TUPAD'])->prefix('Incoming/TUPAD')->group(function () {
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
    // Aging routes
  Route::get('/aging-documents', [TUPADRequestAgingController::class, 'getAgingDocuments'])
    ->name('TUPAD.aging.documents');
Route::get('/aging-statistics', [TUPADRequestAgingController::class, 'getAgingStatistics'])
    ->name('TUPAD.aging.statistics');
Route::post('/aging-alerts/{alert}/acknowledge', [TUPADRequestAgingController::class, 'acknowledgeAlert'])
    ->name('TUPAD.aging.acknowledge');
});
Route::middleware(['auth:web', 'verified', 'role:Admin|Incoming-TEV'])->prefix('Incoming/TEV')->group(function () {
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

      // ============ TEV AGING ROUTES ============
    Route::get('/aging/documents', [TEVRequestAgingController::class, 'getAgingDocuments'])
        ->middleware('permission:tev.view')
        ->name('TEV.aging.documents');
    
    Route::get('/aging/statistics', [TEVRequestAgingController::class, 'getAgingStatistics'])
        ->middleware('permission:tev.view')
        ->name('TEV.aging.statistics');
    

});
Route::middleware(['auth:web', 'verified', 'role:Admin|Budget_Officer'])->prefix('/BudgetOfficer')->name('BudgetOfficer.')->group(function () {
    
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
        
    
    Route::get('/dv-requests/{id}/preview-ors', [ObligateDVORSController::class, 'previewOrs'])
    ->name('dvrequests.previewOrs');

    Route::post('/dvrequests/{id}/reject', [ObligateDVORSController::class, 'reject'])
    ->name('dvrequests.reject');

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

         Route::prefix('ors-aging')->group(function () {
        // Get ORS aging documents
        Route::get('/documents', [ORSRequestAgingController::class, 'getAgingDocuments'])
            ->middleware('permission:budget.view')
            ->name('ors-aging.documents');
            
        // Get ORS aging statistics
        Route::get('/statistics', [ORSRequestAgingController::class, 'getAgingStatistics'])
            ->middleware('permission:budget.view')
            ->name('ors-aging.statistics');
            
        // Get unacknowledged alerts count
        Route::get('/unacknowledged-count', [ORSRequestAgingController::class, 'getUnacknowledgedCount'])
            ->middleware('permission:budget.view')
            ->name('ors-aging.unacknowledged-count');
            
        // Send aging alerts (admin/automated)
        Route::post('/send-alerts', [ORSRequestAgingController::class, 'sendAgingAlerts'])
            ->middleware('permission:budget.edit')
            ->name('ors-aging.send-alerts');
            
        // Get ORS aging history
        Route::get('/history', [ORSRequestAgingController::class, 'getAgingHistory'])
            ->middleware('permission:budget.view')
            ->name('ors-aging.history');
    });
});

Route::middleware(['auth:web', 'verified', 'role:Admin|Outgoing'])->prefix('/Outgoing')->name('Outgoing.')->group(function () {
    // Main DV Requests Routes
    Route::get('/dvrequests', [OutgoingDvController::class, 'index'])
        ->middleware('permission:outgoing.view')
        ->name('dvrequests.index');
    
    Route::get('/dvrequests/fetch', [OutgoingDvController::class, 'fetchDvRequests'])
        ->middleware('permission:outgoing.view')
        ->name('dvrequests.fetch');
    
    Route::get('/dvrequests/recent', [OutgoingDvController::class, 'fetchDvRecent'])
        ->middleware('permission:outgoing.view')
        ->name('dvrequests.recent');
    
    Route::get('/dvrequests/{id}/preview', [OutgoingDvController::class, 'previewDv'])
        ->name('dvrequests.preview');
    
    Route::get('/dvrequests/{id}/print', [OutgoingDvController::class, 'printOfficialDv'])
        ->name('dvrequests.print');
    
    Route::get('/dvrequests/{id}/generate-pdf', [OutgoingDvController::class, 'generatePdf'])
        ->name('dvrequests.generate-pdf');
    
    Route::get('/dvrequests/history-preview/{id}', [OutgoingDvController::class, 'previewFromHistory'])
        ->name('dvrequests.previewFromHistory')
        ->middleware('permission:outgoing.view');
    
    Route::get('/dvrequests/dvloghistory', [OutgoingDvController::class, 'history'])
        ->middleware('permission:outgoing.view')
        ->name('dvrequests.dvloghistory');
    
    Route::get('/dvrequests/verify', [OutgoingDvController::class, 'redirectToFirstPending'])
        ->middleware('permission:outgoing.view')
        ->name('dvrequests.verifydv');
    
    Route::get('/dvrequests/{id}', [OutgoingDvController::class, 'show'])
        ->middleware('permission:outgoing.view')
        ->name('dvrequests.withid');
    
    // DV Request Actions
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
    
    // ============ OUTGOING AGING ROUTES ============
    Route::prefix('aging')->group(function () {
        Route::get('/documents', [OutgoingRequestAgingController::class, 'getAgingDocuments'])
            ->middleware('permission:outgoing.view')
            ->name('aging.documents');
        
        Route::get('/statistics', [OutgoingRequestAgingController::class, 'getAgingStatistics'])
            ->middleware('permission:outgoing.view')
            ->name('aging.statistics');
        
        Route::post('/alerts/send', [OutgoingRequestAgingController::class, 'sendAgingAlerts'])
            ->middleware('permission:outgoing.manage')
            ->name('aging.alerts.send');
    });
});


//Accountant III
Route::middleware(['auth:web', 'verified', 'role:Admin|Accounting_III'])->prefix('Accounting')->name('Accounting.')->group(function () {
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
    Route::post('/dvrequests/{id}/reject', [RANCAController::class, 'reject'])
    ->name('dvrequests.reject');
    Route::post('/dvrequests/{id}/release-lock', [RANCAController::class, 'releaseLock'])   
    ->name('dvrequests.releaseLock');
    Route::post('/dvrequests/{id}/refresh-lock', [RANCAController::class, 'refreshLock'])
    ->name('dvrequests.refreshLock');

    // ============ RANCA AGING ROUTES ============
    Route::get('/aging/documents', [RANCARequestAgingController::class, 'getAgingDocuments'])
    ->name('aging.documents');
    
    Route::get('/aging/statistics', [RANCARequestAgingController::class, 'getAgingStatistics'])
    ->name('aging.statistics');
    
    Route::get('/aging/report', [RANCARequestAgingController::class, 'getAgingReport'])
    ->name('aging.report');
    
    Route::post('/aging/alerts/send', [RANCARequestAgingController::class, 'sendAgingAlerts'])
    ->name('aging.alerts.send');
    
    Route::post('/aging/alerts/cleanup', [RANCARequestAgingController::class, 'cleanupOldAlerts'])
    ->name('aging.alerts.cleanup');
});