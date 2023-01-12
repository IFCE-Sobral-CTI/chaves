import 'tw-elements';
import Input from '@/Components/Form/Input';
import InputError from '@/Components/InputError';
import { useEffect, useState } from "react";
import { useForm } from '@inertiajs/inertia-react';

export default function Receive({ borrow, keys, received }) {
    const [keysReceived, setKeysReceived] = useState(received);
    const [chaves, setChaves] = useState([]);

    const { data, setData, post, processing, errors, reset } = useForm('receivedKeys', {
        returned_by: ''
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('borrows.receive', {borrow: borrow.id, chaves: chaves.join('|')}), {
            preserveScroll: true,
            onSuccess: () => {
                setChaves([]);
                reset();
            }
        });
    };

    const onHandleChangeKeys = (event) => {
        const value = parseInt(event.target.value);
        let list = chaves;

        if (list.includes(value)) {
            list.splice(list.indexOf(value), 1);
        } else {
            list.push(value);
        }

        setChaves(list);
    }

    useEffect(() => {
        setKeysReceived(received);
    });

    return (
        <>
            <button
                type="button"
                data-bs-toggle="modal"
                data-bs-target="#giveBack-modal"
                className="inline-flex items-center gap-2 px-4 py-2 text-sm tracking-widest text-white transition duration-150 ease-in-out border border-transparent rounded-md bg-green-light active:bg-green-dark hover:bg-green"
            >
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" className="w-5 h-5" viewBox="0 0 16 16">
                    <path d="M0 8a4 4 0 0 1 7.465-2H14a.5.5 0 0 1 .354.146l1.5 1.5a.5.5 0 0 1 0 .708l-1.5 1.5a.5.5 0 0 1-.708 0L13 9.207l-.646.647a.5.5 0 0 1-.708 0L11 9.207l-.646.647a.5.5 0 0 1-.708 0L9 9.207l-.646.647A.5.5 0 0 1 8 10h-.535A4 4 0 0 1 0 8zm4-3a3 3 0 1 0 2.712 4.285A.5.5 0 0 1 7.163 9h.63l.853-.854a.5.5 0 0 1 .708 0l.646.647.646-.647a.5.5 0 0 1 .708 0l.646.647.646-.647a.5.5 0 0 1 .708 0l.646.647.793-.793-1-1h-6.63a.5.5 0 0 1-.451-.285A3 3 0 0 0 4 5z"/>
                    <path d="M4 8a1 1 0 1 1-2 0 1 1 0 0 1 2 0z"/>
                </svg>
                <span>Recerber chaves</span>
            </button>
            <div className="fixed top-0 left-0 hidden w-full h-full overflow-x-hidden overflow-y-auto outline-none modal fade" id="giveBack-modal" data-bs-backdrop="static" data-bs-keyboard="false" tabIndex={-1} aria-labelledby="staticBackdropLabel" aria-hidden="true">
                <div className="relative w-auto pointer-events-none modal-dialog modal-dialog-centered">
                    <div className="relative flex flex-col w-full text-current bg-white border-none rounded-md shadow-lg outline-none pointer-events-auto modal-content bg-clip-padding">
                        <div className="flex items-center justify-between flex-shrink-0 p-4 border-b border-gray-200 modal-header rounded-t-md">
                            <h5 className="text-xl font-medium leading-normal text-gray-800" id="exampleModalLabel">
                                Receber chave(s)
                            </h5>
                            <button type="button" className="box-content w-4 h-4 p-1 text-black border-none rounded-none opacity-50 btn-close focus:shadow-none focus:outline-none focus:opacity-100 hover:text-black hover:opacity-75 hover:no-underline" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form onSubmit={submit}>
                            <div className="relative p-4 modal-body">
                                <div className="mb-4">
                                    <label htmlFor="returned_by" className="font-light">Devolução</label>
                                    <Input value={data.returned_by} type={'text'} name={'returned_by'} handleChange={(e) => setData('returned_by', e.target.value)} required={true} placeholder={'Quem entregou a(s) chave(s)?'} />
                                    <InputError message={errors.returned_by} />
                                </div>
                                <div className="font-light">Chaves</div>
                                <div className="p-2 mb-4 border border-gray-400 rounded-md">
                                    <div className="">
                                        {keys.length != received.length
                                        ? keys.map((key, i) => {
                                            if (!keysReceived.includes(key.id))
                                                return (
                                                    <div className="mb-2" key={i}>
                                                        <div className="flex gap-2">
                                                            <input type="checkbox" name={"keys" + i} value={key.id} id={'key-'+key.id} onChange={onHandleChangeKeys} className="w-5 h-5 bg-gray-100 border rounded-md text-green focus:ring-green-light" />
                                                            <label className="text-sm" htmlFor={'key-' + key.id}>{key.number} - {key.room.description}</label>
                                                            <InputError message={errors.keys} />
                                                        </div>
                                                    </div>
                                                );
                                        })
                                        :<span className='text-sm font-light'>Todas as chaves já foram devolvidas.</span>}
                                    </div>
                                </div>
                            </div>
                            <div className="flex flex-wrap items-center justify-end flex-shrink-0 p-4 border-t border-gray-200 modal-footer rounded-b-md">
                                <button
                                    type="button"
                                    className="flex items-center px-6 py-2.5 bg-gray-600 text-white font-light leading-tight rounded shadow-md hover:bg-gray-700 hover:shadow-lg focus:bg-gray-700 focus:shadow-lg focus:outline-none focus:ring-0 active:bg-gray-800 active:shadow-lg transition duration-150 ease-in-out"
                                    data-bs-dismiss="modal"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" className="w-5 h-5 mr-3" role="img" aria-hidden="true" viewBox="0 0 16 16">
                                        <path d="M2.146 2.854a.5.5 0 1 1 .708-.708L8 7.293l5.146-5.147a.5.5 0 0 1 .708.708L8.707 8l5.147 5.146a.5.5 0 0 1-.708.708L8 8.707l-5.146 5.147a.5.5 0 0 1-.708-.708L7.293 8 2.146 2.854Z"/>
                                    </svg>
                                    <span>Fechar</span>
                                </button>
                                <button
                                    id={'button-submit'}
                                    data-bs-dismiss="modal"
                                    type="submit"
                                    className="flex items-centerk px-6 py-2.5 bg-green text-white font-light leading-tight rounded shadow-md hover:bg-green-dark hover:shadow-lg focus:bg-green-dark focus:shadow-lg focus:outline-none focus:ring-0 active:bg-red-800 active:shadow-lg transition duration-150 ease-in-out ml-1"
                                    disabled={processing}
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" className="w-5 h-5 mr-3" role="img" aria-hidden="true" viewBox="0 0 16 16">
                                        <path d="M12.736 3.97a.733.733 0 0 1 1.047 0c.286.289.29.756.01 1.05L7.88 12.01a.733.733 0 0 1-1.065.02L3.217 8.384a.757.757 0 0 1 0-1.06.733.733 0 0 1 1.047 0l3.052 3.093 5.4-6.425a.247.247 0 0 1 .02-.022Z"/>
                                    </svg>
                                    Enviar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </>
    )
}
