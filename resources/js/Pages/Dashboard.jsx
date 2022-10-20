import Panel from '@/Components/Dashboard/Panel';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import React from 'react';
import 'tw-elements';

export default function Dashboard({breadcrumbs, children}) {
    return (
        <>
            <AuthenticatedLayout breadcrumbs={[{label: 'Minha Página', url: route('admin')}]}>
                <Panel>Teste</Panel>
            </AuthenticatedLayout>
        </>
    )
}
