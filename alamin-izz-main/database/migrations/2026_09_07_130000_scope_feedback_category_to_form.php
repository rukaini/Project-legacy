<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * feedback_category was built as a global, shared-by-name table. The reference schema scopes
 * categories to a single form (formID FK + categoryOrder), so a category shared across several
 * forms today must become one distinct row per form that uses it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('feedback_category', function (Blueprint $table) {
            $table->dropUnique('feedback_category_categoryname_unique');
            $table->unsignedBigInteger('formID')->nullable()->after('categoryName');
            $table->integer('categoryOrder')->default(1)->after('formID');
        });

        // One (formID, old categoryID) pair per distinct category actually used within a form,
        // ordered by the earliest question in that category so categoryOrder reflects real usage.
        $usages = DB::table('feedback_question')
            ->select('formID', 'categoryID', DB::raw('MIN(questionOrder) as first_order'))
            ->groupBy('formID', 'categoryID')
            ->orderBy('formID')
            ->orderBy('first_order')
            ->get();

        $nextNumber = DB::table('feedback_category')->count() + 1;
        $now        = now();

        $perForm = [];
        foreach ($usages as $usage) {
            $perForm[$usage->formID] ??= 0;
            $perForm[$usage->formID]++;

            $oldCategory = DB::table('feedback_category')->where('categoryID', $usage->categoryID)->first();

            $newCategoryId = 'FC' . str_pad((string) $nextNumber++, 4, '0', STR_PAD_LEFT);
            DB::table('feedback_category')->insert([
                'categoryID'    => $newCategoryId,
                'categoryName'  => $oldCategory->categoryName,
                'formID'        => $usage->formID,
                'categoryOrder' => $perForm[$usage->formID],
                'created_at'    => $now,
                'updated_at'    => $now,
            ]);

            DB::table('feedback_question')
                ->where('formID', $usage->formID)
                ->where('categoryID', $usage->categoryID)
                ->update(['categoryID' => $newCategoryId]);
        }

        // The original global rows are now unreferenced — remove them.
        DB::table('feedback_category')->whereNull('formID')->delete();

        Schema::table('feedback_category', function (Blueprint $table) {
            $table->string('categoryName', 100)->change();
            $table->unsignedBigInteger('formID')->nullable(false)->change();
            $table->dropColumn(['created_at', 'updated_at']);

            $table->index(['formID', 'categoryOrder'], 'idx_feedback_category_form');
            $table->foreign('formID', 'fk_feedback_category_form')
                ->references('formID')->on('feedback_form')
                ->onDelete('cascade')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('feedback_category', function (Blueprint $table) {
            $table->dropForeign('fk_feedback_category_form');
            $table->dropIndex('idx_feedback_category_form');
            $table->dropColumn(['formID', 'categoryOrder']);
            $table->string('categoryName', 255)->change();
            $table->unique('categoryName');
            $table->timestamps();
        });
    }
};
