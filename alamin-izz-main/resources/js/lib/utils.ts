import { type ClassValue, clsx } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

// training.mode is stored lowercase ('online' | 'physical' | 'hybrid'); display it capitalized.
export function formatMode(mode: string | null | undefined): string | null {
    if (!mode) return null;
    return mode.charAt(0).toUpperCase() + mode.slice(1);
}
