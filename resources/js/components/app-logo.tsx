import { type SharedData } from '@/types';
import { usePage } from '@inertiajs/react';

export default function AppLogo() {
    const { name } = usePage<SharedData>().props;

    return (
        <div className="flex min-w-0 flex-1 items-center">
            <img src="/images/brand/logo-salero-basamo-jati.png" alt={name} className="h-8 w-full max-w-full object-contain" draggable={false} />
        </div>
    );
}
