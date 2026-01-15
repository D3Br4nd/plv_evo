<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Users
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('role')->default('member'); // member, admin, super_admin
            $table->string('membership_status')->default('inactive'); // inactive, active, expired
            $table->rememberToken();
            $table->timestamps();

            // Profile fields
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('birth_place_type')->nullable(); // it, foreign
            $table->string('birth_province_code', 2)->nullable();
            $table->string('birth_city')->nullable();
            $table->string('birth_country')->nullable();
            
            $table->string('residence_type')->nullable(); // it, foreign
            $table->string('residence_street')->nullable();
            $table->string('residence_house_number')->nullable();
            $table->string('residence_locality')->nullable();
            $table->string('residence_province_code', 2)->nullable();
            $table->string('residence_city')->nullable();
            $table->string('residence_country')->nullable();

            $table->date('plv_joined_at')->nullable();
            $table->date('plv_expires_at')->nullable();
            $table->string('phone')->nullable();
            $table->boolean('must_set_password')->default(false);
            $table->string('avatar_path')->nullable();
            $table->string('plv_role')->nullable();
        });

        // Password Reset Tokens
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        // Sessions
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->uuid('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

        // Activity Logs
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('actor_user_id')->nullable()->index();
            $table->string('action'); // created, updated, deleted
            $table->string('subject_type');
            $table->uuid('subject_id')->nullable()->index();
            $table->text('summary');
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->foreign('actor_user_id')->references('id')->on('users')->nullOnDelete();
        });

        // Memberships (annual)
        Schema::create('memberships', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->integer('year');
            $table->string('status')->default('active');
            $table->timestamp('paid_at')->nullable();
            $table->decimal('amount', 8, 2)->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'year']);
        });

        // Member Invitations
        Schema::create('member_invitations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->uuid('created_by_user_id')->nullable();
            $table->string('token_hash')->unique();
            $table->timestamp('expires_at')->index();
            $table->timestamp('used_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'expires_at']);
        });

        // Committees (Moved before Events)
        Schema::create('committees', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status')->default('active');
            $table->string('image_path')->nullable();
            $table->foreignUuid('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Events
        Schema::create('events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->timestamp('start_date');
            $table->timestamp('end_date');
            $table->string('type'); // meeting, event, etc
            $table->text('description')->nullable();
            $table->foreignUuid('committee_id')->nullable()->constrained('committees')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        // Event Checkins
        Schema::create('event_checkins', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('event_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('membership_id')->constrained()->cascadeOnDelete();
            $table->string('role')->nullable();
            $table->uuid('checked_in_by_user_id')->nullable();
            $table->timestamp('checked_in_at')->useCurrent();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['event_id', 'membership_id']);
            $table->index(['event_id', 'checked_in_at']);
        });

        // Projects (Kanban)
        Schema::create('projects', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->text('description')->nullable(); // Short description
            $table->text('content')->nullable();     // Rich text content
            $table->string('status')->default('todo'); 
            $table->string('priority')->default('medium');
            $table->timestamp('deadline')->nullable();
            $table->foreignUuid('committee_id')->nullable()->constrained('committees')->nullOnDelete();
            $table->uuid('assignee_id')->nullable(); // Legacy single assignee
            $table->timestamps();
        });

        // Project Members (Pivot)
        Schema::create('project_user', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('project_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['project_id', 'user_id']);
        });

        // Committee Members (Pivot)
        Schema::create('committee_user', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('committee_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->string('role')->nullable(); // President, Secretary, etc within committee
            $table->timestamp('joined_at')->useCurrent();
            $table->timestamps();

            $table->unique(['committee_id', 'user_id']);
        });

        // Committee Posts (Bulletin Board)
        Schema::create('committee_posts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('committee_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('author_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->text('content');
            $table->string('featured_image_path')->nullable();
            $table->string('attachment_path')->nullable();
            $table->string('attachment_name')->nullable();
            $table->timestamps();
        });

        // Committee Post Read Status (Pivot)
        Schema::create('committee_post_read', function (Blueprint $table) {
            $table->foreignUuid('post_id')->constrained('committee_posts')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('read_at')->useCurrent();

            $table->primary(['post_id', 'user_id']);
        });

        // Content Pages (Public/Private)
        Schema::create('content_pages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('excerpt')->nullable();
            $table->text('body');
            $table->string('status')->default('draft'); // draft, published
            $table->timestamp('published_at')->nullable();
            $table->uuid('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->uuid('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'published_at']);
        });

        // Broadcast Notifications (Admin to all active members)
        Schema::create('broadcast_notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->text('content');                     // HTML formatted (from Tiptap)
            $table->string('featured_image_path')->nullable();
            $table->string('attachment_path')->nullable();
            $table->string('attachment_name')->nullable(); // Original filename
            $table->foreignUuid('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('sent_at')->nullable();     // When notifications were sent
            $table->timestamps();
        });

        // Notifications
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->uuidMorphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        // Push Subscriptions (WebPush)
        Schema::create('push_subscriptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuidMorphs('subscribable');
            $table->string('endpoint', 500)->unique();
            $table->string('public_key')->nullable();
            $table->string('auth_token')->nullable();
            $table->string('content_encoding')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('push_subscriptions');
        Schema::dropIfExists('broadcast_notifications');
        Schema::dropIfExists('committee_post_read');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('content_pages');
        Schema::dropIfExists('committee_posts');
        Schema::dropIfExists('committee_user');
        Schema::dropIfExists('committees');
        Schema::dropIfExists('projects');
        Schema::dropIfExists('event_checkins');
        Schema::dropIfExists('events');
        Schema::dropIfExists('member_invitations');
        Schema::dropIfExists('memberships');
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
