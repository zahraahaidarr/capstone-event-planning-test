<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Admin\employeesController;
use App\Http\Controllers\Admin\dashboardController;
use App\Http\Controllers\Employee\dashboardController as EmployeeDashboardController;
use App\Http\Controllers\Admin\VolunteerController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\Admin\TaxonomiesVenuesController;
use App\Http\Controllers\Admin\EventsController;
use App\Http\Controllers\AnnouncementFeedController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Worker\EventDiscoveryController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\SystemSettingController;
use App\Http\Controllers\Worker\PostEventSubmissionController;
use App\Http\Controllers\Employee\VolunteerAssignmentController;
use App\Http\Controllers\Worker\ReservationController;
use App\Http\Controllers\Employee\MessageController as EmployeeMessageController;
use App\Http\Controllers\Worker\MessageController as WorkerMessageController;
use App\Http\Controllers\Employee\PostEventReportController;
use App\Http\Controllers\EventStaffingController;
use App\Http\Controllers\worker\PaymentController;
use App\Http\Controllers\Worker\WorkerDashboardController;
use App\Http\Controllers\Worker\FollowEmployeesController;
use App\Http\Controllers\Worker\FeedController;
use App\Http\Controllers\Employee\ContentController;
use App\Http\Controllers\Social\LikeController;
use App\Http\Controllers\Social\CommentController;
use App\Http\Controllers\Worker\FeedCommentController;


