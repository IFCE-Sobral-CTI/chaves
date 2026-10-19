import React, { useEffect, useRef, useState } from "react";
import Input from "@/Components/Form/Input";

export default function Search({ endpoint, onChange, values = [], selectedEmployees = [] }) {
    const [list, setList] = useState([]);
    const [known, setKnown] = useState(selectedEmployees);
    const [term, setTerm] = useState('');
    const [add, setAdd] = useState(values);
    const [page, setPage] = useState(1);
    const [lastPage, setLastPage] = useState(1);
    const [loadingMore, setLoadingMore] = useState(false);
    const [loadingSearch, setLoadingSearch] = useState(true);
    const listRef = useRef(null);
    const loadMoreRef = useRef(null);
    const loadingMoreRef = useRef(false);
    const moreController = useRef(null);

    useEffect(() => {
        onChange(add);
    }, [add]);

    useEffect(() => {
        const controller = new AbortController();
        setLoadingSearch(true);
        const timer = setTimeout(async () => {
            try {
                const params = new URLSearchParams({ term, page: '1', employee_type: '1' });
                const response = await fetch(`${endpoint}?${params}`, { signal: controller.signal, headers: { Accept: 'application/json' } });
                if (!response.ok) {
                    setList([]);
                    return;
                }
                const result = await response.json();
                if (controller.signal.aborted) return;
                setList(result.data);
                setKnown(items => [...items, ...result.data].filter((item, index, all) => all.findIndex(other => other.id === item.id) === index));
                setPage(result.current_page);
                setLastPage(result.last_page);
            } catch (error) {
                if (error.name !== 'AbortError') setList([]);
            } finally {
                if (!controller.signal.aborted) setLoadingSearch(false);
            }
        }, 250);

        setPage(1);
        setLastPage(1);
        return () => {
            clearTimeout(timer);
            controller.abort();
            moreController.current?.abort();
        };
    }, [term, endpoint]);

    const loadMore = async () => {
        if (loadingMoreRef.current || page >= lastPage) return;

        loadingMoreRef.current = true;
        setLoadingMore(true);
        const params = new URLSearchParams({ term, page: String(page + 1), employee_type: '1' });
        moreController.current?.abort();
        const controller = new AbortController();
        moreController.current = controller;
        try {
            const response = await fetch(`${endpoint}?${params}`, { signal: controller.signal, headers: { Accept: 'application/json' } });
            if (!response.ok) return;
            const result = await response.json();
            if (controller.signal.aborted) return;
            setList(items => [...items, ...result.data]);
            setKnown(items => [...items, ...result.data].filter((item, index, all) => all.findIndex(other => other.id === item.id) === index));
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
        if (!root || !target || page >= lastPage || loadingMore) return;

        const observer = new IntersectionObserver(([entry]) => {
            if (entry.isIntersecting) loadMore();
        }, { root });

        observer.observe(target);
        return () => observer.disconnect();
    }, [page, lastPage, loadingMore, term, endpoint]);

    const toggleItemHandler = (id) => {
        setAdd(current => current.some(item => String(item) === String(id))
            ? current.filter(item => String(item) !== String(id))
            : [id, ...current]);
    };

    const items = list.filter(item => !add.some(id => String(id) === String(item.id))).map(item => (
        <tr className="border-t border-neutral-200 cursor-pointer hover:bg-neutral-100" onClick={() => toggleItemHandler(item.id)} key={item.id}>
            <td className="w-1/6 p-2">{item.registry}</td>
            <td className="w-4/6 p-2">{item.name}</td>
            <td className="w-1/6 p-2">
                <div className="flex justify-end">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" className="w-5 h-5" viewBox="0 0 16 16">
                        <path d="M13.854 3.646a.5.5 0 0 1 0 .708l-7 7a.5.5 0 0 1-.708 0l-3.5-3.5a.5.5 0 1 1 .708-.708L6.5 10.293l6.646-6.647a.5.5 0 0 1 .708 0z"/>
                    </svg>
                </div>
            </td>
        </tr>
    ));

    const itemsAdd = known.filter(item => add.some(id => String(id) === String(item.id))).map(item => (
        <tr className="border-t border-neutral-200 cursor-pointer hover:bg-neutral-100" onClick={() => toggleItemHandler(item.id)} key={item.id}>
            <td className="w-1/6 p-2">{item.registry}</td>
            <td className="w-4/6 p-2">{item.name}</td>
            <td className="w-1/6 p-2">
                <div className="flex justify-end">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" className="w-5 h-5" viewBox="0 0 16 16">
                        <path d="M2.146 2.854a.5.5 0 1 1 .708-.708L8 7.293l5.146-5.147a.5.5 0 0 1 .708.708L8.707 8.707l5.147 5.146a.5.5 0 0 1-.708.708L8 8.707l-5.146 5.147a.5.5 0 0 1-.708-.708L7.293 8 2.146 2.854Z"/>
                    </svg>
                </div>
            </td>
        </tr>
    ));

    return (
        <div className="flex flex-col gap-0">
            <span className="font-light">Servidores responsáveis</span>
            <div className="flex gap-4">
                <div className="flex flex-col flex-1 gap-2">
                    <Input
                        value={term}
                        type={'search'}
                        handleChange={(event) => setTerm(event.target.value)}
                        placeholder={'Digite sua pesquisa'}
                    />
                    <div ref={listRef} className="p-2 overflow-auto border rounded-lg max-h-96 border-neutral-400 scrollbar-thumb-gray-400 scrollbar-track-gray-100 scrollbar-thin scrollbar-thumb-rounded-full scrollbar-track-rounded-full">
                        <table className="table w-full table-auto">
                            <thead>
                                <tr>
                                    <th className="px-1 pt-2 font-normal text-left">Matrícula</th>
                                    <th className="px-1 pt-2 font-normal text-left">Nome</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody className="font-light">{items}</tbody>
                        </table>
                        {loadingSearch && list.length === 0 && <p className="p-2 text-center text-sm text-neutral-500">Carregando servidores...</p>}
                        {page < lastPage && <div ref={loadMoreRef} className="p-2 text-center text-sm text-neutral-500">{loadingMore ? 'Carregando servidores...' : null}</div>}
                    </div>
                </div>
                <div className="flex-1 border rounded-lg border-neutral-400">
                    <div className="mx-2 mt-2 font-light border-b border-neutral-400">Itens selecionados</div>
                    <div className="ml-2 overflow-auto max-h-96 scrollbar-thumb-gray-400 scrollbar-track-gray-100 scrollbar-thin scrollbar-thumb-rounded-full scrollbar-track-rounded-full">
                        <table className="table w-full table-auto">
                            <thead>
                                <tr>
                                    <th className="px-1 pt-2 font-normal text-left">Matrícula</th>
                                    <th className="px-1 pt-2 font-normal text-left">Nome</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody className="font-light">{itemsAdd}</tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    );
}
