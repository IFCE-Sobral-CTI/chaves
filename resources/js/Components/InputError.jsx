export default function InputError({ message, className = '', id }) {
    return message ? <p id={id} className={'text-sm text-red-600 ' + className}>{message}</p> : null;
}
