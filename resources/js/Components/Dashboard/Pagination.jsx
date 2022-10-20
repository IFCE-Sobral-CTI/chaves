import { Link } from "@inertiajs/inertia-react";
import React from "react";

function Pagination({data, count}) {
    return (
        <nav className="flex flex-col items-end justify-end gap-2 pt-6">
            <ul className="flex gap-4 text-gray-500">
                <li className="">
                    {data.prev_page_url
                    ?(<Link href={data.prev_page_url} className="flex justify-between items-center gap-2 border shadow-md rounded-lg p-2 hover:bg-gray-50 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" className="h-5 w-5" viewBox="0 0 16 16">
                            <path fillRule="evenodd" d="M8.354 1.646a.5.5 0 0 1 0 .708L2.707 8l5.647 5.646a.5.5 0 0 1-.708.708l-6-6a.5.5 0 0 1 0-.708l6-6a.5.5 0 0 1 .708 0z"/>
                            <path fillRule="evenodd" d="M12.354 1.646a.5.5 0 0 1 0 .708L6.707 8l5.647 5.646a.5.5 0 0 1-.708.708l-6-6a.5.5 0 0 1 0-.708l6-6a.5.5 0 0 1 .708 0z"/>
                        </svg>
                        Anterior
                    </Link>)
                    :(<button type="button" className="flex justify-between items-center gap-2 text-gray-300 border shadow-md rounded-lg p-2 transition cursor-auto">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" className="h-5 w-5" viewBox="0 0 16 16">
                            <path fillRule="evenodd" d="M8.354 1.646a.5.5 0 0 1 0 .708L2.707 8l5.647 5.646a.5.5 0 0 1-.708.708l-6-6a.5.5 0 0 1 0-.708l6-6a.5.5 0 0 1 .708 0z"/>
                            <path fillRule="evenodd" d="M12.354 1.646a.5.5 0 0 1 0 .708L6.707 8l5.647 5.646a.5.5 0 0 1-.708.708l-6-6a.5.5 0 0 1 0-.708l6-6a.5.5 0 0 1 .708 0z"/>
                        </svg>
                        Anterior
                    </button>)}
                </li>
                <li className="">
                    {data.next_page_url
                    ?(<Link href={data.next_page_url} className="flex justify-between items-center gap-2 border shadow-md rounded-lg p-2 hover:bg-gray-50 transition">
                        Próxima
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" className="h-5 w-5" viewBox="0 0 16 16">
                            <path fillRule="evenodd" d="M3.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L9.293 8 3.646 2.354a.5.5 0 0 1 0-.708z"/>
                            <path fillRule="evenodd" d="M7.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L13.293 8 7.646 2.354a.5.5 0 0 1 0-.708z"/>
                        </svg>
                    </Link>)
                    :(<button type="button" className="flex justify-between items-center gap-2 text-gray-300 border shadow-md rounded-lg p-2 transition cursor-auto">
                        Próxima
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" className="h-5 w-5" viewBox="0 0 16 16">
                            <path fillRule="evenodd" d="M3.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L9.293 8 3.646 2.354a.5.5 0 0 1 0-.708z"/>
                            <path fillRule="evenodd" d="M7.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L13.293 8 7.646 2.354a.5.5 0 0 1 0-.708z"/>
                        </svg>
                    </button>)}
                </li>
            </ul>
            <p className="flex items-end text-gray-500 text-xs font-light">Total de registros<strong className="font-semibold">&nbsp;{count}&nbsp;</strong> - Página<strong className="font-semibold">&nbsp;{data.current_page}&nbsp;</strong>de<strong className="font-semibold">&nbsp;{data.last_page}</strong>.</p>
        </nav>
    )
}

export default Pagination;
