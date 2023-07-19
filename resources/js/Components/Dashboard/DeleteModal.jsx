import React, { useState, useRef, useEffect } from 'react';
import { useForm } from "@inertiajs/inertia-react";
import {v4 as uuidv4} from 'uuid';
import {
    Modal,
    initTE,
} from "tw-elements";

export default function DeleteModal({ url, forTable }) {
    const { delete: destroy, processing } = useForm();
    const [idRand] = useState(`modal-${uuidv4()}`);
    const [button, setButton] = useState(null);

    const teInitialized = useRef(false);

    useEffect(() => {
        setTimeout(() => {
            if (!teInitialized.current) {
              console.debug(`Tailwind Elements initialized`)
              initTE({ Modal })
            }
            teInitialized.current = true
          }, 500);

        if (forTable) {
            setButton(
                <button
                    type="button"
                    data-te-toggle="modal"
                    data-te-target={"#" + idRand}
                    className="inline-flex items-center gap-2 px-2 py-1 text-sm tracking-widest text-white transition duration-150 ease-in-out bg-red-500 border border-transparent rounded-md active:bg-red-700 hover:bg-red-600"
                    title="Apagar registro"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" className="w-3 h-3" role="img" aria-hidden="true" viewBox="0 0 16 16">
                        <path d="M6.5 1h3a.5.5 0 0 1 .5.5v1H6v-1a.5.5 0 0 1 .5-.5ZM11 2.5v-1A1.5 1.5 0 0 0 9.5 0h-3A1.5 1.5 0 0 0 5 1.5v1H2.506a.58.58 0 0 0-.01 0H1.5a.5.5 0 0 0 0 1h.538l.853 10.66A2 2 0 0 0 4.885 16h6.23a2 2 0 0 0 1.994-1.84l.853-10.66h.538a.5.5 0 0 0 0-1h-.995a.59.59 0 0 0-.01 0H11Zm1.958 1-.846 10.58a1 1 0 0 1-.997.92h-6.23a1 1 0 0 1-.997-.92L3.042 3.5h9.916Zm-7.487 1a.5.5 0 0 1 .528.47l.5 8.5a.5.5 0 0 1-.998.06L5 5.03a.5.5 0 0 1 .47-.53Zm5.058 0a.5.5 0 0 1 .47.53l-.5 8.5a.5.5 0 1 1-.998-.06l.5-8.5a.5.5 0 0 1 .528-.47ZM8 4.5a.5.5 0 0 1 .5.5v8.5a.5.5 0 0 1-1 0V5a.5.5 0 0 1 .5-.5Z"/>
                    </svg>
                </button>
            );
        } else {
            setButton(
                <button
                    type="button"
                    data-te-toggle="modal"
                    data-te-target={"#" + idRand}
                    className="inline-flex items-center gap-2 px-4 py-2 text-sm tracking-widest text-white transition duration-150 ease-in-out bg-red-500 border border-transparent rounded-md active:bg-red-700 hover:bg-red-600"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" className="w-5 h-5" role="img" aria-hidden="true" viewBox="0 0 16 16">
                        <path d="M6.5 1h3a.5.5 0 0 1 .5.5v1H6v-1a.5.5 0 0 1 .5-.5ZM11 2.5v-1A1.5 1.5 0 0 0 9.5 0h-3A1.5 1.5 0 0 0 5 1.5v1H2.506a.58.58 0 0 0-.01 0H1.5a.5.5 0 0 0 0 1h.538l.853 10.66A2 2 0 0 0 4.885 16h6.23a2 2 0 0 0 1.994-1.84l.853-10.66h.538a.5.5 0 0 0 0-1h-.995a.59.59 0 0 0-.01 0H11Zm1.958 1-.846 10.58a1 1 0 0 1-.997.92h-6.23a1 1 0 0 1-.997-.92L3.042 3.5h9.916Zm-7.487 1a.5.5 0 0 1 .528.47l.5 8.5a.5.5 0 0 1-.998.06L5 5.03a.5.5 0 0 1 .47-.53Zm5.058 0a.5.5 0 0 1 .47.53l-.5 8.5a.5.5 0 1 1-.998-.06l.5-8.5a.5.5 0 0 1 .528-.47ZM8 4.5a.5.5 0 0 1 .5.5v8.5a.5.5 0 0 1-1 0V5a.5.5 0 0 1 .5-.5Z"/>
                    </svg>
                    <span>Apagar</span>
                </button>
            );
        }
    }, []);

    const submit = (e) => {
        e.preventDefault();
        destroy(url, {
            preserveScroll: true
        });
    }

    return (
        <>
            {button}
            <div
                data-te-modal-init
                data-te-backdrop="static"
                data-te-keyboard="false"
                className="fixed top-0 left-0 z-[1055] hidden h-full w-full overflow-y-auto overflow-x-hidden outline-none"
                id={idRand}
                tabIndex="-1"
                aria-labelledby="modal-title"
                aria-modal="true"
                role="dialog"
            >
                <div
                    data-te-modal-dialog-ref
                    className="pointer-events-none relative flex min-h-[calc(100%-1rem)] w-auto translate-y-[-50px] items-center opacity-0 transition-all duration-300 ease-in-out min-[576px]:mx-auto min-[576px]:mt-7 min-[576px]:min-h-[calc(100%-3.5rem)] min-[576px]:max-w-[500px]"
                >
                    <div
                        className="relative flex flex-col w-full text-current bg-white border-none rounded-md shadow-lg outline-none pointer-events-auto md:w-3/6 lg:w-2/6 md:m-auto bg-clip-padding"
                    >
                    <div
                        className="flex items-center justify-between flex-shrink-0 p-4 border-b-2 border-opacity-100 rounded-t-md border-neutral-100"
                    >
                        <h5
                            className="text-xl font-medium leading-normal text-neutral-800"
                            id={idRand + "-label"}
                        >
                            Apagar registro
                        </h5>
                        <button
                            type="button"
                            className="box-content border-none rounded-none hover:no-underline hover:opacity-75 focus:opacity-100 focus:shadow-none focus:outline-none"
                            data-te-modal-dismiss
                            aria-label="Close"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                strokeWidth="1.5"
                                stroke="currentColor"
                                className="w-6 h-6">
                                <path
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                    d="M6 18L18 6M6 6l12 12"
                                />
                            </svg>
                        </button>
                    </div>
                    <div className="relative p-4">
                        <p>Deseja realmente excluir este registro?</p>
                    </div>
                    <div
                        className="flex flex-wrap items-center justify-end flex-shrink-0 p-4 border-t-2 border-opacity-100 rounded-b-md border-neutral-100"
                    >
                        <button
                            type="button"
                            className="flex items-center px-6 py-2.5 bg-gray-600 text-white font-light leading-tight rounded shadow-md hover:bg-gray-700 hover:shadow-lg focus:bg-gray-700 focus:shadow-lg focus:outline-none focus:ring-0 active:bg-gray-800 active:shadow-lg transition duration-150 ease-in-out"
                            data-te-modal-dismiss
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" className="w-5 h-5 mr-3" role="img" aria-hidden="true" viewBox="0 0 16 16">
                                <path d="M2.146 2.854a.5.5 0 1 1 .708-.708L8 7.293l5.146-5.147a.5.5 0 0 1 .708.708L8.707 8l5.147 5.146a.5.5 0 0 1-.708.708L8 8.707l-5.146 5.147a.5.5 0 0 1-.708-.708L7.293 8 2.146 2.854Z"/>
                            </svg>
                            <span>Não</span>
                        </button>
                        <form onSubmit={submit}>
                            <button
                                type="submit"
                                className="flex items-center px-6 py-2.5 bg-red-600 text-white font-light leading-tight rounded shadow-md hover:bg-red-700 hover:shadow-lg focus:bg-red-700 focus:shadow-lg focus:outline-none focus:ring-0 active:bg-red-800 active:shadow-lg transition duration-150 ease-in-out ml-1"
                                data-te-modal-dismiss
                                disabled={processing}
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" className="w-5 h-5 mr-3" role="img" aria-hidden="true" viewBox="0 0 16 16">
                                    <path d="M12.736 3.97a.733.733 0 0 1 1.047 0c.286.289.29.756.01 1.05L7.88 12.01a.733.733 0 0 1-1.065.02L3.217 8.384a.757.757 0 0 1 0-1.06.733.733 0 0 1 1.047 0l3.052 3.093 5.4-6.425a.247.247 0 0 1 .02-.022Z"/>
                                </svg>
                                <span>Sim</span>
                            </button>
                        </form>
                    </div>
                    </div>
                </div>
            </div>
        </>
    )
}
