import { router } from '@inertiajs/react';
import { useEffect, useState } from 'react';

export default function useIndexSearch(url, termSearch) {
    const serverTerm = termSearch ?? '';
    const [term, setTerm] = useState(serverTerm);

    useEffect(() => {
        setTerm(serverTerm);
    }, [serverTerm]);

    useEffect(() => {
        if (term === serverTerm) return;

        let cancelToken;
        const debounce = setTimeout(() => {
            router.get(url, { term, page: 1 }, {
                preserveState: true,
                replace: true,
                onCancelToken: token => { cancelToken = token; },
            });
        }, 300);

        return () => {
            clearTimeout(debounce);
            cancelToken?.cancel();
        };
    }, [term, serverTerm, url]);

    return { term, setTerm };
}
