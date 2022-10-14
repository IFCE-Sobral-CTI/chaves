import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import React from "react";

function Index() {
    return (
        <>
            <AuthenticatedLayout breadcrumbs={[{label: 'Usuários', url: route('users.index')}]}>

            </AuthenticatedLayout>
        </>
    )
}

export default Index;
