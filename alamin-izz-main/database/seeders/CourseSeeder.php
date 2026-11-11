<?php

namespace Database\Seeders;

use App\Models\Course;
use Illuminate\Database\Seeder;

class CourseSeeder extends Seeder
{
    public function run(): void
    {
        $courses = [
            [
                'courseName'     => 'Introduction to Inclusive Education',
                'course_category' => 'single',
                'description'     => 'A comprehensive course on inclusive education practices for mainstream classroom teachers.',
                'capacity'        => 30,
                'price'           => 0,
                'status'          => 'completed',
                'poster'          => null,
                'mode'      => 'physical',
                'course_pic'      => null,
                'whatsappGroup'  => null,
                'organiserName'  => 'Jabatan Pendidikan Selangor',
            ],
            [
                'courseName'     => 'Digital Pedagogy for 21st Century Educators',
                'course_category' => 'series',
                'description'     => 'Empowering teachers with digital tools and strategies for modern classroom delivery.',
                'capacity'        => 40,
                'price'           => 50.00,
                'status'          => 'upcoming',
                'poster'          => null,
                'mode'      => 'hybrid',
                'course_pic'      => null,
                'whatsappGroup'  => 'https://chat.whatsapp.com/demo123',
                'organiserName'  => 'Kementerian Pendidikan Malaysia',
            ],
            [
                'courseName'     => 'Classroom Management Strategies',
                'course_category' => 'multi_day',
                'description'     => 'Effective techniques for managing diverse classrooms and improving student discipline.',
                'capacity'        => 25,
                'price'           => 30.00,
                'status'          => 'ongoing',
                'poster'          => null,
                'mode'      => 'online',
                'course_pic'      => null,
                'whatsappGroup'  => null,
                'organiserName'  => 'Institut Aminuddin Baki',
            ],
            [
                'courseName'     => 'STEM Integration Programme for Secondary Teachers',
                'course_category' => 'multi_day',
                'description'     => 'A multi-day residential programme equipping teachers with hands-on STEM integration skills across subjects. Participants will design cross-curricular lesson plans and present them on the final day.',
                'capacity'        => 50,
                'price'           => 120.00,
                'status'          => 'upcoming',
                'poster'          => null,
                'mode'      => 'physical',
                'course_pic'      => 'Dr. Hafizuddin Razak',
                'whatsappGroup'  => 'https://chat.whatsapp.com/stem2026',
                'organiserName'  => 'Bahagian Pembangunan Kurikulum, KPM',
            ],
            [
                'courseName'     => 'Effective Assessment and Feedback Techniques',
                'course_category' => 'single',
                'description'     => 'Equips teachers with practical strategies for formative and summative assessment, constructive feedback, and using assessment data to drive learning improvements.',
                'capacity'        => 35,
                'price'           => 0,
                'status'          => 'upcoming',
                'poster'          => null,
                'mode'      => 'physical',
                'course_pic'      => 'Pn. Roziana Ismail',
                'whatsappGroup'  => null,
                'organiserName'  => 'Jabatan Pendidikan Selangor',
            ],
            [
                'courseName'     => 'Special Education Awareness Workshop',
                'course_category' => 'single',
                'description'     => 'Raises awareness on learning disabilities, autism spectrum, and hearing or visual impairments. Teachers will learn early identification signs and basic accommodation strategies.',
                'capacity'        => 40,
                'price'           => 0,
                'status'          => 'upcoming',
                'poster'          => null,
                'mode'      => 'hybrid',
                'course_pic'      => null,
                'whatsappGroup'  => 'https://chat.whatsapp.com/spedworkshop2026',
                'organiserName'  => 'Unit Pendidikan Khas, KPM',
            ],
            [
                'courseName'     => 'Outdoor and Experiential Learning Facilitation',
                'course_category' => 'multi_day',
                'description'     => 'A two-day hands-on camp preparing teachers to design and facilitate safe outdoor learning activities that build student resilience, teamwork, and critical thinking.',
                'capacity'        => 30,
                'price'           => 80.00,
                'status'          => 'upcoming',
                'poster'          => null,
                'mode'      => 'physical',
                'course_pic'      => 'En. Faizal Nordin',
                'whatsappGroup'  => null,
                'organiserName'  => 'Institut Aminuddin Baki',
            ],
            [
                'courseName'     => 'Al Amin School Development Workshop',
                'course_category' => 'single',
                'description'     => 'A single-day professional development workshop organised by Al Amin Edu Oasis to strengthen teaching quality and school leadership practices.',
                'capacity'        => 30,
                'price'           => 0,
                'status'          => 'ongoing',
                'poster'          => null,
                'mode'      => 'physical',
                'course_pic'      => null,
                'whatsappGroup'  => null,
                'organiserName'  => 'EDU Al-amin',
            ],
            [
                'courseName'     => 'Project-Based Learning Implementation Workshop',
                'course_category' => 'multi_day',
                'description'     => 'A multi-day workshop guiding teachers through the full cycle of project-based learning — from designing authentic problems to assessing student outputs. Day 1 is today; Day 2 continues next week.',
                'capacity'        => 30,
                'price'           => 60.00,
                'status'          => 'ongoing',
                'poster'          => null,
                'mode'      => 'physical',
                'course_pic'      => 'En. Azrul Hisham',
                'whatsappGroup'  => null,
                'organiserName'  => 'Jabatan Pendidikan Selangor',
            ],
            [
                'courseName'     => 'Instructional Coaching for School Leaders',
                'course_category' => 'series',
                'description'     => 'A structured series for school leaders and senior teachers on coaching frameworks, classroom observation protocols, and giving effective instructional feedback.',
                'capacity'        => 20,
                'price'           => 200.00,
                'status'          => 'upcoming',
                'poster'          => null,
                'mode'      => 'hybrid',
                'course_pic'      => 'Dr. Norhaslinda Ahmad',
                'whatsappGroup'  => null,
                'organiserName'  => 'Bahagian Pendidikan Guru, KPM',
            ],
        ];

        foreach ($courses as $data) {
            Course::updateOrCreate(['courseName' => $data['courseName']], $data);
        }
    }
}
