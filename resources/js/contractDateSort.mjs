export function sortContractsByDate(contracts, field, direction) {
    return [...contracts].sort((a, b) => {
        const left = a[field] || '';
        const right = b[field] || '';
        if (!left) return right ? 1 : 0;
        if (!right) return -1;
        return (left < right ? -1 : left > right ? 1 : 0) * direction;
    });
}
