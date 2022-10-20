import { useForm } from "@inertiajs/inertia-react";
import 'tw-elements';

export default function DeleteModal({ url }) {
    const { delete: destroy, processing } = useForm();

    const submit = (e) => {
        e.preventDefault();
        destroy(url, {
            preserveScroll: true
        });
    }

    return (
        <>
            <button
                type="button"
                data-bs-toggle="modal"
                data-bs-target="#delete-modal"
                className="inline-flex items-center gap-2 px-4 py-2 border border-transparent rounded-md text-sm text-white tracking-widest transition ease-in-out duration-150 bg-red-500 active:bg-red-700 hover:bg-red-600"
            >
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" className="w-5 h-5" role="img" aria-hidden="true" viewBox="0 0 16 16">
                    <path d="M6.5 1h3a.5.5 0 0 1 .5.5v1H6v-1a.5.5 0 0 1 .5-.5ZM11 2.5v-1A1.5 1.5 0 0 0 9.5 0h-3A1.5 1.5 0 0 0 5 1.5v1H2.506a.58.58 0 0 0-.01 0H1.5a.5.5 0 0 0 0 1h.538l.853 10.66A2 2 0 0 0 4.885 16h6.23a2 2 0 0 0 1.994-1.84l.853-10.66h.538a.5.5 0 0 0 0-1h-.995a.59.59 0 0 0-.01 0H11Zm1.958 1-.846 10.58a1 1 0 0 1-.997.92h-6.23a1 1 0 0 1-.997-.92L3.042 3.5h9.916Zm-7.487 1a.5.5 0 0 1 .528.47l.5 8.5a.5.5 0 0 1-.998.06L5 5.03a.5.5 0 0 1 .47-.53Zm5.058 0a.5.5 0 0 1 .47.53l-.5 8.5a.5.5 0 1 1-.998-.06l.5-8.5a.5.5 0 0 1 .528-.47ZM8 4.5a.5.5 0 0 1 .5.5v8.5a.5.5 0 0 1-1 0V5a.5.5 0 0 1 .5-.5Z"/>
                </svg>
                <span>Apagar</span>
            </button>
            <div className="fixed top-0 left-0 hidden w-full h-full overflow-x-hidden overflow-y-auto outline-none modal fade" id="delete-modal" data-bs-backdrop="static" data-bs-keyboard="false" tabIndex={-1} aria-labelledby="staticBackdropLabel" aria-hidden="true">
                <div className="relative w-auto pointer-events-none modal-dialog modal-dialog-centered">
                    <div className="relative flex flex-col w-full text-current bg-white border-none rounded-md shadow-lg outline-none pointer-events-auto modal-content bg-clip-padding">
                        <div className="flex items-center justify-between flex-shrink-0 p-4 border-b border-gray-200 modal-header rounded-t-md">
                            <h5 className="text-xl font-medium leading-normal text-gray-800" id="exampleModalLabel">
                                Apagar registro
                            </h5>
                            <button type="button" className="box-content w-4 h-4 p-1 text-black border-none rounded-none opacity-50 btn-close focus:shadow-none focus:outline-none focus:opacity-100 hover:text-black hover:opacity-75 hover:no-underline" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div className="relative p-4 modal-body">
                            Deseja realmente excluir este registro?
                        </div>
                        <div className="flex flex-wrap items-center justify-end flex-shrink-0 p-4 border-t border-gray-200 modal-footer rounded-b-md">
                            <button type="button" className="flex items-center px-6 py-2.5 bg-gray-600 text-white font-light leading-tight rounded shadow-md hover:bg-gray-700 hover:shadow-lg focus:bg-gray-700 focus:shadow-lg focus:outline-none focus:ring-0 active:bg-gray-800 active:shadow-lg transition duration-150 ease-in-out" data-bs-dismiss="modal">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" className="w-5 h-5 mr-3" role="img" aria-hidden="true" viewBox="0 0 16 16">
                                    <path d="M2.146 2.854a.5.5 0 1 1 .708-.708L8 7.293l5.146-5.147a.5.5 0 0 1 .708.708L8.707 8l5.147 5.146a.5.5 0 0 1-.708.708L8 8.707l-5.146 5.147a.5.5 0 0 1-.708-.708L7.293 8 2.146 2.854Z"/>
                                </svg>
                                <span>Não</span>
                            </button>
                            <form onSubmit={submit}>
                                <button
                                    type="submit"
                                    data-bs-dismiss="modal"
                                    className="flex items-centerk px-6 py-2.5 bg-red-600 text-white font-light leading-tight rounded shadow-md hover:bg-red-700 hover:shadow-lg focus:bg-red-700 focus:shadow-lg focus:outline-none focus:ring-0 active:bg-red-800 active:shadow-lg transition duration-150 ease-in-out ml-1"
                                    disabled={processing}
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" className="w-5 h-5 mr-3" role="img" aria-hidden="true" viewBox="0 0 16 16">
                                        <path d="M12.736 3.97a.733.733 0 0 1 1.047 0c.286.289.29.756.01 1.05L7.88 12.01a.733.733 0 0 1-1.065.02L3.217 8.384a.757.757 0 0 1 0-1.06.733.733 0 0 1 1.047 0l3.052 3.093 5.4-6.425a.247.247 0 0 1 .02-.022Z"/>
                                    </svg>
                                    Sim
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </>
    )
}
