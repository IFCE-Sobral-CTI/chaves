import React from "react";

function Panel({ className, children }) {
    return (
        <>
            <div className={"w-full p-2 bg-white shadow-md rounded-lg " + className}>
                {children}
            </div>
        </>
    )
}

export default Panel;
