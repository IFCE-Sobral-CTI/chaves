export function formatPhone(value) {
    const original = String(value ?? '');
    const digits = original.replace(/\D/g, '');

    if (digits.length > 11) {
        return original;
    }

    if (digits.length <= 2) {
        return digits ? `(${digits}` : '';
    }

    const areaCode = digits.slice(0, 2);
    const number = digits.slice(2);

    if (number.length <= 4) {
        return `(${areaCode}) ${number}`;
    }

    const prefixLength = digits.length > 10 ? 5 : 4;

    return `(${areaCode}) ${digits.slice(2, 2 + prefixLength)}-${digits.slice(2 + prefixLength)}`;
}
