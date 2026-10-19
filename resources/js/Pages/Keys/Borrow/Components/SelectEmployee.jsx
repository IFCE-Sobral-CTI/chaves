import InputError from "@/Components/InputError";
import React, { useEffect, useRef, useState } from "react";

function SelectEmployee({ data, endpoint, onChange, value, error }) {
    const [toggle, setToggle] = useState(false);
    const [term, setTerm] = useState('');
    const [list, setList] = useState(data);
    const [page, setPage] = useState(1);
    const [lastPage, setLastPage] = useState(1);
    const [loadingMore, setLoadingMore] = useState(false);
    const [selected, setSelected] = useState(() => data.find(item => String(item.id) === String(value)) ?? {id: null, registry: null, name: null});
    const ref = useRef(null);
    const listRef = useRef(null);
    const loadMoreRef = useRef(null);
    const loadingMoreRef = useRef(false);
    const moreController = useRef(null);

    useEffect(() => {
        setList(data);
    }, [data]);

    useEffect(() => {
        if (!toggle || !endpoint) return;
        const controller = new AbortController();
        const timer = setTimeout(async () => {
            try {
                const params = new URLSearchParams({ term, page: '1' });
                const response = await fetch(`${endpoint}?${params}`, { signal: controller.signal, headers: { Accept: 'application/json' } });
                if (!response.ok) {
                    setList([]);
                    return;
                }
                const result = await response.json();
                setList(result.data);
                setPage(result.current_page);
                setLastPage(result.last_page);
            } catch (error) {
                if (error.name !== 'AbortError') setList([]);
            }
        }, 250);
        return () => { clearTimeout(timer); controller.abort(); moreController.current?.abort(); };
    }, [term, toggle, endpoint]);

    useEffect(() => {
        document.addEventListener('click', handleClickOutside, true);
        return () => document.removeEventListener('click', handleClickOutside, true);
    }, []);

    const handleSelect = (event) => {
        const employee = list.find(item => String(item.id) === event.currentTarget.value);
        if (employee) {
            setSelected(employee);
            onChange(employee.id);
        }
        toggleHandle();
    }

    const loadMore = async () => {
        if (loadingMoreRef.current || page >= lastPage) return;

        loadingMoreRef.current = true;
        setLoadingMore(true);
        const params = new URLSearchParams({ term, page: String(page + 1) });
        moreController.current?.abort();
        moreController.current = new AbortController();
        try {
            const response = await fetch(`${endpoint}?${params}`, { signal: moreController.current.signal, headers: { Accept: 'application/json' } });
            if (!response.ok) return;
            const result = await response.json();
            setList(items => [...items, ...result.data]);
            setPage(result.current_page);
            setLastPage(result.last_page);
        } catch (error) {
            if (error.name !== 'AbortError') setList([]);
        } finally {
            loadingMoreRef.current = false;
            setLoadingMore(false);
        }
    };

    useEffect(() => {
        const root = listRef.current;
        const target = loadMoreRef.current;
        if (!toggle || !root || !target || page >= lastPage || loadingMore) return;

        const observer = new IntersectionObserver(([entry]) => {
            if (entry.isIntersecting) loadMore();
        }, { root });

        observer.observe(target);
        return () => observer.disconnect();
    }, [toggle, page, lastPage, loadingMore, term, endpoint]);

    const items = list.map((item) => {
        if (selected?.id !== item.id)
            return (
                <li key={item.id}>
                    <button type="button" className="w-full px-2 py-1 text-left rounded-lg transition cursor-pointer font-light hover:bg-neutral-100" onClick={handleSelect} value={item.id}>{item.registry} - {item.name}{item.usual_name ? ` (${item.usual_name})` : ''}</button>
                </li>
            );
    });

    const toggleHandle = () => {
        setToggle(!toggle);
    };

    const handleClickOutside = (event) => {
        if (ref.current && !ref.current.contains(event.target)) {
            setToggle(false);
            setTerm('');
        }
    }

    const inputId = 'select-employee-borrow';

    return (
        <div className="w-full mb-4 relative">
            <label htmlFor={inputId} className="font-light">Mutuário</label>
            <div className="flex border border-neutral-400 rounded-lg p-2" onClick={toggleHandle}>
                <div className="flex-1">
                    {selected?.id != null
                    ?<span className="text-neutral-700 font-normal">{selected.registry} - {selected.name}{selected.usual_name ? ` (${selected.usual_name})` : ''}</span>
                    :<span className="text-neutral-500">Nenhum mutuário selecionado</span>}
                </div>
                <div className="flex justify-center items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" className="h-5 w-5" viewBox="0 0 16 16">
                        <path fillRule="evenodd" d="M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z"/>
                    </svg>
                </div>
            </div>
            <InputError message={error} />
            <div className={'absolute z-50 w-full transition ' + (toggle? 'block': 'hidden')} ref={ref}>
                <div className="">
                        <input id={inputId} type="text" value={term} onChange={e => setTerm(e.target.value)} className="w-full rounded-t-lg mt-0.5 border-neutral-400 focus:ring-0 focus:border-neutral-500" placeholder="Digite sua pesquisa" />
                </div>
                <div ref={listRef} className="max-h-32 p-1 shadow-md rounded-b-lg border-b border-l border-r border-neutral-400 bg-white overflow-auto scrollbar-thumb-gray-400 scrollbar-track-gray-100 scrollbar-thin scrollbar-thumb-rounded-full scrollbar-track-rounded-ful">
                    <ul>
                        {items}
                        {page < lastPage && <li ref={loadMoreRef} className="p-2 text-center text-sm text-neutral-500">{loadingMore ? 'Carregando mutuários...' : null}</li>}
                    </ul>
                </div>
            </div>
        </div>
    );
}

export default SelectEmployee;
