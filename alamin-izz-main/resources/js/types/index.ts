import { LucideIcon } from 'lucide-react';

export type UserRole = 'guru_besar' | 'teacher' | 'outsider' | 'guru_baru' | 'hr';

export interface User {
    id: number | string;
    name: string;
    email: string;
    role: UserRole;
    avatar?: string;
    session_only?: boolean;
    email_verified_at?: string | null;
    created_at?: string;
    updated_at?: string;
    [key: string]: unknown;
}

export interface Auth {
    user: User | null;
}

export interface Notifications {
    unread_count: number;
}

export interface Flash {
    success?: string | null;
    error?: string | null;
}

export interface PageProps {
    auth: Auth;
    notifications: Notifications;
    flash: Flash;
    [key: string]: unknown;
}

export interface SharedData extends PageProps {
    name: string;
    quote?: { message: string; author: string };
    [key: string]: unknown;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavGroup {
    title: string;
    items: NavItem[];
}

export interface NavItem {
    title: string;
    url: string;
    icon?: LucideIcon | null;
    isActive?: boolean;
}

export interface Trainer {
    trainer_id: string;
    trainer_name: string;
    trainer_ic: string | null;
    trainer_email: string | null;
    trainer_phoneNo: string | null;
    expertise: string | null;
    average_rating: number;
    status: 'active' | 'inactive';
}

export interface CourseSession {
    session_id: string;
    courseID: string;
    session_date: string;
    start_time: string | null;
    end_time: string | null;
    location: string | null;
    trainers?: Trainer[];
}

export interface Training {
    id: string;
    courseID?: string;
    courseName: string;
    title?: string;
    course_category: 'single' | 'series' | 'multi_day' | null;
    description: string | null;
    capacity: number;
    price: number;
    status: 'upcoming' | 'ongoing' | 'completed' | 'cancelled';
    poster: string | null;
    mode: string | null;
    location?: string | null;
    course_pic?: string | null;
    whatsappGroup: string | null;
    organiserName: string;
    trainer_name?: string;
    created_at?: string;
    updated_at?: string;
    applications_count?: number;
    course_sessions?: CourseSession[];
}

export interface Application {
    id: string;         // composite key "{teacherID}_{courseID}"
    teacherID: string;
    courseID: string;
    training_id?: string;
    status: 'pending' | 'approved' | 'rejected';
    rejection_reason?: string | null;
    applied_at: string;
    user?: User;        // virtual, injected by controller from teacher→email
    training?: Training;
    can_view?: boolean;             // participant_material_access, on My Applications
    access_until?: string | null;
}

export interface Paginated<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: { url: string | null; label: string; active: boolean }[];
}

export interface Task {
    id: number;
    course_id: string;
    training_id?: string;
    title: string;
    description: string;
    deadline: string;
    accepted_file_types: string | null;
    training?: Training;
}

export interface TaskSubmission {
    id: number;
    user_id: number;
    task_id: number;
    text_answer: string | null;
    file_path: string | null;
    submitted_at: string | null;
    status: 'not_submitted' | 'submitted' | 'graded';
    grade: string | null;
    user?: User;
}

export interface QuizOption {
    id: number;
    question_id?: number;
    option_text: string;
    is_correct?: boolean;
}

export interface QuizQuestion {
    id: number;
    quiz_id?: number;
    question_text: string;
    type: 'mcq' | 'short_answer';
    display_order: number;
    options?: QuizOption[];
}

export interface Quiz {
    id: number;
    course_id: string;
    training_id?: string;
    title: string;
    instructions: string | null;
    time_limit: number | null;
    available_from: string;
    deadline: string;
    questions?: QuizQuestion[];
    training?: Training;
}

export interface AnswerResponse {
    id: number;
    answer_sheet_id: number;
    question_id: number;
    selected_option_id: number | null;
    text_answer: string | null;
}

export interface AnswerSheet {
    id: number;
    user_id: number;
    quiz_id: number;
    total_score: number | string | null;
    submitted_at: string | null;
    user?: User;
    quiz?: Quiz;
    responses?: AnswerResponse[];
}

export interface AppNotification {
    Notification_id: number;
    user_id: number;
    message: string;
    type: string;
    url: string | null;
    is_read: boolean;
    created_at: string;
    teacherID: string | null;
    gn_id: string | null;
    principalID: string | null;
}

export interface Feedback {
    id: number;
    user_id: number;
    course_id: string;
    training_id?: string;
    rating: number;
    comment: string | null;
    submitted_at: string;
    user?: User;
}

export interface HistoryRow {
    application_id: string;
    training_id: string | null;
    type: 'training' | 'penyelaras_ladap' | 'certificate';
    title: string;
    mode: string | null;
    course_category: string | null;
    course_type: 'lms' | 'simple' | null;
    organiserName: string | null;
    status: 'upcoming' | 'ongoing' | 'completed' | 'cancelled';
    present_count: number;
    total_sessions: number;
    attendance_rate: number | null;
    total_session_hours: number;
    ladap_hours: number;
    submitted_count?: number;
    total_tasks?: number;
    average_quiz_score?: number | null;
}

export interface DevelopmentOverviewRow {
    user: Pick<User, 'id' | 'name' | 'email'>;
    ic_number: string | null;
    completed_trainings: number;
    total_trainings: number;
    ladap_hours: number;
    attendance_rate: number | null;
}
