import { GraduationCap } from 'lucide-react';

interface AuthLayoutProps {
    children: React.ReactNode;
    title?: string;
    description?: string;
}

export default function AuthSimpleLayout({ children, title, description }: AuthLayoutProps) {
    return (
        <div className="flex min-h-screen">
            {/* Left branding panel */}
            <div className="hidden lg:flex lg:w-1/2 flex-col justify-between bg-blue-700 px-12 py-12 text-white relative overflow-hidden">
                {/* Decorative circles */}
                <div className="absolute -top-24 -left-24 h-96 w-96 rounded-full bg-blue-600 opacity-50" />
                <div className="absolute -bottom-32 -right-16 h-[28rem] w-[28rem] rounded-full bg-blue-800 opacity-60" />
                <div className="absolute top-1/2 left-1/3 h-48 w-48 rounded-full bg-blue-500 opacity-20" />

                {/* Logo */}
                <div className="relative z-10 flex items-center gap-3">
                    <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-white/20">
                        <GraduationCap className="h-6 w-6 text-white" />
                    </div>
                    <span className="text-xl font-bold tracking-tight">EduTrain</span>
                </div>

                {/* Centre text */}
                <div className="relative z-10">
                    <h2 className="text-4xl font-bold leading-tight">
                        Educator Training<br />Management System
                    </h2>
                    <p className="mt-4 text-lg text-blue-200">
                        Al Amin Edu Oasis
                    </p>
                </div>

                <p className="relative z-10 text-xs text-blue-300">
                    © {new Date().getFullYear()} Al Amin Edu Oasis. All rights reserved.
                </p>
            </div>

            {/* Right form panel */}
            <div className="flex flex-1 flex-col items-center justify-center bg-gray-50 px-6 py-12 sm:px-12">
                {/* Mobile-only logo */}
                <div className="mb-8 flex items-center gap-2 lg:hidden">
                    <div className="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-700">
                        <GraduationCap className="h-5 w-5 text-white" />
                    </div>
                    <span className="text-lg font-bold text-gray-900">EduTrain</span>
                </div>

                <div className="w-full max-w-sm">
                    <div className="mb-8">
                        <h1 className="text-2xl font-bold text-gray-900">{title}</h1>
                        {description && (
                            <p className="mt-1 text-sm text-gray-500">{description}</p>
                        )}
                    </div>

                    {children}
                </div>

                <p className="mt-8 text-xs text-gray-400 lg:hidden">
                    © {new Date().getFullYear()} Al Amin Edu Oasis
                </p>
            </div>
        </div>
    );
}
