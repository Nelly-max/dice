<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rider applications get a comment from the team instead of a "rejection reason": a note the
 * rider can act on (e.g. "please upload a clearer photo of your licence") at any stage, not
 * only when an application is turned down. comment_at is when it was last written.
 *
 * customer_notifications: messages for a customer in the Hub (the bell), e.g. a new comment
 * on their application, or "your application was approved".
 */
return new class extends Migration
{
    public function up(): void
    {
        $schema = Schema::connection('customer');

        if ($schema->hasColumn('riders', 'rejection_reason') && !$schema->hasColumn('riders', 'comment')) {
            $schema->table('riders', function (Blueprint $table) {
                $table->renameColumn('rejection_reason', 'comment');
            });
        }

        if (!$schema->hasColumn('riders', 'comment')) {
            $schema->table('riders', function (Blueprint $table) {
                $table->text('comment')->nullable()->after('account_status');
            });
        }

        if (!$schema->hasColumn('riders', 'comment_at')) {
            $schema->table('riders', function (Blueprint $table) {
                $table->timestamp('comment_at')->nullable()->after('comment');
            });
        }

        if (!$schema->hasTable('customer_notifications')) {
            $schema->create('customer_notifications', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('customer_id');
                $table->string('type', 40);                 // application_comment, application_approved, …
                $table->string('title', 160);
                $table->text('body')->nullable();
                $table->string('url', 500)->nullable();     // where tapping it goes
                $table->string('icon', 60)->nullable();     // Font Awesome class, e.g. fa-solid fa-motorcycle
                $table->timestamp('read_at')->nullable();
                $table->timestamps();

                $table->index(['customer_id', 'read_at']);
                $table->foreign('customer_id')->references('id')->on('customer_accounts')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        $schema = Schema::connection('customer');

        $schema->dropIfExists('customer_notifications');

        if ($schema->hasColumn('riders', 'comment_at')) {
            $schema->table('riders', fn (Blueprint $table) => $table->dropColumn('comment_at'));
        }
        if ($schema->hasColumn('riders', 'comment') && !$schema->hasColumn('riders', 'rejection_reason')) {
            $schema->table('riders', fn (Blueprint $table) => $table->renameColumn('comment', 'rejection_reason'));
        }
    }
};
