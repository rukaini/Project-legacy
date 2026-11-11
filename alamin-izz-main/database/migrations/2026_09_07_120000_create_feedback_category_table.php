<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feedback_category', function (Blueprint $table) {
            $table->string('categoryID', 20)->primary();
            $table->string('categoryName')->unique();
            $table->timestamps();
        });

        // Backfill one category row per distinct existing feedback_question.category value.
        $distinctCategories = DB::table('feedback_question')
            ->whereNotNull('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        $now = now();
        foreach ($distinctCategories as $i => $name) {
            DB::table('feedback_category')->insert([
                'categoryID'   => 'FC' . str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT),
                'categoryName' => $name,
                'created_at'   => $now,
                'updated_at'   => $now,
            ]);
        }

        Schema::table('feedback_question', function (Blueprint $table) {
            $table->string('categoryID', 20)->nullable()->after('category');
        });

        DB::statement('
            UPDATE feedback_question fq
            JOIN feedback_category fc ON fc.categoryName = fq.category
            SET fq.categoryID = fc.categoryID
        ');

        // Anything left uncategorised falls back to a generic "General" category.
        if (DB::table('feedback_question')->whereNull('categoryID')->exists()) {
            $generalId = 'FC' . str_pad((string) ($distinctCategories->count() + 1), 4, '0', STR_PAD_LEFT);
            DB::table('feedback_category')->insert([
                'categoryID'   => $generalId,
                'categoryName' => 'General',
                'created_at'   => $now,
                'updated_at'   => $now,
            ]);
            DB::table('feedback_question')->whereNull('categoryID')->update(['categoryID' => $generalId]);
        }

        Schema::table('feedback_question', function (Blueprint $table) {
            $table->dropColumn('category');
            $table->string('categoryID', 20)->nullable(false)->change();

            $table->index(['categoryID', 'questionOrder'], 'idx_feedback_question_category');
            $table->foreign('categoryID', 'fk_feedback_question_category')
                ->references('categoryID')->on('feedback_category')
                ->onDelete('cascade')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('feedback_question', function (Blueprint $table) {
            $table->dropForeign('fk_feedback_question_category');
            $table->dropIndex('idx_feedback_question_category');
            $table->string('category', 100)->nullable()->after('formID');
        });

        DB::statement('
            UPDATE feedback_question fq
            JOIN feedback_category fc ON fc.categoryID = fq.categoryID
            SET fq.category = fc.categoryName
        ');

        Schema::table('feedback_question', function (Blueprint $table) {
            $table->dropColumn('categoryID');
        });

        Schema::dropIfExists('feedback_category');
    }
};
