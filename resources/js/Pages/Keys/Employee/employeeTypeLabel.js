const employeeTypeLabels = {
    1: 'Servidor',
    2: 'Colaborador',
    3: 'Discente',
    4: 'Externo',
};

export default function employeeTypeLabel(type) {
    return employeeTypeLabels[type] ?? '';
}
