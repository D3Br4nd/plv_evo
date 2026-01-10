<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AdminProfileController;
use App\Http\Controllers\AdminMemberInvitationController;
use App\Http\Controllers\AdminMembershipController;
use App\Http\Controllers\MemberInvitationAcceptController;
use App\Http\Controllers\MemberCardController;
use App\Http\Controllers\MemberEventsController;
use App\Http\Controllers\MemberHomeController;
use App\Http\Controllers\MemberProfileController;
use App\Http\Controllers\MemberOnboardingController;
use App\Http\Controllers\MemberPasswordController;
use App\Http\Controllers\MemberPushSubscriptionController;
use App\Http\Controllers\MemberNotificationsController;
use App\Http\Controllers\MemberUuidController;
use App\Http\Controllers\PublicContentPageController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    if (auth()->check()) {
        $user = auth()->user();
        $isAdmin = in_array($user->role, ['super_admin', 'admin'], true);

        // Mobile: default to the PWA/mobile UI unless the user explicitly opted into the admin UI.
        $ua = strtolower((string) request()->userAgent());
        $isMobile = str_contains($ua, 'iphone')
            || str_contains($ua, 'ipad')
            || str_contains($ua, 'ipod')
            || str_contains($ua, 'android')
            || str_contains($ua, 'mobile')
            || str_contains($ua, 'tablet');
        $preferAdminUi = request()->session()->get('ui.prefer') === 'admin';

        if ($isMobile && ! $preferAdminUi) {
            return redirect($user->must_set_password ? '/me/onboarding' : '/me');
        }

        if ($isAdmin) return redirect('/admin/dashboard');

        return redirect($user->must_set_password ? '/me/onboarding' : '/me');
    }

    return Inertia::render('Welcome');
})->name('home');

Route::middleware('auth')->get('/ui/admin', function (Request $request) {
    $request->session()->put('ui.prefer', 'admin');
    return redirect('/admin/dashboard');
})->name('ui.admin');

Route::middleware('auth')->get('/ui/me', function (Request $request) {
    $request->session()->put('ui.prefer', 'me');
    return redirect('/me');
})->name('ui.me');

Route::middleware('auth')->get('/me/card', [MemberCardController::class, 'show'])->name('member.card');
Route::middleware('auth')->get('/me', [MemberHomeController::class, 'show'])->name('member.home');
Route::middleware('auth')->get('/me/uuid', [MemberUuidController::class, 'show'])->name('member.uuid');
Route::middleware('auth')->get('/me/events', [MemberEventsController::class, 'index'])->name('member.events');
Route::middleware('auth')->get('/me/events/{event}', [MemberEventsController::class, 'show'])->name('member.events.show');
Route::middleware('auth')->get('/me/profile', [MemberProfileController::class, 'show'])->name('member.profile');
Route::middleware('auth')->patch('/me/profile', [MemberProfileController::class, 'update'])->name('member.profile.update');
Route::middleware('auth')->post('/me/profile/avatar', [MemberProfileController::class, 'updateAvatar'])->name('member.profile.avatar');
Route::middleware('auth')->delete('/me/profile/avatar', [MemberProfileController::class, 'destroyAvatar'])->name('member.profile.avatar.destroy');
Route::middleware('auth')->get('/me/onboarding', [MemberOnboardingController::class, 'show'])->name('member.onboarding');
Route::middleware('auth')->patch('/me/password', [MemberPasswordController::class, 'update'])->name('member.password.update');
Route::middleware('auth')->post('/me/push-subscriptions', [MemberPushSubscriptionController::class, 'store'])->name('member.push-subscriptions.store');
Route::middleware('auth')->delete('/me/push-subscriptions', [MemberPushSubscriptionController::class, 'destroy'])->name('member.push-subscriptions.destroy');
Route::middleware('auth')->get('/me/notifications', [MemberNotificationsController::class, 'index'])->name('member.notifications');
Route::middleware('auth')->delete('/me/notifications/{notificationId}', [MemberNotificationsController::class, 'destroy'])->name('member.notifications.destroy');

