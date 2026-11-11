<?php

namespace App\Services\Lms;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class TrainerLmsMaterialAuthoringService
{
    /**
     * These are the only block types rendered and saved by the current module
     * builder. Legacy rows stay untouched, but cannot inflate visible counts.
     *
     * @var array<int, string>
     */
    public const BUILDER_BLOCK_TYPES = [
        'title',
        'text',
        'material',
        'media',
        'youtube',
        'quiz',
        'submission',
    ];

    /**
     * Canonical file-extension contract used by the existing Materials
     * renderer and upload validation for persisted Media blocks.
     *
     * @var array<string, list<string>>
     */
    public const MEDIA_EXTENSIONS = [
        'image' => ['jpg', 'jpeg', 'png', 'webp', 'gif'],
        'video' => ['mp4', 'webm', 'mov', 'avi'],
        'audio' => ['mp3', 'wav', 'm4a', 'ogg'],
    ];

    public static function mediaKind(?string $originalFileName): ?string
    {
        $extension = strtolower(pathinfo((string) $originalFileName, PATHINFO_EXTENSION));

        foreach (self::MEDIA_EXTENSIONS as $kind => $extensions) {
            if (in_array($extension, $extensions, true)) {
                return $kind;
            }
        }

        return null;
    }

    /** @return list<string> */
    public static function interactiveMediaExtensions(): array
    {
        return array_values(array_merge(
            self::MEDIA_EXTENSIONS['video'],
            self::MEDIA_EXTENSIONS['audio']
        ));
    }

    /**
     * Return every assessment that belongs to the current course and normalize
     * only the two canonical authoring types used by the shared builder.
     * Module-linked assessments remain editable in this workspace.
     *
     * @return Collection<int, object>
     */
    public function assessmentsForCourse(string $courseId): Collection
    {
        $wrapperAttachments = DB::table('training_content_block')
            ->join(
                'training_module',
                'training_content_block.module_id',
                '=',
                'training_module.module_id'
            )
            ->where('training_module.course_id', $courseId)
            ->whereNotNull('training_content_block.assessment_id')
            ->select(
                'training_content_block.assessment_id',
                'training_module.module_id',
                'training_module.module_title'
            )
            ->get()
            ->keyBy(static fn (object $row): string => (string) $row->assessment_id);
        $questionCounts = DB::table('assessment_question')
            ->join('assessment', 'assessment_question.assessment_id', '=', 'assessment.assessment_id')
            ->where('assessment.course_id', $courseId)
            ->selectRaw('assessment_question.assessment_id, COUNT(*) as question_count')
            ->groupBy('assessment_question.assessment_id')
            ->pluck('question_count', 'assessment_id');

        return DB::table('assessment')
            ->where('course_id', $courseId)
            ->whereIn(DB::raw('LOWER(TRIM(type))'), ['quiz', 'submission'])
            ->orderByDesc('created_at')
            ->orderByDesc('assessment_id')
            ->get()
            ->map(function (object $assessment) use (
                $wrapperAttachments,
                $questionCounts
            ): object {
                $assessment->type = strtolower(trim((string) $assessment->type));
                $wrapper = $wrapperAttachments->get((string) $assessment->assessment_id);
                $assessment->attachment_module_id = $wrapper->module_id ?? null;
                $assessment->attachment_module_title = $wrapper->module_title ?? null;
                $assessment->attachment_source = $wrapper ? 'content_block' : null;
                $assessment->question_count = (int) ($questionCounts->get(
                    $assessment->assessment_id
                ) ?? 0);
                $assessment->availability = $this->deriveAssessmentAvailability(
                    $assessment->available_from ?? null,
                    $assessment->available_until ?? null
                );

                return $assessment;
            })
            ->values();
    }

