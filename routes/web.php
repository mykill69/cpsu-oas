<?php

use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\QA\DriveController;
use App\Http\Controllers\QA\SchoolYearController;
use App\Http\Controllers\SchoolYearSwitchController;
use App\Http\Controllers\BinderController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\QA\AreaController;
use App\Http\Controllers\QA\CollegeController;
use App\Http\Controllers\QA\DashboardController;
use App\Http\Controllers\QA\FilesController;
use App\Http\Controllers\QA\ShareController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\QA\ParameterController;
use App\Http\Controllers\QA\SurveyVisitController;
use App\Http\Controllers\QA\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:10,1');
    Route::get('/auth/google', [GoogleController::class, 'redirect'])->name('auth.google');
    Route::get('/auth/google/callback', [GoogleController::class, 'callback'])->name('auth.google.callback');
});

// GET too, so a bookmarked or typed /logout signs out instead of erroring.
Route::match(['get', 'post'], '/logout', [LoginController::class, 'logout'])->name('logout');

// Accreditors: no account. Link token + one-time code, then read-only access for this browser session.
Route::prefix('review/{token}')->name('review.')->where(['token' => '[A-Za-z0-9]{48}'])->group(function () {
    Route::get('/', [ReviewController::class, 'show'])->name('show');
    Route::post('/verify', [ReviewController::class, 'verify'])->middleware('throttle:10,1')->name('verify');
    Route::post('/resend', [ReviewController::class, 'resend'])->middleware('throttle:4,10')->name('resend');
    Route::post('/leave', [ReviewController::class, 'leave'])->name('leave');
    Route::get('/binder', [ReviewController::class, 'visit'])->name('visit');
    Route::get('/areas/{area}/{parameter?}', [ReviewController::class, 'node'])->name('node');
    Route::get('/folders/{folder}', [ReviewController::class, 'folder'])->name('folder');
    Route::get('/documents/{document}', [ReviewController::class, 'document'])->name('document');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function (Request $request) {
        /** @var \App\Models\User $user */
        $user = $request->user();

        return redirect()->route($user->isQa() ? 'qa.dashboard' : 'binder.index');
    })->name('dashboard');

    Route::post('/school-year', SchoolYearSwitchController::class)->name('school-year.switch');

    // QA office: builds survey visits, assigns colleges, manages accounts.
    Route::middleware('role:qa_director|administrator')->prefix('qa')->name('qa.')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        Route::resource('survey-visits', SurveyVisitController::class);
        Route::post('survey-visits/{survey_visit}/duplicate', [SurveyVisitController::class, 'duplicate'])->name('survey-visits.duplicate');
        Route::put('survey-visits/{survey_visit}/colleges', [SurveyVisitController::class, 'assignColleges'])->name('survey-visits.colleges');

        // Areas and parameters live inside a survey visit.
        Route::post('survey-visits/{survey_visit}/areas', [AreaController::class, 'store'])->name('areas.store');
        Route::put('areas/{area}', [AreaController::class, 'update'])->name('areas.update');
        Route::delete('areas/{area}', [AreaController::class, 'destroy'])->name('areas.destroy');
        Route::post('areas/{area}/move', [AreaController::class, 'move'])->name('areas.move');

        Route::post('areas/{area}/parameters', [ParameterController::class, 'store'])->name('parameters.store');
        Route::put('parameters/{parameter}', [ParameterController::class, 'update'])->name('parameters.update');
        Route::delete('parameters/{parameter}', [ParameterController::class, 'destroy'])->name('parameters.destroy');
        Route::post('parameters/{parameter}/move', [ParameterController::class, 'move'])->name('parameters.move');

        Route::resource('colleges', CollegeController::class)->except('show');
        Route::resource('users', UserController::class)->except('show');

        // Everything the colleges filed, by college, with file search.
        Route::get('files', [FilesController::class, 'index'])->name('files.index');
        Route::get('files/{college}', [FilesController::class, 'show'])->name('files.show');

        // Accreditor access to one college's binder for one visit.
        Route::get('shares', [ShareController::class, 'index'])->name('shares.index');
        Route::post('binder/{college}/visits/{survey_visit}/shares', [ShareController::class, 'store'])->name('shares.store');
        Route::post('shares/{share}/resend', [ShareController::class, 'resend'])->name('shares.resend');
        Route::post('shares/{share}/revoke', [ShareController::class, 'revoke'])->name('shares.revoke');

        // School years: each accreditation cycle is its own year; old years stay browsable.
        Route::get('school-years', [SchoolYearController::class, 'index'])->name('school-years.index');
        Route::post('school-years', [SchoolYearController::class, 'store'])->name('school-years.store');
        Route::post('school-years/{schoolYear}/current', [SchoolYearController::class, 'makeCurrent'])->name('school-years.current');

        // The system Google Drive (cpsu_accresys account) that stores every file.
        Route::get('drive', [DriveController::class, 'index'])->name('drive.index');
        Route::get('drive/connect', [DriveController::class, 'connect'])->name('drive.connect');
        Route::get('drive/callback', [DriveController::class, 'callback'])->name('drive.callback');
        Route::post('drive/sync', [DriveController::class, 'sync'])->name('drive.sync');
        Route::post('drive/disconnect', [DriveController::class, 'disconnect'])->name('drive.disconnect');
    });

    // Binders: QA reads, the college's in-charge writes. Access is checked per college in the controllers.
    Route::middleware('role:qa_director|administrator|campus_incharge')->group(function () {
        Route::get('/my-visits', [BinderController::class, 'index'])->middleware('role:campus_incharge')->name('binder.index');
        Route::get('/binder/{college}/visits/{survey_visit}', [BinderController::class, 'visit'])->name('binder.visit');
        Route::get('/binder/{college}/areas/{area}/{parameter?}', [BinderController::class, 'node'])->name('binder.node');
        Route::get('/folders/{folder}', [BinderController::class, 'folder'])->name('binder.folder');
        Route::post('/folders', [BinderController::class, 'storeFolder'])->name('folders.store');
        Route::patch('/folders/{folder}', [BinderController::class, 'renameFolder'])->name('folders.update');
        Route::delete('/folders/{folder}', [BinderController::class, 'destroyFolder'])->name('folders.destroy');

        Route::post('/folders/{folder}/documents', [DocumentController::class, 'store'])->name('documents.store');
        Route::get('/documents/{document}', [DocumentController::class, 'show'])->name('documents.show');
        Route::delete('/documents/{document}', [DocumentController::class, 'destroy'])->name('documents.destroy');
    });
});
