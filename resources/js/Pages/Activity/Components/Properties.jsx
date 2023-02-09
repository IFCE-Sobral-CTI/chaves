import React from "react";

export default function Properties({ properties }) {
    const attributes = (attributes) => {
        let result = [];
        for (const[key, value] of Object.entries(attributes)) {
            result.push((
                <div key={'key' + key}>
                    <div className="text-sm font-light capitalize">{`${key}`}</div>
                    <div>{`${value}`}</div>
                </div>
            ));
        }
        return result;
    }

    return (
        <div className="flex">
            {properties.attributes && <div className="lg:w-1/3">
                <div className="ml-3 text-sm">Novos valores</div>
                <div className="ml-8">{attributes(properties.attributes)}</div>
            </div>}
            {properties.old && <div className="border-l">
                <div className="ml-3 text-sm">Antigos valores</div>
                <div className="ml-8">{attributes(properties.old)}</div>
            </div>}
        </div>
    );
}
