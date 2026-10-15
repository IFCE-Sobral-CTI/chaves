import React from "react";

export default function Properties({ properties, attributeChanges }) {
    const { before, after, attributes: legacyAttributes, old: legacyOld, ...details } = properties ?? {};
    const newValues = after !== undefined ? after : attributeChanges?.attributes ?? legacyAttributes;
    const oldValues = before !== undefined ? before : attributeChanges?.old ?? legacyOld;
    const attributes = (attributes) => {
        if (attributes === null) {
            return <div>null</div>;
        }
        let result = [];
        for (const[key, value] of Object.entries(attributes)) {
            result.push((
                <div key={'key' + key}>
                    <div className="text-sm font-light capitalize">{`${key}`}</div>
                    <div>{typeof value === 'string' ? value : JSON.stringify(value)}</div>
                </div>
            ));
        }
        return result;
    }

    return (
        <div className="flex">
            {newValues !== undefined && <div className="lg:w-1/3">
                <div className="ml-3 text-sm">Novos valores</div>
                <div className="ml-8">{attributes(newValues)}</div>
            </div>}
            {oldValues !== undefined && <div className="border-l">
                <div className="ml-3 text-sm">Antigos valores</div>
                <div className="ml-8">{attributes(oldValues)}</div>
            </div>}
            {Object.keys(details).length > 0 && <div className="border-l">
                <div className="ml-3 text-sm">Detalhes</div>
                <div className="ml-8">{attributes(details)}</div>
            </div>}
        </div>
    );
}
