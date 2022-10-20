import React from 'react';

export default function ApplicationLogo({ className }) {
    const style = {
        filter: 'brightness(70%)'
    }

    return (
            <img src={route().t.url + "/img/logo_branco.svg"} className={className} style={style} title="IFCE - Campus Sobral"/>
    );
}