    /**
     * @param  Collection<int, int|string>  $moduleIds
     * @return Collection<int, object>
     */
    public function blocksForModules(Collection $moduleIds): Collection
    {
        if ($moduleIds->isEmpty()) {
            return collect();
        }

        $blocks = $this->builderBlocksQuery()
            ->whereIn('training_content_block.module_id', $moduleIds)
            ->leftJoin(
                'learning_material',
                'training_content_block.material_id',
                '=',
                'learning_material.material_id'
            )
            ->leftJoin(
                'assessment',
                'training_content_block.assessment_id',
                '=',
                'assessment.assessment_id'
            )
            ->select(
                'training_content_block.*',
                'learning_material.original_file_name',
                'learning_material.file_path',
                'learning_material.material_type',
                'learning_material.ai_generated_title',
                'learning_material.ai_synopsis',
                'assessment.title as assessment_title',
                'assessment.type as assessment_type',
                'assessment.available_from',
                'assessment.available_until',
                'assessment.accepted_format',
                'assessment.total_marks'
            )
            ->orderBy('training_content_block.module_id')
            ->orderByRaw('CASE WHEN training_content_block.block_order IS NULL THEN 1 ELSE 0 END')
            ->orderBy('training_content_block.block_order')
            ->orderBy('training_content_block.block_id')
            ->get();

        $questionCounts = DB::table('assessment_question')
            ->whereIn('assessment_id', $blocks->pluck('assessment_id')->filter())
            ->selectRaw('assessment_id, COUNT(*) as question_count')
            ->groupBy('assessment_id')
            ->pluck('question_count', 'assessment_id');

        return $blocks->map(function (object $block) use ($questionCounts): object {
            if ($block->assessment_id) {
                $block->assessment_question_count = (int) (
                    $questionCounts->get($block->assessment_id) ?? 0
                );
                $block->assessment_availability = $this->deriveAssessmentAvailability(
                    $block->available_from ?? null,
                    $block->available_until ?? null
                );
            }

            return $block;
        });
    }

    /**
     * Derive display-only authoring availability without creating a stored
     * status. The end instant remains open; it becomes closed immediately
     * after that instant.
     *
     * @return array{key: string, label: string, context: string}
     */
    public function deriveAssessmentAvailability(
        mixed $availableFrom,
        mixed $availableUntil,
        ?CarbonInterface $now = null
    ): array {
        $current = $now
            ? CarbonImmutable::instance($now)
            : CarbonImmutable::now(config('app.timezone'));
        $from = $availableFrom
            ? CarbonImmutable::parse($availableFrom, config('app.timezone'))
            : null;
        $until = $availableUntil
            ? CarbonImmutable::parse($availableUntil, config('app.timezone'))
            : null;
        $format = static fn (CarbonInterface $date): string => $date->format('d M Y, h:i A');

        if (! $from && ! $until) {
            return [
                'key' => 'always',
                'label' => 'Always available',
                'context' => 'No availability restrictions',
            ];
        }

        if ($from && $current->lt($from)) {
            return [
                'key' => 'scheduled',
                'label' => 'Scheduled',
                'context' => 'Opens '.$format($from),
            ];
        }

        if ($until && $current->gt($until)) {
            return [
                'key' => 'closed',
                'label' => 'Closed',
                'context' => 'Closed '.$format($until),
            ];
        }

        return [
            'key' => 'open',
            'label' => 'Open',
            'context' => $until
                ? 'Available until '.$format($until)
                : 'Available now',
        ];
    }

    public function visibleBlockCount(int $moduleId): int
    {
        return $this->builderBlocksQuery()
            ->where('module_id', $moduleId)
            ->count();
    }

    public function nextBlockOrder(int $moduleId): int
    {
        return ((int) DB::table('training_content_block')
            ->where('module_id', $moduleId)
            ->max('block_order')) + 1;
    }

    public function resolveAppendedBlockOrder(int $moduleId, ?int $requestedOrder): int
    {
        return $requestedOrder ?? $this->nextBlockOrder($moduleId);
    }

    public function normalizeBuilderBlockOrder(int $moduleId): void
    {
        $blockIds = $this->builderBlocksQuery()
            ->where('module_id', $moduleId)
            ->orderByRaw('CASE WHEN block_order IS NULL THEN 1 ELSE 0 END')
            ->orderBy('block_order')
            ->orderBy('block_id')
            ->pluck('block_id');

        foreach ($blockIds as $index => $blockId) {
            DB::table('training_content_block')
                ->where('module_id', $moduleId)
                ->where('block_id', $blockId)
                ->update([
                    'block_order' => $index + 1,
                    'updated_at' => now(),
                ]);
        }
    }

    public function builderBlocksQuery(): Builder
    {
        $query = DB::table('training_content_block')
            ->whereIn('training_content_block.block_type', self::BUILDER_BLOCK_TYPES);

        if (Schema::hasColumn('training_content_block', 'is_visible')) {
            $query->where(function (Builder $builder): void {
                $builder->where('training_content_block.is_visible', true)
                    ->orWhereNull('training_content_block.is_visible');
            });
        }

        return $query;
    }
}
