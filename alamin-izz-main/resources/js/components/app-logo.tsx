import { GraduationCap } from 'lucide-react';

export default function AppLogo() {
    return (
        <div className="flex items-center gap-2">
            <div className="flex size-8 items-center justify-center rounded-md bg-primary">
                <GraduationCap className="size-5 text-primary-foreground" />
            </div>
            <span className="text-lg font-bold text-primary">EduTrain</span>
        </div>
    );
}