Route::post('/ai/staffing', [EventStaffingController::class, 'predictRoles']);

   
Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store'])->name('register.store');
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->middleware('auth')->name('logout');


    Route::middleware(['auth', 'role:ADMIN'])->group(function () {
        //admin dashboard route
        Route::get('/admin/dashboard', [dashboardController::class, 'index'])->name('admin.dashboard');
        //employees routes
        Route::get('/admin/employees', [EmployeesController::class, 'index'])->name('employees.index');
        Route::post('admin/employees', [EmployeesController::class, 'store'])->name('employees.store');
        Route::get('/admin/employees/search', [EmployeesController::class, 'search'])->name('employees.search');
        Route::get('/admin/employees/json', [EmployeesController::class, 'json'])->name('employees.json');
        Route::post('/admin/employees/{id}/status', [EmployeesController::class, 'setStatus'])->name('employee.set-status');
        Route::delete('/admin/employees/{id}',      [EmployeesController::class, 'destroy'])->name('destroy');
        //volunteers routes
        Route::get('/admin/volunteers', [VolunteerController::class, 'index'])->name('volunteers.index');
        Route::get('/admin/volunteers/list', [VolunteerController::class, 'list'])->name('volunteers.list');
        Route::get('/admin/volunteers/search', [VolunteerController::class, 'search'])->name('volunteers.search');
        Route::post('/admin/volunteers/{id}/status', [VolunteerController::class, 'setStatus'])->name('set-status');
        // Taxonomies & Venues routes
        Route::get('/admin/taxonomies-venues', [TaxonomiesVenuesController::class, 'index'])->name('taxonomies-venues.index');
        Route::get('/admin/taxonomies-venues/worker-types',    [TaxonomiesVenuesController::class, 'workerTypesIndex'])->name('taxonomies-venues.worker-types.index');
        Route::post('/admin/taxonomies-venues/worker-types',   [TaxonomiesVenuesController::class, 'workerTypesStore'])->name('taxonomies-venues.worker-types.store');
        Route::delete('/admin/taxonomies-venues/worker-types/{roleType}', [TaxonomiesVenuesController::class, 'workerTypesDestroy'])->name('taxonomies-venues.worker-types.destroy');
        Route::get('/admin/taxonomies-venues/event-categories',[TaxonomiesVenuesController::class,'eventCategoriesIndex']) ->name('taxonomies-venues.event-categories.index');
        Route::post('/admin/taxonomies-venues/event-categories',[TaxonomiesVenuesController::class,'eventCategoriesStore'])->name('taxonomies-venues.event-categories.store');
        Route::delete('/admin/taxonomies-venues/event-categories/{eventCategory}',[TaxonomiesVenuesController::class,'eventCategoriesDestroy'])->name('taxonomies-venues.event-categories.destroy');
        Route::get('/admin/taxonomies-venues/venues',[TaxonomiesVenuesController::class,'venuesIndex'])->name('taxonomies-venues.venues.index');
        Route::post('/admin/taxonomies-venues/venues',[TaxonomiesVenuesController::class,'venuesStore'])->name('taxonomies-venues.venues.store');
        Route::delete('/admin/taxonomies-venues/venues/{venue}',[TaxonomiesVenuesController::class,'venuesDestroy'])->name('taxonomies-venues.venues.destroy');
        
    });


    Route::middleware(['auth', 'role:EMPLOYEE'])->group(function () {
        Route::get('/employee/dashboard', [EmployeeDashboardController::class, 'index'])->name('employee.dashboard');
       
        Route::get('/employee/volunteer-assignment', [VolunteerAssignmentController::class, 'index'])->name('employee.volunteer.assignment');
        Route::get('/employee/volunteer-assignment/events/{event}/applications',[VolunteerAssignmentController::class, 'applications'])->name('volunteers.assign.applications');
        Route::patch('/employee/volunteer-assignment/reservations/{reservation}/status',[VolunteerAssignmentController::class, 'updateStatus'])->name('employee.volunteer-assignment.status');

        Route::get('/employee/messages', [EmployeeMessageController::class, 'index'])->name('employee.messages');
        Route::get('/employee/messages/contacts', [EmployeeMessageController::class, 'contacts'])->name('employee.messages.contacts');
        Route::get('/employee/messages/thread/{user}', [EmployeeMessageController::class, 'thread'])->name('employee.messages.thread');
        Route::post('/employee/messages/thread/{user}', [EmployeeMessageController::class, 'send'])->name('employee.messages.send');


        Route::get('/employee/post-event-reports', [PostEventReportController::class, 'index'])->name('employee.postEventReports.index');
        Route::post('/employee/post-event-reports/{submission}/approve', [PostEventReportController::class, 'approve'])->name('employee.postEventReports.approve');
        Route::post('/employee/post-event-reports/{submission}/reject', [PostEventReportController::class, 'reject'])->name('employee.postEventReports.reject');


          Route::get('/employee/content', [ContentController::class, 'index'])->name('content.index');

    // Create
    Route::post('/employee/content/posts',   [ContentController::class, 'storePost'])->name('content.posts.store');
    Route::post('/employee/content/reels',   [ContentController::class, 'storeReel'])->name('content.reels.store');
    Route::post('/employee/content/stories', [ContentController::class, 'storeStory'])->name('content.stories.store');
    Route::get('/employee/content/comments', [ContentController::class, 'comments'])
    ->name('employee.content.comments');
    // Delete
    Route::delete('/employee/content/posts/{post}',   [ContentController::class, 'destroyPost'])->name('content.posts.destroy');
    Route::delete('/employee/content/reels/{reel}',   [ContentController::class, 'destroyReel'])->name('content.reels.destroy');
    Route::delete('/employee/content/stories/{story}',[ContentController::class, 'destroyStory'])->name('content.stories.destroy');
    });

    Route::middleware(['auth', 'role:ADMIN|EMPLOYEE'])->group(function () {
    Route::get('/events', [EventsController::class, 'index'])->name('events.index');
    Route::post('/events', [EventsController::class, 'store'])->name('events.store');
    Route::get   ('/events/{event}',        [EventsController::class, 'show'])->name('events.show');
    Route::put   ('/events/{event}',        [EventsController::class, 'update'])->name('events.update');
    Route::patch('/admin/events/{event}/status',[EventsController::class, 'updateStatus'])->name('admin.events.status');
});


    Route::middleware(['auth'])->group(function () {
        Route::get('/announcements/create', [AnnouncementController::class, 'create'])->name('announcements.create');
        Route::post('/announcements', [AnnouncementController::class, 'store'])->name('announcements.store');
        Route::get('/employee/announcements', [AnnouncementFeedController::class, 'index'])->name('employee.announcements.index');
        Route::get('/worker/announcements', [AnnouncementFeedController::class, 'index'])->name('worker.announcements.index');
        
        Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::get('/api/notifications/unread-count', [NotificationController::class, 'unreadCount'])->name('notifications.unreadCount');
        Route::post('/api/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.markAllRead');
    
        Route::get('/profile',          [ProfileController::class,'show'])->name('profile');
        Route::get('/profile/data',     [ProfileController::class,'data'])->name('profile.data');
        Route::put('/profile/account',  [ProfileController::class,'updateAccount'])->name('profile.account');
        Route::put('/profile/personal', [ProfileController::class,'updatePersonal'])->name('profile.personal');
        Route::put('/profile/password', [ProfileController::class,'updatePassword'])->name('profile.password');
        Route::post('/profile/avatar',  [ProfileController::class,'uploadAvatar'])->name('profile.avatar');
        Route::put('/profile/engagement', [ProfileController::class, 'updateEngagement'])->name('profile.engagement'); // 👈 NEW

        Route::get('/settings',  [SystemSettingController::class, 'edit'])->name('settings');
        Route::post('/settings', [SystemSettingController::class, 'update'])->name('settings.update');
        Route::post('/settings/logout-all', [SystemSettingController::class, 'logoutAll'])->name('settings.logoutAll');
        Route::delete('/settings/delete-account', [SystemSettingController::class, 'destroyAccount'])->name('settings.deleteAccount');
   
    Route::post('/social/like', [LikeController::class, 'toggle'])->name('social.like.toggle');

    // Comments
    Route::get('/social/comments', [CommentController::class, 'index'])->name('social.comments.index');
    Route::post('/social/comments', [CommentController::class, 'store'])->name('social.comments.store');
   
   
    });




    Route::get('/dashboard', function () {
        $user = Auth::user();

        return match ($user?->role) {
            'ADMIN'    => redirect()->route('admin.dashboard'),
            'EMPLOYEE' => redirect()->route('employee.dashboard'),
            'WORKER'   => redirect()->route('worker.dashboard'),
            default    => redirect()->route('login'),
        };})->middleware('auth')->name('dashboard');

    Route::get('/', function () {
        if (Auth::check()) {
            return match (Auth::user()->role) {
                'ADMIN'    => redirect()->route('admin.dashboard'),
                'EMPLOYEE' => redirect()->route('employee.dashboard'),
                'WORKER'   => redirect()->route('worker.dashboard'),
                default    => redirect()->route('login'),
            };
        }
        return redirect()->route('login');})->name('home');