// Committees (PWA)
Route::middleware('auth')->prefix('me/committees')->group(function () {
    Route::get('/', [\App\Http\Controllers\MemberCommitteeController::class, 'index'])->name('member.committees.index');
    Route::get('/posts/{post}', [\App\Http\Controllers\MemberCommitteeController::class, 'showPost'])->name('member.committees.posts.show');
    Route::get('/{committee}', [\App\Http\Controllers\MemberCommitteeController::class, 'show'])->name('member.committees.show');
    Route::post('/posts/{post}/read', [\App\Http\Controllers\MemberCommitteeController::class, 'markAsRead'])->name('member.committees.posts.mark-as-read');
});

// Broadcasts (PWA)
Route::middleware('auth')->get('/me/broadcasts/{broadcast}', [\App\Http\Controllers\MemberBroadcastController::class, 'show'])
    ->name('member.broadcasts.show');

// Projects (PWA)
Route::middleware('auth')->prefix('me/projects')->group(function () {
    Route::get('/', [\App\Http\Controllers\MemberProjectController::class, 'index'])->name('member.projects.index');
    Route::get('/{project}', [\App\Http\Controllers\MemberProjectController::class, 'show'])->name('member.projects.show');
    Route::patch('/{project}', [\App\Http\Controllers\MemberProjectController::class, 'update'])->name('member.projects.update');
});

// One-time invitation link (public)
Route::get('/invite/{token}', [MemberInvitationAcceptController::class, 'show'])->name('invite.accept');
Route::post('/invite/{token}', [MemberInvitationAcceptController::class, 'store'])->name('invite.store');

// Public content pages (published)
Route::get('/p/{slug}', [PublicContentPageController::class, 'show'])->name('public.page');

// Public legal pages
Route::get('/privacy-policy', function () {
    return Inertia::render('Public/PrivacyPolicy');
})->name('privacy.policy');

Route::get('/cookie-policy', function () {
    return Inertia::render('Public/CookiePolicy');
})->name('cookie.policy');

// Authentication routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Password reset routes
Route::get('/forgot-password', [\App\Http\Controllers\PasswordResetController::class, 'showRequestForm'])->name('password.request');
Route::post('/forgot-password', [\App\Http\Controllers\PasswordResetController::class, 'sendResetLink'])->name('password.email');
Route::get('/reset-password/{token}', [\App\Http\Controllers\PasswordResetController::class, 'showResetForm'])->name('password.reset');
Route::post('/reset-password', [\App\Http\Controllers\PasswordResetController::class, 'reset'])->name('password.update');

