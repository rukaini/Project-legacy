import AppLayout from '@/layouts/app-layout';
import { type PageProps, type Training } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, CheckCircle2, ClipboardList, FileText, Upload, X } from 'lucide-react';
import { useRef, useState } from 'react';

interface Question {
    question_id: number;
    question_text: string;
    question_type: 'mcq' | 'true_false' | 'short_answer';
    options_json: string[] | null;
    correct_answer: string | null;
    marks: number;
    question_order: number;
}

interface Assessment {
    assessment_id: number;
    type: string;
    title: string;
    instruction_text: string | null;
    available_until: string | null;
    total_marks: number | null;
    accepted_format: string | null;
    status: string;
    questions: Question[];
}

interface Submission {
    submission_id: number;
    grade: number | null;
    status: string;
    submitted_at: string;
    answers_json: { question_id: number; answer: string | null }[] | null;
    original_file_name: string | null;
    submitted_file_path: string | null;
    feedback: string | null;
}

interface Props extends PageProps {
    training: Training;
    assessment: Assessment;
    submission: Submission | null;
    enrollment_id: number | string;
}

function formatDeadline(iso: string | null): string {
    if (!iso) return '';
    return new Date(iso).toLocaleDateString('en-MY', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
}

export default function AssessmentShow({ training, assessment, submission }: Props) {
    const isQuiz   = assessment.type === 'quiz';
    const backHref = route('trainings.show', training.courseID ?? training.id) + '?from=my_courses';

    return (
        <AppLayout>
            <Head title={assessment.title} />
            <div className="mx-auto w-full max-w-3xl flex-1 px-6 py-8 sm:px-8">
                <Link href={backHref} className="mb-5 inline-flex items-center gap-1 text-sm text-gray-500 hover:text-indigo-700">
                    <ArrowLeft className="h-4 w-4" /> Back to Training
                </Link>

                <div className="mb-6 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                    <div className="flex items-start gap-3">
                        <div className={`flex h-10 w-10 items-center justify-center rounded-lg ${isQuiz ? 'bg-blue-100' : 'bg-amber-100'}`}>
                            {isQuiz
                                ? <ClipboardList className="h-5 w-5 text-blue-700" />
                                : <FileText className="h-5 w-5 text-amber-700" />}
                        </div>
                        <div className="flex-1">
                            <div className="flex items-center gap-2">
                                <span className={`rounded-full px-2.5 py-0.5 text-xs font-semibold ${isQuiz ? 'bg-blue-100 text-blue-700' : 'bg-amber-100 text-amber-700'}`}>
                                    {isQuiz ? 'Quiz' : 'Task'}
                                </span>
                                {assessment.total_marks && (
                                    <span className="text-xs text-gray-400">{assessment.total_marks} marks</span>
                                )}
                            </div>
                            <h1 className="mt-1 text-xl font-bold text-gray-900">{assessment.title}</h1>
                            {assessment.available_until && (
                                <p className="mt-0.5 text-xs text-gray-400">Due: {formatDeadline(assessment.available_until)}</p>
                            )}
                        </div>
                    </div>
                    {assessment.instruction_text && (
                        <p className="mt-4 whitespace-pre-line text-sm text-gray-700 border-t border-gray-100 pt-4">{assessment.instruction_text}</p>
                    )}
                </div>

                {submission ? (
                    <SubmissionResult assessment={assessment} submission={submission} />
                ) : isQuiz ? (
                    <QuizForm training={training} assessment={assessment} />
                ) : (
                    <TaskForm training={training} assessment={assessment} />
                )}
            </div>
        </AppLayout>
    );
}

function SubmissionResult({ assessment, submission }: { assessment: Assessment; submission: Submission }) {
    const isGraded = submission.status === 'graded';
    const isQuiz   = assessment.type === 'quiz';

    const answerMap = new Map(
        (submission.answers_json ?? []).map(a => [a.question_id, a.answer])
    );

    return (
        <div className="space-y-4">
            <div className={`rounded-xl border p-5 ${isGraded ? 'border-green-200 bg-green-50' : 'border-blue-200 bg-blue-50'}`}>
                <div className="flex items-center gap-3">
                    <CheckCircle2 className={`h-6 w-6 ${isGraded ? 'text-green-600' : 'text-blue-600'}`} />
                    <div>
                        <p className={`font-semibold ${isGraded ? 'text-green-800' : 'text-blue-800'}`}>
                            {isGraded ? 'Graded' : 'Submitted — Awaiting Review'}
                        </p>
                        <p className="text-xs text-gray-500">
                            Submitted on {new Date(submission.submitted_at).toLocaleDateString('en-MY', { day: '2-digit', month: 'short', year: 'numeric' })}
                        </p>
                    </div>
                    {isGraded && submission.grade !== null && (
                        <div className="ml-auto text-right">
                            <p className="text-2xl font-bold text-green-700">{submission.grade}</p>
                            {assessment.total_marks && (
                                <p className="text-xs text-gray-500">/ {assessment.total_marks}</p>
                            )}
                        </div>
                    )}
                </div>
                {submission.feedback && (
                    <p className="mt-3 rounded-lg border border-green-200 bg-white p-3 text-sm text-gray-700">
                        <span className="font-medium text-gray-500">Feedback: </span>{submission.feedback}
                    </p>
                )}
            </div>

            {/* Show submitted answers for quiz */}
            {isQuiz && assessment.questions.length > 0 && (
                <div className="space-y-3">
                    <h3 className="text-sm font-semibold uppercase tracking-wide text-gray-500">Your Answers</h3>
                    {assessment.questions.map((q, i) => {
                        const given = answerMap.get(q.question_id) ?? null;
                        const isCorrect = q.question_type !== 'short_answer' && q.correct_answer
                            ? strmatch(given, q.correct_answer) : null;
                        return (
                            <div key={q.question_id} className="rounded-xl border border-gray-200 bg-white p-5">
                                <div className="flex items-start justify-between gap-2">
                                    <p className="text-sm font-medium text-gray-900">
                                        <span className="mr-2 text-gray-400">{i + 1}.</span>{q.question_text}
                                    </p>
                                    {isCorrect !== null && (
                                        <span className={`flex-shrink-0 rounded-full px-2 py-0.5 text-xs font-semibold ${isCorrect ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'}`}>
                                            {isCorrect ? `+${q.marks}` : '0'}
                                        </span>
                                    )}
                                </div>
                                <div className="mt-3 text-sm">
                                    {q.question_type === 'short_answer' ? (
                                        <p className="rounded-lg border border-gray-100 bg-gray-50 p-3 text-gray-700">{given ?? '—'}</p>
                                    ) : (
                                        <div className="space-y-1.5">
                                            {(q.options_json ?? ['True', 'False']).map((optText, idx) => {
                                                const isSelected = strmatch(given, optText);
                                                const isCorrectOpt = strmatch(q.correct_answer, optText);
                                                const label = q.question_type !== 'true_false'
                                                    ? (['A', 'B', 'C', 'D'][idx] ?? String(idx + 1))
                                                    : null;
                                                return (
                                                    <div key={optText} className={`flex items-center gap-2 rounded-lg px-3 py-2 text-sm ${isCorrectOpt ? 'border border-green-300 bg-green-50' : isSelected ? 'border border-red-300 bg-red-50' : 'border border-gray-100 bg-gray-50'}`}>
                                                        {label && <span className="w-5 font-mono text-xs text-gray-500">{label}</span>}
                                                        <span className={isCorrectOpt ? 'text-green-800' : isSelected ? 'text-red-700' : 'text-gray-700'}>{optText}</span>
                                                    </div>
                                                );
                                            })}
                                        </div>
                                    )}
                                </div>
                            </div>
                        );
                    })}
                </div>
            )}

            {/* File submitted */}
            {!isQuiz && submission.original_file_name && (
                <div className="rounded-xl border border-gray-200 bg-white p-5">
                    <h3 className="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-500">Submitted File</h3>
                    <div className="flex items-center gap-3 rounded-lg border border-gray-100 bg-gray-50 px-4 py-3">
                        <FileText className="h-5 w-5 text-gray-400" />
                        <span className="text-sm text-gray-700">{submission.original_file_name}</span>
                    </div>
                </div>
            )}
        </div>
    );
}

function strmatch(a: string | null | undefined, b: string | null | undefined): boolean {
    return (a ?? '').toLowerCase() === (b ?? '').toLowerCase();
}

function QuizForm({ training, assessment }: { training: Training; assessment: Assessment }) {
    const trainingId = training.courseID ?? training.id;
    const [answers, setAnswers]   = useState<Record<number, string>>({});
    const [submitting, setSubmit] = useState(false);

    const answered = Object.keys(answers).length;
    const total    = assessment.questions.length;

    function setAnswer(qId: number, val: string) {
        setAnswers(prev => ({ ...prev, [qId]: val }));
    }

    function submit(e: React.FormEvent) {
        e.preventDefault();
        setSubmit(true);
        router.post(
            route('assessments.store', { training: trainingId, assessment: assessment.assessment_id }),
            { answers },
            { onFinish: () => setSubmit(false) }
        );
    }

    return (
        <form onSubmit={submit} className="space-y-4">
            {/* Progress */}
            <div className="rounded-xl border border-gray-200 bg-white p-4">
                <div className="flex items-center justify-between text-sm">
                    <span className="text-gray-600">{answered} of {total} answered</span>
                    <span className="font-semibold text-indigo-700">{total > 0 ? Math.round((answered / total) * 100) : 0}%</span>
                </div>
                <div className="mt-2 h-1.5 w-full rounded-full bg-gray-100">
                    <div className="h-1.5 rounded-full bg-indigo-500 transition-all" style={{ width: `${total > 0 ? (answered / total) * 100 : 0}%` }} />
                </div>
            </div>

            {assessment.questions.map((q, i) => (
                <div key={q.question_id} className="rounded-xl border border-gray-200 bg-white p-5">
                    <div className="mb-4 flex items-start gap-3">
                        <span className="flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-full bg-indigo-50 text-xs font-bold text-indigo-700">{i + 1}</span>
                        <div className="flex-1">
                            <p className="text-sm font-medium text-gray-900">{q.question_text}</p>
                            <p className="mt-0.5 text-xs text-gray-400">{q.marks} mark{Number(q.marks) !== 1 ? 's' : ''}</p>
                        </div>
                        {answers[q.question_id] && (
                            <span className="flex-shrink-0 rounded-full bg-green-100 px-2 py-0.5 text-xs font-semibold text-green-700">Answered</span>
                        )}
                    </div>

                    {q.question_type === 'short_answer' ? (
                        <textarea
                            className="w-full rounded-lg border border-gray-200 p-3 text-sm text-gray-900 focus:border-indigo-400 focus:outline-none focus:ring-1 focus:ring-indigo-400"
                            rows={4}
                            placeholder="Write your answer here..."
                            value={answers[q.question_id] ?? ''}
                            onChange={e => setAnswer(q.question_id, e.target.value)}
                        />
                    ) : (
                        <div className="space-y-2">
                            {(q.options_json ?? ['True', 'False']).map((optText, idx) => {
                                const selected = answers[q.question_id] === optText;
                                const label = q.question_type !== 'true_false'
                                    ? (['A', 'B', 'C', 'D'][idx] ?? String(idx + 1))
                                    : null;
                                return (
                                    <label key={optText} className={`flex cursor-pointer items-center gap-3 rounded-lg border px-4 py-3 transition-colors ${selected ? 'border-indigo-400 bg-indigo-50' : 'border-gray-200 bg-gray-50 hover:border-indigo-200 hover:bg-indigo-50/40'}`}>
                                        <input
                                            type="radio"
                                            name={`q_${q.question_id}`}
                                            value={optText}
                                            checked={selected}
                                            onChange={() => setAnswer(q.question_id, optText)}
                                            className="h-4 w-4 accent-indigo-600"
                                        />
                                        {label && <span className="w-5 text-xs font-mono font-semibold text-gray-500">{label}</span>}
                                        <span className="text-sm text-gray-700">{optText}</span>
                                    </label>
                                );
                            })}
                        </div>
                    )}
                </div>
            ))}

            <div className="flex justify-end pt-2">
                <button
                    type="submit"
                    disabled={submitting}
                    className="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-6 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-60"
                >
                    {submitting ? 'Submitting…' : 'Submit Quiz'}
                </button>
            </div>
        </form>
    );
}

function TaskForm({ training, assessment }: { training: Training; assessment: Assessment }) {
    const trainingId             = training.courseID ?? training.id;
    const fileRef                = useRef<HTMLInputElement>(null);
    const [file, setFile]        = useState<File | null>(null);
    const [text, setText]        = useState('');
    const [dragging, setDragging]= useState(false);
    const [submitting, setSubmit]= useState(false);

    function handleDrop(e: React.DragEvent) {
        e.preventDefault();
        setDragging(false);
        const f = e.dataTransfer.files[0];
        if (f) setFile(f);
    }

    function submit(e: React.FormEvent) {
        e.preventDefault();
        if (!file && !text.trim()) return;
        setSubmit(true);
        const form = new FormData();
        if (file) form.append('file', file);
        if (text) form.append('text_answer', text);
        router.post(
            route('assessments.store', { training: trainingId, assessment: assessment.assessment_id }),
            form,
            { onFinish: () => setSubmit(false), forceFormData: true }
        );
    }

    return (
        <form onSubmit={submit} className="space-y-4">
            {/* File upload */}
            <div className="rounded-xl border border-gray-200 bg-white p-5">
                <h3 className="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-500">Upload File</h3>
                {assessment.accepted_format && (
                    <p className="mb-3 text-xs text-gray-400">Accepted: {assessment.accepted_format}</p>
                )}

                {file ? (
                    <div className="flex items-center justify-between rounded-lg border border-indigo-200 bg-indigo-50 px-4 py-3">
                        <div className="flex items-center gap-2">
                            <FileText className="h-5 w-5 text-indigo-500" />
                            <span className="text-sm font-medium text-indigo-700">{file.name}</span>
                            <span className="text-xs text-gray-400">({(file.size / 1024).toFixed(0)} KB)</span>
                        </div>
                        <button type="button" onClick={() => setFile(null)} className="text-gray-400 hover:text-red-500">
                            <X className="h-4 w-4" />
                        </button>
                    </div>
                ) : (
                    <div
                        onDragOver={e => { e.preventDefault(); setDragging(true); }}
                        onDragLeave={() => setDragging(false)}
                        onDrop={handleDrop}
                        onClick={() => fileRef.current?.click()}
                        className={`flex cursor-pointer flex-col items-center gap-3 rounded-xl border-2 border-dashed p-8 transition-colors ${dragging ? 'border-indigo-400 bg-indigo-50' : 'border-gray-200 hover:border-indigo-300 hover:bg-indigo-50/40'}`}
                    >
                        <Upload className={`h-8 w-8 ${dragging ? 'text-indigo-500' : 'text-gray-300'}`} />
                        <p className="text-sm text-gray-500">Drop file here or <span className="font-medium text-indigo-600">browse</span></p>
                    </div>
                )}
                <input
                    ref={fileRef}
                    type="file"
                    className="hidden"
                    accept={assessment.accepted_format ?? undefined}
                    onChange={e => { const f = e.target.files?.[0]; if (f) setFile(f); }}
                />
            </div>

            {/* Text answer */}
            <div className="rounded-xl border border-gray-200 bg-white p-5">
                <h3 className="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-500">Text Answer <span className="font-normal normal-case text-gray-400">(optional)</span></h3>
                <textarea
                    className="w-full rounded-lg border border-gray-200 p-3 text-sm text-gray-900 focus:border-indigo-400 focus:outline-none focus:ring-1 focus:ring-indigo-400"
                    rows={5}
                    placeholder="Write any additional notes or answers here..."
                    value={text}
                    onChange={e => setText(e.target.value)}
                />
            </div>

            <div className="flex justify-end pt-2">
                <button
                    type="submit"
                    disabled={submitting || (!file && !text.trim())}
                    className="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-6 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-60"
                >
                    {submitting ? 'Submitting…' : 'Submit Task'}
                </button>
            </div>
        </form>
    );
}
