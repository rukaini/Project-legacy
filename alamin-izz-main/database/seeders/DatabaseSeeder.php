<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,        // 1. roles first
            SchoolSeeder::class,      // 2. schools (no dependencies)
            TeacherSeeder::class,     // 3. teachers (needs schools)
            PrincipalSeeder::class,   // 4. principals (needs schools)
            CourseSeeder::class,      // 5. courses (no dependencies)
            GuruNewSeeder::class,     // 6. guru_new / guru baru records (needs school)
            UserSeeder::class,        // 7. users + roles (needs teachers/principals exist)
            AssignSeeder::class,      // 8. assign teachers to schools
            TrainerSeeder::class,     // 9. trainers
            CourseSessionSeeder::class, // 10. sessions (needs courses + trainers)
            DemoSeeder::class,          // 11. demo data (needs all above)
            FeedbackSeeder::class,      // 12. feedback forms + questions
            PresentationSeeder::class,    // 13. rich demo data for presentation
            MoreDataSeeder::class,        // 14. more users, courses, all tables
            LearningMaterialSeeder::class, // 15. learning materials for all courses
            FeedbackSeeder2::class,        // 16. feedback forms for courses 11-18
            MoreSchoolsSeeder::class,      // 17. 4 more schools + principals + teachers
            DemoLadapSeeder::class,        // 18. 3 courses demonstrating 0.5×, 1.0×, 1.5× LADAP multipliers
            ModuleAssessmentSeeder::class, // 19. modules, content blocks, assessments, questions for courses 10, 19–21
            ExtraDataSeeder::class,        // 20. 3 more schools (SK Kepong, SK Bangsar, SK Setapak) + principals + teachers 21–29
            MoreCoursesSeeder::class,      // 21. 6 courses (3 single + 3 multi_day, all 3 LADAP tiers) + trainers 5–6 + sessions today 16:00
        ]);
    }
}