// Admin routes (protected)
Route::middleware(['auth', 'role:super_admin,admin'])->prefix('admin')->group(function () {
    Route::get('/dashboard', AdminDashboardController::class)->name('admin.dashboard');
    
    // Activity log cleanup (super admin only)
    Route::delete('/activity-logs/clear', [AdminDashboardController::class, 'clearActivityLogs'])
        ->middleware('role:super_admin')
        ->name('admin.activity.clear');

    Route::get('/profile', [AdminProfileController::class, 'edit'])->name('admin.profile.edit');
    Route::patch('/profile', [AdminProfileController::class, 'update'])->name('admin.profile.update');
    Route::post('/profile/avatar', [AdminProfileController::class, 'updateAvatar'])->name('admin.profile.avatar');
    Route::delete('/profile/avatar', [AdminProfileController::class, 'destroyAvatar'])->name('admin.profile.avatar.destroy');
    Route::put('/profile/password', [AdminProfileController::class, 'updatePassword'])->name('admin.profile.password');

    // CSV Export/Import (must come before resource route)
    Route::get('members/export', [\App\Http\Controllers\AdminMemberController::class, 'exportCsv'])
        ->name('members.export');
    Route::post('members/import', [\App\Http\Controllers\AdminMemberController::class, 'importCsv'])
        ->name('members.import');

    Route::resource('members', \App\Http\Controllers\AdminMemberController::class);
    Route::post('members/{member}/invite', [AdminMemberInvitationController::class, 'store'])
        ->name('members.invite.store');
    Route::delete('members/{member}/invite', [AdminMemberInvitationController::class, 'destroy'])
        ->name('members.invite.destroy');

    Route::post('members/{member}/membership', [AdminMembershipController::class, 'store'])
        ->name('members.membership.store');
    Route::delete('members/{member}/membership', [AdminMembershipController::class, 'destroy'])
        ->name('members.membership.destroy');
    
    // Member avatar (admin)
    Route::post('members/{member}/avatar', [\App\Http\Controllers\AdminMemberController::class, 'updateMemberAvatar'])
        ->name('members.avatar.update');
    Route::delete('members/{member}/avatar', [\App\Http\Controllers\AdminMemberController::class, 'destroyMemberAvatar'])
        ->name('members.avatar.destroy');
    
    Route::patch('members/{member}/role', \App\Http\Controllers\AdminMemberRoleController::class.'@update')
        ->name('members.role.update')
        ->middleware('role:super_admin');
    Route::resource('events', \App\Http\Controllers\AdminEventController::class);
    Route::get('events/{event}/checkins', [\App\Http\Controllers\AdminEventCheckinController::class, 'index'])
        ->name('events.checkins.index');
    Route::post('events/{event}/checkins', [\App\Http\Controllers\AdminEventCheckinController::class, 'store'])
        ->name('events.checkins.store');
    Route::get('events/{event}/checkins/export', [\App\Http\Controllers\AdminEventCheckinController::class, 'exportCsv'])
        ->name('events.checkins.export');
    Route::resource('projects', \App\Http\Controllers\AdminProjectController::class);
    
    // Committees
    Route::resource('committees', \App\Http\Controllers\AdminCommitteeController::class);
    Route::post('committees/{committee}/members', [\App\Http\Controllers\AdminCommitteeController::class, 'attachMember'])
        ->name('committees.members.attach');
    Route::delete('committees/{committee}/members/{user}', [\App\Http\Controllers\AdminCommitteeController::class, 'detachMember'])
        ->name('committees.members.detach');
    Route::get('committees/{committee}/posts/create', [\App\Http\Controllers\AdminCommitteeController::class, 'createPost'])
        ->name('committees.posts.create');
    Route::post('committees/{committee}/posts', [\App\Http\Controllers\AdminCommitteeController::class, 'storePost'])
        ->name('committees.posts.store');
    Route::get('committees/{committee}/posts/{post}/edit', [\App\Http\Controllers\AdminCommitteeController::class, 'editPost'])
        ->name('committees.posts.edit');
    Route::post('committees/{committee}/posts/{post}', [\App\Http\Controllers\AdminCommitteeController::class, 'updatePost'])
        ->name('committees.posts.update');
    Route::delete('committees/{committee}/posts/{post}', [\App\Http\Controllers\AdminCommitteeController::class, 'destroyPost'])
        ->name('committees.posts.destroy');
    Route::post('committees/{committee}/image', [\App\Http\Controllers\AdminCommitteeController::class, 'updateCommitteeImage'])
        ->name('committees.image.update');
    Route::delete('committees/{committee}/image', [\App\Http\Controllers\AdminCommitteeController::class, 'destroyCommitteeImage'])
        ->name('committees.image.destroy');
    
    Route::resource('content-pages', \App\Http\Controllers\AdminContentPageController::class)
        ->only(['index', 'store', 'update', 'destroy']);
    
    // Broadcast Notifications
    Route::resource('broadcasts', \App\Http\Controllers\AdminBroadcastController::class)
        ->only(['index', 'create', 'store', 'show', 'destroy']);
});
