import React from 'react';
import ApplicationLogo from '@/Components/ApplicationLogo';
import { Link, usePage } from '@inertiajs/inertia-react';

export default function Guest({ children }) {
    const { title } = usePage().props;
    return (
        <div className="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100">
            <div className='w-full sm:max-w-md'>
                <h1 className='text-4xl text-center text-green font-extrabold'>
                    {title}
                </h1>
            </div>
            <div className="w-full sm:max-w-md mt-10 px-6 py-4 bg-white shadow-md overflow-hidden sm:rounded-lg">
                {children}
            </div>
            <div className='mt-10'>
                <ApplicationLogo className="h-16 fill-current" />
            </div>
            <div className="w-full sm:max-w-md flex justify-center items-center mt-8 text-neutral-400">
                <Link href="#" className='flex-1 underline border-r border-neutral-400 pr-6 text-right'>Fale Conosco</Link>
                <Link href="#" className='flex-1 underline pl-6'>FAQ</Link>
            </div>
        </div>
    );
}
