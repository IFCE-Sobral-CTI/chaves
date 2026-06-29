import React from 'react';
import Panel from './Panel';
import { ResponsiveContainer } from 'recharts';

export default function ChartCard({ title, children, className = '' }) {
    return (
        <Panel className={`flex flex-col ${className}`}>
            <h2 className="text-sm font-semibold text-gray-700 mb-2">{title}</h2>
            <div className="flex-1 min-h-64">
                {children ? (
                    <ResponsiveContainer width="100%" height="100%">
                        {children}
                    </ResponsiveContainer>
                ) : (
                    <div className="w-full h-full flex items-center justify-center text-gray-400 text-sm">
                        Sem dados disponíveis
                    </div>
                )}
            </div>
        </Panel>
    );
}
