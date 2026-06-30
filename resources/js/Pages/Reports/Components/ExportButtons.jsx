import React from "react";
import { useForm } from "@inertiajs/react";

export default function ExportButtons({ routeName, params }) {
    const handleCsv = () => {
        const url = new URL(route(routeName), window.location.origin);
        Object.entries(params).forEach(([key, value]) => {
            if (value !== null && value !== undefined && value !== '') {
                url.searchParams.set(key, value);
            }
        });
        url.searchParams.set('export_csv', '1');
        window.location.href = url.toString();
    };

    const handlePrint = () => {
        window.print();
    };

    return (
        <div className="flex items-center gap-2 print:hidden">
            <button
                type="button"
                onClick={handleCsv}
                className="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-white transition bg-emerald-600 rounded-lg hover:bg-emerald-700"
            >
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" className="w-4 h-4" viewBox="0 0 16 16">
                    <path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z"/>
                    <path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708l3 3z"/>
                </svg>
                CSV
            </button>
            <button
                type="button"
                onClick={handlePrint}
                className="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-neutral-700 transition bg-white border border-neutral-300 rounded-lg hover:bg-neutral-50"
            >
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" className="w-4 h-4" viewBox="0 0 16 16">
                    <path d="M2.5 8a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1z"/>
                    <path d="M5 1a2 2 0 0 0-2 2v2H2a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h1v1a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-1h1a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-1V3a2 2 0 0 0-2-2H5zM4 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2H4V3zm1 5a2 2 0 0 0-2 2v1H2a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v-1a2 2 0 0 0-2-2H5z"/>
                </svg>
                Imprimir
            </button>
        </div>
    );
}