Route::middleware(['auth', 'role:WORKER'])->prefix('worker')->name('worker.')->group(function () {

        Route::get('/dashboard', [WorkerDashboardController::class, 'index'])->name('dashboard');


        Route::get('/events/discover', [EventDiscoveryController::class, 'index'])->name('events.discover');
        Route::get('/events/discover/list', [EventDiscoveryController::class, 'list'])->name('events.discover.list');
        Route::post('/events/{event}/apply', [EventDiscoveryController::class, 'apply'])->name('events.apply');
        
        Route::get('/my-reservations', [ReservationController::class, 'index'])->name('reservations');
        Route::delete('/reservation/{id}', [ReservationController::class, 'cancel'])->name('reservation.cancel');
        Route::patch('/reservation/{reservation}/complete',[ReservationController::class, 'complete'])->name('reservation.complete');


        Route::get('/submissions', [PostEventSubmissionController::class, 'index'])->name('submissions'); // used by your Blade
        Route::post('/submissions', [PostEventSubmissionController::class, 'store'])->name('submissions.store');

        Route::get('/messages', [WorkerMessageController::class, 'index'])->name('messages');
        Route::get('/messages/contacts', [WorkerMessageController::class, 'contacts'])->name('messages.contacts');
        Route::get('/messages/thread/{user}', [WorkerMessageController::class, 'thread'])->name('messages.thread');
        Route::post('/messages/thread/{user}', [WorkerMessageController::class, 'send'])->name('messages.send');

        Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');
    
      // ✅ Follow Employees Page + APIs
       Route::get('/follow-employees', [FollowEmployeesController::class, 'index'])
        ->name('follow.index');

    Route::get('/follow-employees/search', [FollowEmployeesController::class, 'search'])
        ->name('follow.search');

    Route::post('/follow-employees/{employeeId}/toggle', [FollowEmployeesController::class, 'toggleFollow'])
        ->name('follow.toggle');

    Route::get('/feed', [FeedController::class, 'index'])
        ->name('feed.index');
    Route::get('/employees/following', [FollowEmployeesController::class, 'index'])
    ->name('employees.following');


   Route::get('/feed/comments', [FeedCommentController::class, 'index'])
    ->name('feed.comments.index');

Route::post('/feed/comments', [FeedCommentController::class, 'store'])
    ->name('feed.comments.store');

    Route::post('/feed/stories/seen', [FeedController::class, 'markStorySeen'])
    ->name('feed.stories.seen');

    });

