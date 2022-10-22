import 'tw-elements';
import React, { useEffect, useState } from 'react';
import {Head, Link, useForm, usePage} from '@inertiajs/inertia-react';
import Footer from '@/Components/Public/Footer';
import Sidebar from '@/Components/Dashboard/Sidebar';
import Breadcrumbs from '@/Components/Dashboard/Breadcrumbs';
import Panel from '@/Components/Dashboard/Panel';
import Alert from '@/Components/Dashboard/Alert';
import AlertContext from '@/Context/AlertContext';
import Navbar from "@/Components/Dashboard/Navbar";

export default function AuthenticatedLayout({breadcrumbs, children, titleChildren}) {
    const { title, flash, auth, authorizations } = usePage().props;
    const [alert, setAlert] = useState(false);
    const [message, setMessage] = useState(flash?.flash?.message);
    const [type, setType] = useState(flash?.flash?.status);
    const { post } = useForm();

    const onHandleLogout = () => {
        post(route('logout'));
    }

    useEffect(() => {
        if (flash?.flash)
            setAlert(true);
    }, [flash]);

    return (
        <>
            <AlertContext.Provider value={{ alert, setAlert, type, setType, message, setMessage }}>
                <Alert />
            </AlertContext.Provider>
            <Head title='Dashboard' />
            <div className="flex flex-col min-h-screen max-w-screen bg-neutral-100">
                <Navbar title={title} />
                <div className="flex flex-1">
                    <Sidebar can={authorizations} />
                    <main className="flex flex-col gap-2 md:gap-4 w-full px-0.5 md:pr-2 py-2 md:py-4">
                        {breadcrumbs && <Breadcrumbs href={breadcrumbs} />}
                        {titleChildren && <Panel><h1 className="text-xl font-semibold text-center text-neutral-500">{titleChildren}</h1></Panel>}
                        {children}
                    </main>
                </div>
                <Footer />
            </div>
        </>
    );
}
