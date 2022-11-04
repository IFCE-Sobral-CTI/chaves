import AlertContext from "@/Context/AlertContext";
import React, { useContext, useEffect, useState } from "react";

function Alert() {
    const { alert, setAlert, type, message } = useContext(AlertContext);

    let textColor = '';
    let bgColor = '';
    let borderColor = ''

    useEffect(() => {
        const debounce = setTimeout(() => {
            setAlert(false);
        }, 6000);

        return () => clearTimeout(debounce);
    }, [alert]);

    switch (type) {
        case 'success':
            textColor = " text-white";
            bgColor = " bg-green";
            borderColor = " border-white";
            break;
        case 'danger':
            textColor = " text-white";
            bgColor = " bg-red-500";
            borderColor = " border-white";
            break;
        case 'warning':
            textColor = " text-white";
            bgColor = " bg-yellow-500";
            borderColor = " border-white";
            break;
        case 'info':
            textColor = " text-white";
            bgColor = " bg-sky-500";
            borderColor = " border-white";
            break;
        default:
            textColor = ' text-grey-700';
            bgColor = ' bg-neutral-200';
            borderColor = ' border-neutral-500'
    }

    const handleClose = () => {
        setAlert(false);
    }

    return (
        <div className={"absolute top-32 md:top-20 z-20 left-1/2 m-auto w-full md:w-1/3 transform -translate-x-1/2 px-4 transition " + (alert? 'fadeIn': 'fadeOut')}>
            <div className={"relative flex gap-2 justify-between z-20 w-full p-2 rounded-lg shadow-md " + bgColor + ' ' + textColor}>
                <div className="flex items-center p-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" className="h-8 w-8" viewBox="0 0 16 16">
                        <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                        <path d="M7.002 11a1 1 0 1 1 2 0 1 1 0 0 1-2 0zM7.1 4.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 4.995z"/>
                    </svg>
                </div>
                <div className="flex-1">
                    <h2 className={"text-xl border-b " + borderColor}>Atenção!</h2>
                    <p className="font-light">{message?? 'Nenhuma mensagem.'}</p>
                </div>
                <div>
                    <button onClick={handleClose}>
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" className="h-4 w-4" viewBox="0 0 16 16">
                            <path d="M2.146 2.854a.5.5 0 1 1 .708-.708L8 7.293l5.146-5.147a.5.5 0 0 1 .708.708L8.707 8l5.147 5.146a.5.5 0 0 1-.708.708L8 8.707l-5.146 5.147a.5.5 0 0 1-.708-.708L7.293 8 2.146 2.854Z"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    )
}

export default Alert;
