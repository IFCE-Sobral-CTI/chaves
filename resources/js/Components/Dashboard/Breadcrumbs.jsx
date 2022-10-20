import { Link } from "@inertiajs/inertia-react";
import React from "react";
import Panel from "./Panel";

function Breadcrumbs({ href = [] }) {
    const separator = ' / ';
    const result = () => {
        if (href?.length) {
            href = [{label: 'Principal', url: route('home')}].concat(href);
            return href.map((item, i) => {
                if (item.url && ((i + 1) < href.length))
                    return (
                        <span key={i.toString()}>
                            <Link href={item.url} className="text-green">
                                {item.label}
                            </Link>
                            {((i + 1) < href.length) && <span className="text-green">{separator}</span>}
                        </span>
                    )
                return <span key={i.toString()}>{item.label}</span>
            });
        }

        return (<span>Principal</span>);
    }
    return (
        <>
            <div className={'px-3 md:block font-light'}>
                {result()}
            </div>
        </>
    )
}

export default Breadcrumbs;
